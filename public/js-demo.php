<?php
require_once __DIR__ . '/../bootstrap.php';

// Simple counter component with proper @click handling
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

$instanceId = 'working-counter_' . uniqid();
?>
<!DOCTYPE html>
<html>

<head>
  <title>Working Nova Demo</title>
  <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 600px;
      margin: 50px auto;
      padding: 20px;
      background: #f5f5f5;
    }

    .demo-container {
      background: white;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    h1 {
      color: #333;
      margin-top: 0;
    }

    button {
      transition: transform 0.1s;
    }

    button:active {
      transform: scale(0.95);
    }
  </style>
</head>

<body>
  <div class="demo-container">
    <h1>✨ Nova Working Demo</h1>
    <p>Click the buttons below - they should update the counter without page reload!</p>

    <?php echo nova('working-counter', ['_instance' => $instanceId]); ?>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
      <strong>How it works:</strong><br>
      - @click directives are compiled to data-nova-click attributes<br>
      - Nova.js intercepts clicks and sends AJAX requests<br>
      - Server processes actions and returns updated HTML<br>
      - Component updates without page reload
    </div>
  </div>

  <script src="/nova.js"></script>
</body>

</html>
