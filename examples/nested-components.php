<?php

require_once __DIR__ . '/../bootstrap.php';

// Register card component
component('card', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Welcome Card',
      'variant' => 'primary'
    ];
  });

  $c->view(function ($state) {
?>
    <div style="border: 2px solid #4CAF50; padding: 20px; margin: 10px; border-radius: 8px; background: #f9f9f9;">
      <h2 style="margin-top: 0; color: #4CAF50;">@echo($state->title)</h2>

      <div class="content" style="margin: 15px 0;">
        @slot('content')
        <p>Default content. Override me!</p>
        @endslot
      </div>

      <div class="footer" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
        @slot('footer')
        <small>Card footer</small>
        @endslot
      </div>
    </div>
  <?php
  });
});

// Register todo list component
component('todo-list', function ($c) {
  $c->state(function () {
    return [
      'items' => [
        ['text' => 'Learn Nova', 'completed' => true],
        ['text' => 'Build nested components', 'completed' => false],
        ['text' => 'Create slot system', 'completed' => false]
      ],
      'newTodo' => ''
    ];
  });

  $c->actions([
    'addTodo' => function (&$state) {
      if (!empty($state['newTodo'])) {
        $state['items'][] = [
          'text' => $state['newTodo'],
          'completed' => false
        ];
        $state['newTodo'] = '';
      }
    },
    'toggleTodo' => function (&$state, $index) {
      $state['items'][$index]['completed'] = !$state['items'][$index]['completed'];
    },
    'updateNewTodo' => function (&$state, $value) {
      $state['newTodo'] = $value;
    }
  ]);

  $c->view(function ($state) {
  ?>
    <div style="font-family: sans-serif;">
      <h3>Todo List</h3>

      <ul style="list-style: none; padding: 0;">
        @foreach($state->items as $index => $item)
        <li style="margin: 10px 0; display: flex; align-items: center;">
          <input type="checkbox"
            @click="toggleTodo(@echo($index))"
            @if($item['completed']) checked @endif
            style="margin-right: 10px;">
          <span style="@if($item['completed']) text-decoration: line-through; color: #999; @endif">
            @echo($item['text'])
          </span>
        </li>
        @endforeach
      </ul>

      <div style="margin-top: 15px; display: flex; gap: 10px;">
        <input type="text"
          value="@echo($state->newTodo)"
          @input="updateNewTodo($event.target.value)"
          placeholder="New todo..."
          style="flex: 1; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        <button @click="addTodo"
          style="padding: 8px 15px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
          Add
        </button>
      </div>
    </div>
  <?php
  });
});

// Main page component that uses nested components
component('dashboard', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Nova Dashboard',
      'showTodo' => true
    ];
  });

  $c->actions([
    'toggleTodo' => function (&$state) {
      $state['showTodo'] = !$state['showTodo'];
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
          font-family: sans-serif;
          max-width: 1200px;
          margin: 0 auto;
          padding: 20px;
          background: #f5f5f5;
        }

        .dashboard {
          display: grid;
          grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
          gap: 20px;
        }
      </style>
    </head>

    <body>
      <h1>@echo($state->title)</h1>

      <div class="dashboard">
        <!-- Card with custom content using slots -->
        @component('card', ['title' => 'Welcome Message', 'variant' => 'primary'])
        @slot('content')
        <p>This content is injected from the parent component!</p>
        <p>You can put any HTML or even other components here.</p>

        <!-- Nested component inside slot -->
        @component('button', ['label' => 'Click Me', 'variant' => 'primary'])
        @endslot

        @slot('footer')
        <button @click="toggleTodo" style="padding: 5px 10px;">
          @if($state->showTodo) Hide @else Show @endif Todo List
        </button>
        @endslot
        @endcomponent

        <!-- Conditional rendering of todo list -->
        @if($state->showTodo)
        @component('todo-list')
        @endif
      </div>

      <div style="margin-top: 30px; padding: 15px; background: #e3f2fd; border-radius: 5px;">
        <h3>Component Instance Info</h3>
        <p>Dashboard Instance: @echo($state->_instance)</p>
      </div>
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
  $params = $_POST['params'] ?? [];

  if ($component && $instance && $action) {
    try {
      if (is_string($params)) {
        $params = json_decode($params, true) ?: [];
      }
      $html = nova_action($component, $instance, $action, $params);
      echo json_encode(['success' => true, 'html' => $html]);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
  }
  exit;
}

// Render the dashboard
$instanceId = 'dashboard_' . uniqid();
?>
<!DOCTYPE html>
<html>

<head>
  <title>Nova Components Demo</title>
</head>

<body>
  <?php echo nova('dashboard', ['_instance' => $instanceId]); ?>
</body>

</html>
