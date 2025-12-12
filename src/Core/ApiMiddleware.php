<?php

declare(strict_types=1);

namespace BNT\Core;

class ApiMiddleware
{
    public function __construct(
        private ApiAuth $apiAuth
    ) {}
    
    /**
     * Middleware to require API authentication
     */
    public function requireAuth(): ?array
    {
        $token = $this->apiAuth->getTokenFromRequest();
        
        if (!$token) {
            ApiResponse::unauthorized('API token required');
            return null;
        }
        
        $ship = $this->apiAuth->validateToken($token);
        
        if (!$ship) {
            ApiResponse::unauthorized('Invalid or expired API token');
            return null;
        }
        
        return $ship;
    }
    
    /**
     * Middleware to handle CORS
     */
    public function handleCors(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // In production, specify allowed origins
        // $allowedOrigins = ['https://yourdomain.com'];
        // if (in_array($origin, $allowedOrigins)) {
        //     header("Access-Control-Allow-Origin: $origin");
        // }
        
        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Token');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

