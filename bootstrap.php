<?php

// Load Nova core
require_once __DIR__ . '/vendor/autoload.php';

// Initialize session for state persistence
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
  session_start();
}
