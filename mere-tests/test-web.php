<?php

require_once __DIR__ . '/vendor/autoload.php';

session_start();

// IMPORTANT: Register the component BEFORE handling any requests
component('persistent-counter', function ($c) {
  $c->state(function () {
    return [
      'count' => 0,
      'history' => [],
      'last_action' => 'none'
    ];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
      $state['last_action'] = 'incremented';
      $state['history'][] = ['action' => 'increment', 'time' => date('H:i:s'), 'count' => $state['count']];
      $state['history'] = array_slice($state['history'], -5);
    },
    'decrement' => function (&$state) {
      $state['count']--;
      $state['last_action'] = 'decremented';
      $state['history'][] = ['action' => 'decrement', 'time' => date('H:i:s'), 'count' => $state['count']];
      $state['history'] = array_slice($state['history'], -5);
    },
    'reset' => function (&$state) {
      $state['count'] = 0;
      $state['last_action'] = 'reset';
      $state['history'][] = ['action' => 'reset', 'time' => date('H:i:s'), 'count' => 0];
      $state['history'] = array_slice($state['history'], -5);
    },
    'set_count' => function (&$state, $newCount) {
      $state['count'] = (int)$newCount;
      $state['last_action'] = "set to {$newCount}";
      $state['history'][] = ['action' => "set to {$newCount}", 'time' => date('H:i:s'), 'count' => $state['count']];
      $state['history'] = array_slice($state['history'], -5);
    }
  ]);

  $c->view(function ($state) {
?>
    <div style="background: white; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
      <h3 style="margin-top: 0;">Persistent Counter</h3>

      <div style="font-size: 48px; text-align: center; margin: 20px 0; font-weight: bold; color: #4CAF50;">
        <?php echo $state->count; ?>
      </div>

      <div style="text-align: center; margin: 10px 0; color: #666;">
        Last action: <strong><?php echo htmlspecialchars($state->last_action); ?></strong>
      </div>

      <div style="display: flex; gap: 10px; justify-content: center; margin: 20px 0;">
        <button onclick="callAction('decrement')" style="padding: 10px 20px; background: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer;">-</button>
        <button onclick="callAction('reset')" style="padding: 10px 20px; background: #ff9800; color: white; border: none; border-radius: 4px; cursor: pointer;">Reset</button>
        <button onclick="callAction('increment')" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">+</button>
      </div>

      <div style="margin: 20px 0;">
        <input type="number" id="set-value" placeholder="Set specific value" style="padding: 8px; width: 150px; margin-right: 10px;">
        <button onclick="callActionWithValue('set_count')" style="padding: 8px 15px; background: #2196F3; color: white; border: none; border-radius: 4px; cursor: pointer;">Set</button>
      </div>

      <?php if (!empty($state->history)): ?>
        <div style="margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
          <strong>History (last 5 actions):</strong>
          <ul style="margin: 10px 0 0 20px; padding: 0;">
            <?php foreach ($state->history as $entry): ?>
              <li style="margin: 5px 0;">
                <span style="color: #999;"><?php echo htmlspecialchars($entry['time']); ?></span> -
                Action: <?php echo htmlspecialchars($entry['action']); ?>
                (Count: <?php echo $entry['count']; ?>)
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <div style="margin-top: 15px; font-size: 11px; color: #999; border-top: 1px solid #eee; padding-top: 10px;">
        Instance ID: <?php echo htmlspecialchars($state->_instance ?? 'unknown'); ?>
      </div>
    </div>

    <script>
      function callAction(action) {
        var instance = '<?php echo $state->_instance; ?>';
        var xhr = new XMLHttpRequest();
        xhr.open('POST', window.location.href, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function() {
          if (xhr.status === 200) {
            try {
              var response = JSON.parse(xhr.responseText);
              if (response.success) {
                document.getElementById('component-container').innerHTML = response.html;
                // Re-attach event listeners (the buttons are recreated)
                attachEventListeners();
              } else {
                alert('Error: ' + response.error);
              }
            } catch (e) {
              console.error('Parse error:', e);
              alert('Error processing response');
            }
          }
        };

        var params = 'action=' + encodeURIComponent(action) +
          '&component=persistent-counter' +
          '&instance=' + encodeURIComponent(instance);
        xhr.send(params);
      }

      function callActionWithValue(action) {
        var value = document.getElementById('set-value').value;
        if (value !== '') {
          var instance = '<?php echo $state->_instance; ?>';
          var xhr = new XMLHttpRequest();
          xhr.open('POST', window.location.href, true);
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
          xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

          xhr.onload = function() {
            if (xhr.status === 200) {
              try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                  document.getElementById('component-container').innerHTML = response.html;
                  attachEventListeners();
                } else {
                  alert('Error: ' + response.error);
                }
              } catch (e) {
                console.error('Parse error:', e);
                alert('Error processing response');
              }
            }
          };

          var params = 'action=' + encodeURIComponent(action) +
            '&component=persistent-counter' +
            '&instance=' + encodeURIComponent(instance) +
            '&value=' + encodeURIComponent(value);
          xhr.send(params);
        }
      }

      function attachEventListeners() {
        // Event listeners are reattached via the onclick attributes
        // No need for additional code since the buttons have inline onclick
      }
    </script>
<?php
  });
});

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
  header('Content-Type: application/json');

  $component = $_POST['component'] ?? '';
  $instance = $_POST['instance'] ?? '';
  $action = $_POST['action'] ?? '';
  $value = $_POST['value'] ?? null;

  if ($component && $instance && $action) {
    try {
      $params = [];
      if ($value !== null) {
        $params = [$value];
      }
      $html = nova_action($component, $instance, $action, $params);
      echo json_encode(['success' => true, 'html' => $html]);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
  } else {
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
  }
  exit;
}

// Generate a unique instance for this session
$instanceId = 'session_' . session_id();

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Luxid Nova - Phase 1 Demo</title>
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
      background: #f5f5f5;
    }

    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
    }

    .info {
      background: #e3f2fd;
      padding: 15px;
      border-radius: 8px;
      margin: 20px 0;
      border-left: 4px solid #2196F3;
    }

    .feature-list {
      margin: 10px 0;
      padding-left: 20px;
    }

    .feature-list li {
      margin: 5px 0;
    }

    code {
      background: #f4f4f4;
      padding: 2px 5px;
      border-radius: 3px;
      font-family: monospace;
    }

    button {
      transition: transform 0.2s;
    }

    button:active {
      transform: scale(0.95);
    }

    .error {
      background: #ffebee;
      color: #c62828;
      padding: 10px;
      border-radius: 4px;
      margin: 10px 0;
      border-left: 4px solid #c62828;
    }
  </style>
</head>

<body>
  <div class="header">
    <h1>✨ Luxid Nova - Phase 1</h1>
    <p>State Persistence with Session Storage</p>
  </div>

  <div class="info">
    <strong>✅ What's working in Phase 1:</strong>
    <ul class="feature-list">
      <li>✓ Component state stored in PHP sessions</li>
      <li>✓ Multiple independent component instances</li>
      <li>✓ Actions that mutate state and trigger re-rendering</li>
      <li>✓ AJAX-based interactions without page reload</li>
      <li>✓ State persists across page refreshes</li>
    </ul>
  </div>

  <div id="component-container">
    <?php echo nova('persistent-counter', ['_instance' => $instanceId]); ?>
  </div>

  <div class="info" style="margin-top: 20px;">
    <strong>🧪 Try these tests:</strong>
    <ol>
      <li>Click the buttons and watch the counter change</li>
      <li>Refresh the page - counter value persists!</li>
      <li>Open this page in another browser tab - you'll get a separate counter instance</li>
      <li>Set a specific value using the input field</li>
      <li>Check the history - it tracks your last 5 actions</li>
    </ol>
  </div>

  <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #999; font-size: 12px;">
    Luxid Nova - Building reactive server-side components for PHP
  </div>
</body>

</html>
