<?php

// Always include autoloader when this file is executed directly
if (php_sapi_name() === 'cli' && !defined('PHPUNIT_RUNNING')) {
  require_once __DIR__ . '/../vendor/autoload.php';
}

/*
 * Simple Counter Component
 *
 * This demonstrates the basic structure of a Nova component:
 * - State: holds the current count
 * - Actions: increment and decrement functions
 * - View: renders the counter UI
 */

component('simple-counter', function ($component) {
  // Define the component's state
  $component->state(function () {
    // In a real implementation, state would come from session
    // For now, just return initial state
    return [
      'count' => 0,
      'message' => 'Welcome to Nova!'
    ];
  });

  // Define the component's actions
  $component->actions([
    'increment' => function (&$state) {
      $state['count']++;
      // In Phase 2, this would trigger a re-render
    },
    'decrement' => function (&$state) {
      $state['count']--;
    },
    'reset' => function (&$state) {
      $state['count'] = 0;
    }
  ]);

  // Define the component's view
  $component->view(function ($state) {
?>
    <div class="counter" style="text-align: center; padding: 20px; font-family: sans-serif;">
      <h2>Simple Counter Example</h2>
      <p>@echo($state->message)</p>

      <div style="font-size: 48px; margin: 20px;">
        Count: @echo($state->count)
      </div>

      <div>
        <button @click="decrement" style="padding: 10px 20px; margin: 5px; cursor: pointer;">
          -
        </button>
        <button @click="reset" style="padding: 10px 20px; margin: 5px; cursor: pointer;">
          Reset
        </button>
        <button @click="increment" style="padding: 10px 20px; margin: 5px; cursor: pointer;">
          +
        </button>
      </div>

      <div style="margin-top: 20px; font-size: 12px; color: #666;">
        Note: Actions don't work yet (Phase 2)
      </div>
    </div>
<?php
  });
});

// Test rendering
if (php_sapi_name() === 'cli') {
  echo "=== Testing Simple Counter Component ===\n\n";
  echo nova('simple-counter');
  echo "\n\n✅ Component rendered successfully!\n";
}
