<?php

declare(strict_types=1);

namespace BNT\Controllers;

use BNT\Models\Ship;
use BNT\Models\Skill;
use BNT\Core\Session;

/**
 * Skill management controller
 */
class SkillController
{
    public function __construct(
        private Ship $shipModel,
        private Skill $skillModel,
        private Session $session,
        private array $config
    ) {}

    /**
     * Display skills page
     */
    public function index(): void
    {
        $shipId = $this->session->getUserId();
        if (!$shipId) {
            header('Location: /');
            exit;
        }

        $ship = $this->shipModel->find($shipId);
        if (!$ship) {
            $this->session->set('error', 'Ship not found');
            header('Location: /');
            exit;
        }

        $skills = $this->skillModel->getSkills($shipId);

        // Get detailed info for each skill
        $skillDetails = [];
        foreach (['trading', 'combat', 'engineering', 'leadership'] as $skillType) {
            $skillDetails[$skillType] = $this->skillModel->getSkillInfo($skillType, $skills[$skillType]);
            $skillDetails[$skillType]['level'] = $skills[$skillType];

            // Calculate cost for next level
            $skillDetails[$skillType]['next_cost'] = $this->calculateNextLevelCost($skills[$skillType]);
        }

        $session = $this->session;
        $title = 'Character Skills';
        $showHeader = true;
        
        // Extract variables to make them available to the view
        extract(compact('ship', 'skills', 'skillDetails', 'session', 'title', 'showHeader'));

        ob_start();
        include __DIR__ . '/../Views/skills.php';
        echo ob_get_clean();
    }

    /**
     * Allocate skill points
     */
    public function allocate(): void
    {
        $shipId = $this->session->getUserId();
        if (!$shipId) {
            header('Location: /');
            exit;
        }

        // Verify CSRF token
        $token = $_POST['csrf_token'] ?? '';
        if (!$this->session->validateCsrfToken($token)) {
            $this->session->set('error', 'Invalid security token');
            header('Location: /skills');
            exit;
        }

        $skillType = $_POST['skill'] ?? '';
        $points = (int)($_POST['points'] ?? 1);

        $result = $this->skillModel->allocateSkillPoints($shipId, $skillType, $points);

        if ($result['success']) {
            $this->session->set('message', $result['message']);
        } else {
            $this->session->set('error', $result['message']);
        }

        header('Location: /skills');
        exit;
    }

    /**
     * Calculate cost for next level
     */
    private function calculateNextLevelCost(int $currentLevel): int
    {
        if ($currentLevel >= 100) {
            return 0;
        }
        // Cost formula matches the model: 1 + floor(level / 10)
        return 1 + (int)floor($currentLevel / 10);
    }
}
