<?php

require_once __DIR__ . '/vendor/autoload.php';

session_start();

echo "=== Phase 1: Testing State Persistence ===\n\n";

// Register the component
component('test-state', function ($c) {
  $c->state(function () {
    return ['clicks' => 0];
  });

  $c->actions([
    'click' => function (&$state) {
      $state['clicks']++;
    }
  ]);

  $c->view(function ($state) {
    // Add the instance ID to the state for display
    echo "Clicks: @echo(\$state->clicks)\n";
    echo "Instance: @echo(\$state->_instance ?? 'unknown')\n";
  });
});

// Create two instances
echo "Instance 1:\n";
$instance1 = nova('test-state', ['_instance' => 'instance-1']);
echo $instance1;
echo "\n";

echo "Instance 2:\n";
$instance2 = nova('test-state', ['_instance' => 'instance-2']);
echo $instance2;
echo "\n";

// Call action on instance 1
echo "Calling click action on instance 1...\n";
$output = nova_action('test-state', 'instance-1', 'click');
echo $output;
echo "\n";

// Render again to show persisted state
echo "Instance 1 after click:\n";
echo nova('test-state', ['_instance' => 'instance-1']);
echo "\n";

echo "Instance 2 (should still be 0):\n";
echo nova('test-state', ['_instance' => 'instance-2']);
echo "\n";
