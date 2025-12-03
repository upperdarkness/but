#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use BNT\Core\Database;

echo "Fixing planet owner column to allow NULL...\n";

$config = require __DIR__ . '/../config/config.php';
$db = new Database($config);

try {
    // Make owner column nullable
    echo "Making owner column nullable...\n";
    $db->execute("ALTER TABLE planets ALTER COLUMN owner DROP NOT NULL");
    
    // Remove default value of 0
    echo "Removing default value...\n";
    $db->execute("ALTER TABLE planets ALTER COLUMN owner DROP DEFAULT");
    
    // Set default to NULL
    echo "Setting default to NULL...\n";
    $db->execute("ALTER TABLE planets ALTER COLUMN owner SET DEFAULT NULL");
    
    // Update existing planets with owner = 0 to NULL
    echo "Updating existing unowned planets...\n";
    $db->execute("UPDATE planets SET owner = NULL WHERE owner = 0");
    
    echo "Migration completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
