<?php

declare(strict_types=1);

namespace BNT\Core;

use BNT\Models\Ship;

class ApiAuth
{
    private const TOKEN_LENGTH = 64;
    private const TOKEN_EXPIRY_DAYS = 90; // 90 days expiry
    
    public function __construct(
        private Database $db,
        private Ship $shipModel
    ) {}
    
    /**
     * Generate a new API token for a user
     */
    public function generateToken(int $shipId, string $tokenName = 'Mobile App'): array
    {
        // Generate secure random token
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        $tokenHash = hash('sha256', $token);
        
        // Calculate expiry
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::TOKEN_EXPIRY_DAYS . ' days'));
        
        // Store token hash in database
        $this->db->execute(
            'INSERT INTO api_tokens (ship_id, token_hash, token_name, expires_at) 
             VALUES (:ship_id, :token_hash, :token_name, :expires_at)',
            [
                'ship_id' => $shipId,
                'token_hash' => $tokenHash,
                'token_name' => $tokenName,
                'expires_at' => $expiresAt
            ]
        );
        
        return [
            'token' => $token,
            'expires_at' => $expiresAt,
            'token_name' => $tokenName
        ];
    }
    
    /**
     * Validate API token and return ship data
     */
    public function validateToken(string $token): ?array
    {
        $tokenHash = hash('sha256', $token);
        
        $tokenData = $this->db->fetchOne(
            'SELECT * FROM api_tokens WHERE token_hash = :hash AND expires_at > NOW()',
            ['hash' => $tokenHash]
        );
        
        if (!$tokenData) {
            return null;
        }
        
        // Update last used timestamp
        $this->db->execute(
            'UPDATE api_tokens SET last_used_at = NOW() WHERE token_id = :id',
            ['id' => $tokenData['token_id']]
        );
        
        // Get ship data
        $ship = $this->shipModel->find((int)$tokenData['ship_id']);
        
        if (!$ship || $ship['ship_destroyed']) {
            return null;
        }
        
        return $ship;
    }
    
    /**
     * Get token from request headers
     */
    public function getTokenFromRequest(): ?string
    {
        // Check Authorization header: "Bearer <token>"
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        // Fallback: check X-API-Token header
        return $_SERVER['HTTP_X_API_TOKEN'] ?? null;
    }
    
    /**
     * Revoke a token
     */
    public function revokeToken(string $token): bool
    {
        $tokenHash = hash('sha256', $token);
        
        return $this->db->execute(
            'DELETE FROM api_tokens WHERE token_hash = :hash',
            ['hash' => $tokenHash]
        );
    }
    
    /**
     * Revoke all tokens for a ship
     */
    public function revokeAllTokens(int $shipId): bool
    {
        return $this->db->execute(
            'DELETE FROM api_tokens WHERE ship_id = :ship_id',
            ['ship_id' => $shipId]
        );
    }
    
    /**
     * Get all tokens for a ship
     */
    public function getShipTokens(int $shipId): array
    {
        return $this->db->fetchAll(
            'SELECT token_id, token_name, last_used_at, expires_at, created_at 
             FROM api_tokens 
             WHERE ship_id = :ship_id AND expires_at > NOW()
             ORDER BY created_at DESC',
            ['ship_id' => $shipId]
        );
    }
}

