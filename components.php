<?php
// components.php - Shared component definitions

// Register components that will be used across requests
component('test-counter', function ($c) {
  $c->state(function () {
    return ['count' => 0];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
      return $state['count'];
    },
    'decrement' => function (&$state) {
      $state['count']--;
      return $state['count'];
    }
  ]);

  $c->view(function ($state) {
?>
    <div style="padding: 20px; border: 1px solid #ccc; text-align: center;">
      <h2>Test Counter</h2>
      <div style="font-size: 48px; margin: 20px;">@echo($state->count)</div>
      <button @click="increment" style="padding: 10px 20px;">+</button>
      <button @click="decrement" style="padding: 10px 20px;">-</button>
    </div>
  <?php
  });
});

// Register working counter
component('working-counter', function ($c) {
  $c->state(function () {
    return ['count' => 0];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
    },
    'decrement' => function (&$state) {
      $state['count']--;
    }
  ]);

  $c->view(function ($state) {
  ?>
    <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
      <div style="font-size: 72px; font-weight: bold; margin: 20px 0;">
        @echo($state->count)
      </div>

      <div style="display: flex; gap: 15px; justify-content: center;">
        <button @click="decrement" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          ➖ Decrement
        </button>
        <button @click="increment" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          ➕ Increment
        </button>
      </div>

      <div style="margin-top: 20px; font-size: 12px; opacity: 0.7;">
        Instance: @echo($state->_instance)
      </div>
    </div>
<?php
  });
});
