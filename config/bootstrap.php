<?php
// CHRONONAV_WEB_DOSS/config/bootstrap.php

// 1. Load Composer's autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load the dotenv library
try {
    // We create an immutable loader, pointing to the parent directory (project root)
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    
    // safeLoad() loads the file but doesn't throw an error if the .env file is missing
    $dotenv->safeLoad();
    
    // Optional: Ensure critical variables are present (e.g., in production)
    // $dotenv->required(['DB_USERNAME', 'DB_PASSWORD', 'DB_NAME'])->notEmpty();

} catch (\Exception $e) {
    // Log any errors during the loading process
    error_log("DotEnv failed to load: " . $e->getMessage());
}
?>