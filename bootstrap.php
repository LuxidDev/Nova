<?php

// Load Nova core
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration (if exists)
$configFile = __DIR__ . '/config/nova.php';
if (file_exists($configFile)) {
  $config = require_once $configFile;

  // Configure compiler
  if ($config['cache']['enabled'] ?? false) {
    Luxid\Nova\Compiler::setCachePath($config['cache']['path']);
    Luxid\Nova\Compiler::enableDebug($config['cache']['debug'] ?? false);
  }

  // Configure component cache
  if ($config['component_cache']['enabled'] ?? false) {
    Luxid\Nova\ComponentCache::enable($config['component_cache']['path']);
  }

  // Configure performance monitoring
  if ($config['performance']['enabled'] ?? false) {
    Luxid\Nova\Performance::enable();
  }
}

// Initialize session for state persistence
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
  session_start();
}
