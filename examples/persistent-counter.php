<?php

if (php_sapi_name() === 'cli') {
  require_once __DIR__ . '/../vendor/autoload.php';
}

component('persistent-counter', function ($component) {
  $component->state(function () {
    // Default state (only used first time)
    return [
      'count' => 0,
      'lastAction' => 'none'
    ];
  });

  $component->actions([
    'increment' => function (&$state) {
      $state['count']++;
      $state['lastAction'] = 'incremented';
    },
    'decrement' => function (&$state) {
      $state['count']--;
      $state['lastAction'] = 'decremented';
    },
    'reset' => function (&$state) {
      $state['count'] = 0;
      $state['lastAction'] = 'reset';
    }
  ]);

  $component->view(function ($state) {
?>
    <div style="text-align: center; padding: 20px; font-family: sans-serif; border: 1px solid #ccc; border-radius: 8px; max-width: 400px; margin: 20px auto;">
      <h2>Persistent Counter</h2>

      <div style="font-size: 72px; margin: 20px; font-weight: bold;">
        @echo($state->count)
      </div>

      <div style="margin-bottom: 10px; color: #666;">
        Last action: @echo($state->lastAction)
      </div>

      <div>
        <form method="POST" style="display: inline;">
          <input type="hidden" name="_component" value="persistent-counter">
          <input type="hidden" name="_instance" value="@echo($state->_instance)">
          <button type="submit" name="_action" value="decrement" style="padding: 10px 20px; margin: 5px; font-size: 18px;">-</button>
        </form>
        <form method="POST" style="display: inline;">
          <input type="hidden" name="_component" value="persistent-counter">
          <input type="hidden" name="_instance" value="@echo($state->_instance)">
          <button type="submit" name="_action" value="reset" style="padding: 10px 20px; margin: 5px; font-size: 18px;">Reset</button>
        </form>
        <form method="POST" style="display: inline;">
          <input type="hidden" name="_component" value="persistent-counter">
          <input type="hidden" name="_instance" value="@echo($state->_instance)">
          <button type="submit" name="_action" value="increment" style="padding: 10px 20px; margin: 5px; font-size: 18px;">+</button>
        </form>
      </div>

      <div style="margin-top: 10px; font-size: 12px; color: #999;">
        Instance ID: @echo($state->_instance)
      </div>
    </div>
<?php
  });
});

// Handle action requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
  $componentName = $_POST['_component'] ?? '';
  $instanceId = $_POST['_instance'] ?? '';
  $action = $_POST['_action'];

  if ($componentName && $instanceId && $action) {
    echo nova_action($componentName, $instanceId, $action);
    exit;
  }
}

// Normal render
echo nova('persistent-counter', ['_instance' => session_id() . '_counter']);
