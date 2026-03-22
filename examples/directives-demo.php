<?php

if (php_sapi_name() === 'cli') {
  require_once __DIR__ . '/../vendor/autoload.php';
}

component('directives-demo', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Luxid Nova Directives Demo',
      'showBadge' => true,
      'version' => 'v0.2.0',
      'items' => [
        ['name' => '@echo directive', 'description' => 'Safe HTML output', 'done' => true],
        ['name' => '@if statements', 'description' => 'Conditional rendering', 'done' => true],
        ['name' => '@foreach loops', 'description' => 'Iterate over arrays', 'done' => true],
        ['name' => '@for loops', 'description' => 'Traditional loops', 'done' => false],
        ['name' => '@php blocks', 'description' => 'Custom PHP code', 'done' => false],
      ],
      'user' => [
        'name' => 'Luxid Developer',
        'loggedIn' => true
      ],
      'counter' => 0
    ];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['counter']++;
    },
    'decrement' => function (&$state) {
      $state['counter']--;
    },
    'toggleBadge' => function (&$state) {
      $state['showBadge'] = !$state['showBadge'];
    }
  ]);

  $c->view(function ($state) {
?>
    <!DOCTYPE html>
    <html>

    <head>
      <title>@echo($state->title)</title>
      <style>
        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          max-width: 800px;
          margin: 0 auto;
          padding: 20px;
          background: #f5f5f5;
        }

        .card {
          background: white;
          border-radius: 8px;
          padding: 20px;
          margin-bottom: 20px;
          box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .badge {
          display: inline-block;
          background: #4CAF50;
          color: white;
          padding: 4px 12px;
          border-radius: 20px;
          font-size: 12px;
          margin-left: 10px;
        }

        .feature-list {
          list-style: none;
          padding: 0;
        }

        .feature-item {
          padding: 10px;
          border-bottom: 1px solid #eee;
          display: flex;
          align-items: center;
        }

        .feature-item.completed {
          text-decoration: line-through;
          color: #999;
        }

        .feature-name {
          font-weight: bold;
          width: 200px;
        }

        .feature-desc {
          flex: 1;
          margin-left: 10px;
        }

        .status {
          width: 60px;
          text-align: center;
        }

        .status.completed {
          color: #4CAF50;
        }

        .counter {
          text-align: center;
          margin: 20px 0;
        }

        .counter-value {
          font-size: 48px;
          font-weight: bold;
          color: #2196F3;
        }

        button {
          padding: 10px 20px;
          margin: 5px;
          border: none;
          border-radius: 4px;
          cursor: pointer;
          font-size: 14px;
        }

        button.primary {
          background: #4CAF50;
          color: white;
        }

        button.secondary {
          background: #ff9800;
          color: white;
        }

        button.danger {
          background: #f44336;
          color: white;
        }

        .info {
          background: #e3f2fd;
          padding: 15px;
          border-radius: 4px;
          margin: 20px 0;
          border-left: 4px solid #2196F3;
        }

        code {
          background: #f4f4f4;
          padding: 2px 5px;
          border-radius: 3px;
          font-family: monospace;
        }
      </style>
    </head>

    <body>
      <div class="card">
        <h1>
          @echo($state->title)
          @if($state->showBadge)
          <span class="badge">@echo($state->version)</span>
          @endif
        </h1>

        <div class="info">
          <strong>✨ Nova Directives Demo</strong><br>
          This component demonstrates all the Nova template directives:
          <code>@echo</code>, <code>@if</code>, <code>@foreach</code>, <code>@for</code>, and <code>@php</code>
        </div>

        @php
        $completedCount = 0;
        foreach ($state->items as $item) {
        if ($item['done']) $completedCount++;
        }
        $totalCount = count($state->items);
        $progress = ($totalCount > 0) ? ($completedCount / $totalCount) * 100 : 0;
        @endphp

        <div style="margin: 20px 0;">
          <strong>Progress:</strong> @echo($completedCount) / @echo($totalCount) items completed
          <div style="background: #e0e0e0; border-radius: 10px; height: 20px; margin-top: 5px;">
            <div style="background: #4CAF50; width: @echo($progress)%; height: 20px; border-radius: 10px; transition: width 0.3s;"></div>
          </div>
        </div>

        <h2>Features (@echo($completedCount) completed)</h2>

        <ul class="feature-list">
          @foreach($state->items as $index => $item)
          <li class="feature-item @if($item['done']) completed @endif">
            <span class="feature-name">@echo($item['name'])</span>
            <span class="feature-desc">@echo($item['description'])</span>
            <span class="status @if($item['done']) completed @endif">
              @if($item['done']) ✓ @else ○ @endif
            </span>
          </li>
          @endforeach
        </ul>

        <div class="counter">
          <h3>Counter Demo</h3>
          <div class="counter-value">@echo($state->counter)</div>
          <div>
            <button class="danger" onclick="callAction('decrement')">-</button>
            <button class="primary" onclick="callAction('increment')">+</button>
            <button class="secondary" onclick="callAction('toggleBadge')">
              @if($state->showBadge) Hide @else Show @endif Badge
            </button>
          </div>
        </div>

        @if($state->user['loggedIn'])
        <div class="info">
          <strong>Welcome back, @echo($state->user['name'])!</strong>
          <br>You're logged in and can interact with the component.
        </div>
        @endif

        <h3>For Loop Example (Numbers 1-5)</h3>
        <div style="display: flex; gap: 10px;">
          @for($i = 1; $i <= 5; $i++)
            <span style="display: inline-block; width: 40px; height: 40px; background: #2196F3; color: white; text-align: center; line-height: 40px; border-radius: 50%;">
            @echo($i)
            </span>
            @endfor
        </div>
      </div>

      <div class="card">
        <h3>Source Code View</h3>
        <details>
          <summary>Click to see the component definition</summary>
          <pre style="background: #f4f4f4; padding: 15px; border-radius: 4px; overflow-x: auto;">
<code>component('directives-demo', function($c) {
    $c->state(function() {
        return [
            'title' => 'Luxid Nova Directives Demo',
            'showBadge' => true,
            'version' => 'v0.2.0',
            'items' => [
                ['name' => '@echo directive', 'description' => 'Safe HTML output', 'done' => true],
                // ... more items
            ],
            'counter' => 0
        ];
    });
    
    $c->actions([
        'increment' => fn(&$state) => $state['counter']++,
        'decrement' => fn(&$state) => $state['counter']--,
        'toggleBadge' => fn(&$state) => $state['showBadge'] = !$state['showBadge']
    ]);
    
    $c->view(function($state) {
        // Beautiful template with @echo, @if, @foreach directives!
    });
});</code>
                    </pre>
        </details>
      </div>

      <script>
        function callAction(action) {
          var xhr = new XMLHttpRequest();
          xhr.open('POST', window.location.href, true);
          xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
          xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

          xhr.onload = function() {
            if (xhr.status === 200) {
              try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                  document.body.innerHTML = response.html;
                }
              } catch (e) {
                console.error('Error:', e);
              }
            }
          };

          var params = 'action=' + encodeURIComponent(action) +
            '&component=directives-demo' +
            '&instance=@echo($state->_instance)';
          xhr.send(params);
        }
      </script>
    </body>

    </html>
  <?php
  });
});

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
  header('Content-Type: application/json');

  $component = $_POST['component'] ?? '';
  $instance = $_POST['instance'] ?? '';
  $action = $_POST['action'] ?? '';

  if ($component && $instance && $action) {
    try {
      $html = nova_action($component, $instance, $action);
      echo json_encode(['success' => true, 'html' => $html]);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
  }
  exit;
}

// Normal rendering
if (php_sapi_name() === 'cli') {
  echo nova('directives-demo');
} else {
  ?>
  <!DOCTYPE html>
  <html>

  <head>
    <title>Luxid Nova - Phase 2 Demo</title>
  </head>

  <body>
    <?php echo nova('directives-demo', ['_instance' => 'demo_' . session_id()]); ?>
  </body>

  </html>
<?php
}
