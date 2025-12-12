<?php

declare(strict_types=1);

namespace BNT\Controllers;

use BNT\Core\ApiAuth;
use BNT\Core\ApiMiddleware;
use BNT\Core\ApiResponse;
use BNT\Models\Ship;
use BNT\Models\Universe;
use BNT\Models\Planet;
use BNT\Models\Combat;
use BNT\Models\ShipType;

class ApiGameController
{
    public function __construct(
        private Ship $shipModel,
        private Universe $universeModel,
        private Planet $planetModel,
        private Combat $combatModel,
        private ApiAuth $apiAuth,
        private ApiMiddleware $middleware,
        private array $config
    ) {}
    
    private function requireAuth(): array
    {
        $ship = $this->middleware->requireAuth();
        if (!$ship) {
            exit; // Response already sent
        }
        return $ship;
    }
    
    /**
     * GET /api/v1/game/main
     * Get main game screen data
     */
    public function main(): void
    {
        $ship = $this->requireAuth();
        
        // If on planet, leave it automatically
        if ($ship['on_planet']) {
            $this->shipModel->getDb()->execute(
                'UPDATE ships SET on_planet = FALSE, planet_id = 0 WHERE ship_id = :id',
                ['id' => (int)$ship['ship_id']]
            );
            $ship = $this->shipModel->find((int)$ship['ship_id']);
        }
        
        $sector = $this->universeModel->getSector((int)$ship['sector']);
        $links = $this->universeModel->getLinkedSectors((int)$ship['sector']);
        $planets = $this->planetModel->getPlanetsInSector((int)$ship['sector']);
        $shipsInSector = $this->shipModel->getShipsInSector((int)$ship['sector'], (int)$ship['ship_id']);
        
        $maxHolds = $this->calculateHolds($ship['hull'], $ship['ship_type']);
        $usedHolds = $ship['ship_ore'] + $ship['ship_organics'] +
                     $ship['ship_goods'] + $ship['ship_energy'] +
                     $ship['ship_colonists'];
        
        $isStarbaseSector = $this->universeModel->isStarbase((int)$ship['sector']);
        
        ApiResponse::success([
            'ship' => $ship,
            'sector' => $sector,
            'links' => $links,
            'planets' => $planets,
            'ships_in_sector' => $shipsInSector,
            'holds' => [
                'max' => $maxHolds,
                'used' => $usedHolds,
                'available' => $maxHolds - $usedHolds
            ],
            'is_starbase_sector' => $isStarbaseSector
        ]);
    }
    
    /**
     * POST /api/v1/game/move/:sector
     * Move to a new sector
     */
    public function move(int $destinationSector): void
    {
        $ship = $this->requireAuth();
        
        $turnCost = ShipType::getTurnCost($ship['ship_type']);
        
        if ($ship['turns'] < $turnCost) {
            ApiResponse::error('Not enough turns', 'INSUFFICIENT_TURNS', 400);
        }
        
        if (!$this->universeModel->isLinked((int)$ship['sector'], $destinationSector)) {
            ApiResponse::error('Sectors are not linked', 'SECTORS_NOT_LINKED', 400);
        }
        
        $this->shipModel->useTurns((int)$ship['ship_id'], $turnCost);
        $this->shipModel->update((int)$ship['ship_id'], ['sector' => $destinationSector]);
        
        // Log movement
        $this->logMovement((int)$ship['ship_id'], $destinationSector);
        
        // Check for mines in destination sector
        $mineDeflectors = (int)($ship['dev_minedeflector'] ?? 0);
        $mineResult = $this->combatModel->checkMines(
            (int)$ship['ship_id'], 
            $destinationSector, 
            (int)$ship['hull'], 
            $mineDeflectors
        );
        
        $response = [
            'sector' => $destinationSector,
            'turns_used' => $turnCost,
            'mine_result' => null,
            'fighter_result' => null
        ];
        
        if ($mineResult['deflector_used']) {
            $response['mine_result'] = [
                'deflector_used' => true,
                'message' => $mineResult['message']
            ];
        } elseif ($mineResult['hit']) {
            $this->combatModel->applyDamageToShip((int)$ship['ship_id'], $mineResult['damage']);
            
            if ($mineResult['mines_destroyed'] > 0) {
                $this->removeMines($destinationSector, $mineResult['mines_destroyed']);
            }
            
            if ($mineResult['ship_destroyed']) {
                ApiResponse::error('Your ship was destroyed by mines!', 'SHIP_DESTROYED', 400, [
                    'sector' => $destinationSector
                ]);
            }
            
            $response['mine_result'] = [
                'hit' => true,
                'damage' => $mineResult['damage'],
                'message' => $mineResult['message']
            ];
        }
        
        // Check for sector fighters
        $ship = $this->shipModel->find((int)$ship['ship_id']);
        $fighterResult = $this->combatModel->checkSectorFighters($ship, $destinationSector);
        
        if ($fighterResult['attacked']) {
            if ($fighterResult['damage'] > 0) {
                $this->combatModel->applyDamageToShip((int)$ship['ship_id'], $fighterResult['damage']);
            }
            
            if ($fighterResult['ship_destroyed']) {
                ApiResponse::error('Your ship was destroyed by sector fighters!', 'SHIP_DESTROYED', 400);
            }
            
            $response['fighter_result'] = [
                'attacked' => true,
                'damage' => $fighterResult['damage'],
                'message' => $fighterResult['message']
            ];
        }
        
        // Reload ship data
        $ship = $this->shipModel->find((int)$ship['ship_id']);
        
        ApiResponse::success([
            'ship' => $ship,
            'movement' => $response
        ]);
    }
    
    /**
     * GET /api/v1/game/scan
     * Get detailed sector scan
     */
    public function scan(): void
    {
        $ship = $this->requireAuth();
        
        $sector = $this->universeModel->getSector((int)$ship['sector']);
        $links = $this->universeModel->getLinkedSectors((int)$ship['sector']);
        $planets = $this->planetModel->getPlanetsInSector((int)$ship['sector']);
        $shipsInSector = $this->shipModel->getShipsInSector((int)$ship['sector'], (int)$ship['ship_id']);
        
        $sql = "SELECT sd.*, s.character_name
                FROM sector_defence sd
                JOIN ships s ON sd.ship_id = s.ship_id
                WHERE sd.sector_id = :sector_id";
        
        $defenses = $this->shipModel->getDb()->fetchAll($sql, ['sector_id' => $ship['sector']]);
        
        ApiResponse::success([
            'ship' => $ship,
            'sector' => $sector,
            'links' => $links,
            'planets' => $planets,
            'ships_in_sector' => $shipsInSector,
            'defenses' => $defenses
        ]);
    }
    
    /**
     * GET /api/v1/game/status
     * Get ship status
     */
    public function status(): void
    {
        $ship = $this->requireAuth();
        
        $score = $this->shipModel->calculateScore((int)$ship['ship_id']);
        $ship = $this->shipModel->find((int)$ship['ship_id']);
        $planets = $this->planetModel->getPlayerPlanets((int)$ship['ship_id']);
        
        $maxHolds = $this->calculateHolds($ship['hull'], $ship['ship_type']);
        $maxEnergy = $this->calculateEnergy($ship['power']);
        $maxFighters = $this->calculateFighters($ship['computer']);
        $maxTorps = $this->calculateTorps($ship['torp_launchers']);
        
        ApiResponse::success([
            'ship' => $ship,
            'planets' => $planets,
            'capacities' => [
                'holds' => $maxHolds,
                'energy' => $maxEnergy,
                'fighters' => $maxFighters,
                'torps' => $maxTorps
            ],
            'score' => $score
        ]);
    }
    
    /**
     * GET /api/v1/game/planet/:id
     * Get planet information
     */
    public function planet(int $planetId): void
    {
        $ship = $this->requireAuth();
        
        $planet = $this->planetModel->find($planetId);
        
        if (!$planet) {
            ApiResponse::notFound('Planet not found');
        }
        
        if ($planet['sector_id'] != $ship['sector']) {
            ApiResponse::error('You must be in the same sector as the planet', 'WRONG_SECTOR', 400);
        }
        
        $ownerName = null;
        if ($planet['owner'] > 0) {
            $owner = $this->shipModel->find($planet['owner']);
            $ownerName = $owner ? $owner['character_name'] : 'Unknown';
        }
        
        $isOwner = $planet['owner'] == $ship['ship_id'];
        $isOnPlanet = $ship['on_planet'] && $ship['planet_id'] == $planetId;
        
        ApiResponse::success([
            'planet' => $planet,
            'owner_name' => $ownerName,
            'is_owner' => $isOwner,
            'is_on_planet' => $isOnPlanet
        ]);
    }
    
    /**
     * POST /api/v1/game/land/:id
     * Land on a planet
     */
    public function landOnPlanet(int $planetId): void
    {
        $ship = $this->requireAuth();
        
        $planet = $this->planetModel->find($planetId);
        
        if (!$planet || $planet['sector_id'] != $ship['sector']) {
            ApiResponse::error('Cannot land on this planet', 'INVALID_PLANET', 400);
        }
        
        if ($planet['owner'] != 0 && $planet['owner'] != $ship['ship_id']) {
            ApiResponse::error('This planet is owned by another player', 'PLANET_OWNED', 403);
        }
        
        $this->shipModel->getDb()->execute(
            'UPDATE ships SET on_planet = TRUE, planet_id = :planet_id WHERE ship_id = :id',
            ['planet_id' => $planetId, 'id' => (int)$ship['ship_id']]
        );
        
        $ship = $this->shipModel->find((int)$ship['ship_id']);
        
        ApiResponse::success([
            'ship' => $ship,
            'planet' => $planet
        ], 'Landed on planet successfully');
    }
    
    /**
     * POST /api/v1/game/leave
     * Leave current planet
     */
    public function leavePlanet(): void
    {
        $ship = $this->requireAuth();
        
        if (!$ship['on_planet']) {
            ApiResponse::error('You are not on a planet', 'NOT_ON_PLANET', 400);
        }
        
        $this->shipModel->getDb()->execute(
            'UPDATE ships SET on_planet = FALSE, planet_id = 0 WHERE ship_id = :id',
            ['id' => (int)$ship['ship_id']]
        );
        
        $ship = $this->shipModel->find((int)$ship['ship_id']);
        
        ApiResponse::success([
            'ship' => $ship
        ], 'Left planet successfully');
    }
    
    // Helper methods
    private function calculateHolds(int $level, string $shipType): int
    {
        $baseCapacity = (int)round(pow(1.5, $level) * 100);
        return ShipType::getCargoCapacity($shipType, $baseCapacity);
    }
    
    private function calculateEnergy(int $level): int
    {
        return (int)round(pow(1.5, $level) * 500);
    }
    
    private function calculateFighters(int $level): int
    {
        return (int)round(pow(1.5, $level) * 100);
    }
    
    private function calculateTorps(int $level): int
    {
        return (int)round(pow(1.5, $level) * 100);
    }
    
    private function logMovement(int $shipId, int $sectorId): void
    {
        $sql = "INSERT INTO movement_log (ship_id, sector_id, time) VALUES (:ship_id, :sector_id, NOW())";
        $this->shipModel->getDb()->execute($sql, ['ship_id' => $shipId, 'sector_id' => $sectorId]);
    }
    
    private function removeMines(int $sectorId, int $count): void
    {
        $sql = "SELECT * FROM sector_defence
                WHERE sector_id = :sector AND defence_type = 'M'
                ORDER BY quantity ASC";
        $mines = $this->shipModel->getDb()->fetchAll($sql, ['sector' => $sectorId]);
        
        $remaining = $count;
        foreach ($mines as $mine) {
            if ($remaining <= 0) break;
            
            if ($mine['quantity'] <= $remaining) {
                $this->shipModel->getDb()->execute(
                    'DELETE FROM sector_defence WHERE defence_id = :id',
                    ['id' => $mine['defence_id']]
                );
                $remaining -= $mine['quantity'];
            } else {
                $this->shipModel->getDb()->execute(
                    'UPDATE sector_defence SET quantity = quantity - :count WHERE defence_id = :id',
                    ['count' => $remaining, 'id' => $mine['defence_id']]
                );
                $remaining = 0;
            }
        }
    }
}

