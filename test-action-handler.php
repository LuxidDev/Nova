<?php
require_once dirname(__DIR__) . '/bootstrap.php';

// This file will handle AJAX requests for testing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $input = json_decode(file_get_contents('php://input'), true);

  if ($input && isset($input['component']) && isset($input['action'])) {
    $instanceId = $input['component'];
    $action = $input['action'];

    // Extract component name from instance ID
    $lastUnderscore = strrpos($instanceId, '_');
    if ($lastUnderscore !== false) {
      $componentName = substr($instanceId, 0, $lastUnderscore);
    } else {
      $componentName = $instanceId;
    }

    echo "Component: $componentName\n";
    echo "Instance: $instanceId\n";
    echo "Action: $action\n";

    try {
      $result = nova_action($componentName, $instanceId, $action, []);
      echo "Result: " . $result;
    } catch (Exception $e) {
      echo "Error: " . $e->getMessage();
    }
    exit;
  }
}

// Register component
component('test-counter', function ($c) {
  $c->state(fn() => ['count' => 0]);
  $c->actions(['increment' => fn(&$s) => $s['count']++]);
  $c->view(fn($s) => "<div>Count: {$s->count}</div>");
});

$instanceId = 'test-counter_' . uniqid();
echo nova('test-counter', ['_instance' => $instanceId]);
