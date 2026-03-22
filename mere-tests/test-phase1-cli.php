<?php

require_once __DIR__ . '/vendor/autoload.php';

use Luxid\Nova\ComponentManager;

// Start session for state persistence
session_start();

echo "=== Phase 1: Testing State Persistence ===\n\n";

// Register a simple component
component('test-persistence', function ($c) {
  $c->state(function () {
    return [
      'counter' => 0,
      'message' => 'Initial state'
    ];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['counter']++;
      $state['message'] = "Counter incremented to {$state['counter']}";
    },
    'decrement' => function (&$state) {
      $state['counter']--;
      $state['message'] = "Counter decremented to {$state['counter']}";
    },
    'set_message' => function (&$state, $newMessage) {
      $state['message'] = $newMessage;
    }
  ]);

  $c->view(function ($state) {
    // For CLI testing, we'll output plain text without directives
    echo "Counter: " . $state->counter . "\n";
    echo "Message: " . $state->message . "\n";
    echo "Instance: " . ($state->_instance ?? 'unknown') . "\n";
    echo "---\n";
  });
});

// Test 1: Create two instances with different IDs
echo "Test 1: Creating two separate instances\n";
$instance1_id = 'instance-1';
$instance2_id = 'instance-2';

echo "Instance 1 (ID: $instance1_id):\n";
$output1 = nova('test-persistence', ['_instance' => $instance1_id]);
echo $output1;

echo "\nInstance 2 (ID: $instance2_id):\n";
$output2 = nova('test-persistence', ['_instance' => $instance2_id]);
echo $output2;

// Test 2: Increment instance 1
echo "\nTest 2: Incrementing instance 1\n";
echo "Calling increment action...\n";
$result = nova_action('test-persistence', $instance1_id, 'increment');
echo $result;

echo "\nAfter increment:\n";
echo nova('test-persistence', ['_instance' => $instance1_id]);

// Test 3: Verify instance 2 remains unchanged
echo "\nTest 3: Verify instance 2 unaffected\n";
echo "Instance 2 state (should still be 0):\n";
echo nova('test-persistence', ['_instance' => $instance2_id]);

// Test 4: Decrement instance 1
echo "\nTest 4: Decrementing instance 1\n";
echo "Calling decrement action...\n";
$result = nova_action('test-persistence', $instance1_id, 'decrement');
echo $result;

echo "\nFinal state for instance 1:\n";
echo nova('test-persistence', ['_instance' => $instance1_id]);

// Test 5: Action with parameters
echo "\nTest 5: Action with parameters\n";
echo "Calling set_message action...\n";
$result = nova_action('test-persistence', $instance1_id, 'set_message', ['Custom message!']);
echo $result;

echo "\nFinal state with custom message:\n";
echo nova('test-persistence', ['_instance' => $instance1_id]);

// Test 6: Persistence across "page reloads"
echo "\nTest 6: Simulating page reload\n";
echo "Clearing local component cache and re-rendering...\n";

// Clear the component from memory (but state remains in session)
ComponentManager::clear();

// Re-register the component
component('test-persistence', function ($c) {
  $c->state(function () {
    return [
      'counter' => 0,
      'message' => 'Initial state'
    ];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['counter']++;
      $state['message'] = "Counter incremented to {$state['counter']}";
    },
    'decrement' => function (&$state) {
      $state['counter']--;
      $state['message'] = "Counter decremented to {$state['counter']}";
    },
    'set_message' => function (&$state, $newMessage) {
      $state['message'] = $newMessage;
    }
  ]);

  $c->view(function ($state) {
    echo "Counter: " . $state->counter . "\n";
    echo "Message: " . $state->message . "\n";
    echo "Instance: " . ($state->_instance ?? 'unknown') . "\n";
    echo "---\n";
  });
});

echo "After 'page reload' (state should be preserved):\n";
echo nova('test-persistence', ['_instance' => $instance1_id]);

echo "\n✅ All tests completed!\n";
