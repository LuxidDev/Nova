<?php
// bootstrap.php - Fixed ordering

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load Nova core - this also loads helpers.php which defines component() function
require_once __DIR__ . '/vendor/autoload.php';

// Now that helpers are loaded, load shared component definitions
require_once __DIR__ . '/components.php';

// Debug function
function nova_debug($message)
{
  $logFile = __DIR__ . '/nova-debug.log';
  file_put_contents($logFile, date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

// Handle Nova AJAX actions
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
  nova_debug("AJAX Request detected");

  // Parse JSON input if present
  $input = json_decode(file_get_contents('php://input'), true);
  nova_debug("Input: " . print_r($input, true));

  if ($input && isset($input['component']) && isset($input['action'])) {
    header('Content-Type: text/html');

    try {
      $componentName = $input['component'];
      $instanceId = $input['instance'] ?? null;
      $action = $input['action'];
      $params = $input['params'] ?? [];

      nova_debug("Action called - instanceId: {$instanceId}, action: {$action}");

      nova_debug("Component name: {$componentName}");

      // Check if component exists
      if (!\Luxid\Nova\ComponentManager::has($componentName)) {
        throw new Exception("Component '{$componentName}' not found");
      }

      // Call the action
      $html = nova_action($componentName, $instanceId, $action, $params);
      echo $html;
      exit;
    } catch (Exception $e) {
      nova_debug("Error: " . $e->getMessage());
      http_response_code(500);
      echo "<div class='nova-error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
      exit;
    }
  }

  // Handle form data submissions (non-JSON)
  if (isset($_POST['_action']) && isset($_POST['_component'])) {
    $componentName = $_POST['_component'];
    $instanceId = $_POST['_instance'] ?? $componentName;
    $action = $_POST['_action'];

    // Build params from form data
    $params = $_POST;
    unset($params['_action'], $params['_component'], $params['_instance']);

    try {
      $html = nova_action($componentName, $instanceId, $action, $params);
      echo $html;
      exit;
    } catch (Exception $e) {
      http_response_code(500);
      echo "<div class='nova-error'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
      exit;
    }
  }
}

// Load configuration
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

// Serve Nova.js if requested
if (isset($_SERVER['REQUEST_URI'])) {
  $uri = $_SERVER['REQUEST_URI'];

  if ($uri === '/nova.js') {
    header('Content-Type: application/javascript');
    header('Cache-Control: public, max-age=3600');
    $jsFile = __DIR__ . '/public/nova.js';
    if (file_exists($jsFile)) {
      readfile($jsFile);
    } else {
      echo "// Nova.js not found";
    }
    exit;
  }

  if ($uri === '/nova-alpine.js') {
    header('Content-Type: application/javascript');
    header('Cache-Control: public, max-age=3600');
    $jsFile = __DIR__ . '/public/nova-alpine.js';
    if (file_exists($jsFile)) {
      readfile($jsFile);
    } else {
      echo "// Nova Alpine integration not found";
    }
    exit;
  }
}
