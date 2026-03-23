<?php

require_once __DIR__ . '/../bootstrap.php';

use Luxid\Nova\Slot;

// Layout component with slots
component('layout', function ($c) {
  $c->state(function () {
    return ['title' => 'Layout Title'];
  });

  $c->view(function ($state) {
?>
    <div style="border: 2px solid #333; padding: 20px; margin: 10px; border-radius: 5px;">
      <h1 style="margin-top: 0;">@echo($state->title)</h1>

      <div class="content" style="margin: 15px 0;">
        @slot('content')
        <p>Default content</p>
        @endslot
      </div>

      <div class="footer" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
        @slot('footer')
        <small>Layout footer</small>
        @endslot
      </div>
    </div>
  <?php
  });
});

// Button component
component('button', function ($c) {
  $c->state(function () {
    return ['label' => 'Click Me'];
  });

  $c->actions([
    'click' => function (&$state) {
      $state['clicked'] = true;
    }
  ]);

  $c->view(function ($state) {
  ?>
    <button onclick="callAction('click')"
      style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
      @echo($state->label)
    </button>
  <?php
  });
});

// Card component with slots
component('card', function ($c) {
  $c->state(function () {
    return ['title' => 'Card Title'];
  });

  $c->view(function ($state) {
  ?>
    <div style="border: 1px solid #ddd; padding: 15px; margin: 10px; border-radius: 8px; background: white;">
      <h3 style="margin-top: 0;">@echo($state->title)</h3>

      <div class="card-body">
        @slot('body')
        <p>Default body content</p>
        @endslot
      </div>
    </div>
  <?php
  });
});

// Main page using directives
component('home', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Nova with Directives!',
      'showCard' => true,
      'items' => ['Item 1', 'Item 2', 'Item 3']
    ];
  });

  $c->view(function ($state) use ($c) {
  ?>
    <!DOCTYPE html>
    <html>

    <head>
      <title>@echo($state->title)</title>
      <style>
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
          max-width: 800px;
          margin: 0 auto;
          padding: 20px;
          background: #f5f5f5;
        }

        .demo-section {
          margin: 20px 0;
        }

        button {
          margin: 5px;
        }
      </style>
    </head>

    <body>
      <h1>@echo($state->title)</h1>

      <div class="demo-section">
        <h2>Layout with Slots</h2>
        @component('layout', ['title' => 'Custom Layout Title'])
        @slot('content')
        <p>This content is injected via <code>@slot</code> directive!</p>
        <p>It can contain any HTML or even other components:</p>
        @component('button', ['label' => 'Injected Button'])
        @endslot

        @slot('footer')
        <strong>✨ Custom Footer</strong> - This overrides the default footer
        @endslot
        @endcomponent
      </div>

      <div class="demo-section">
        <h2>Card with Slots</h2>
        @component('card', ['title' => 'Custom Card'])
        @slot('body')
        <ul>
          @foreach($state->items as $item)
          <li>@echo($item)</li>
          @endforeach
        </ul>
        <p>This card content is injected via slots!</p>
        @endslot
        @endcomponent
      </div>

      <div class="demo-section">
        <h2>Conditional Rendering</h2>
        @if($state->showCard)
        @component('card', ['title' => 'Conditional Card'])
        @slot('body')
        <p>This card only shows when <code>showCard</code> is true!</p>
        <p>Current value: @echo($state->showCard ? 'true' : 'false')</p>
        @endslot
        @endcomponent
        @endif
      </div>

      <div class="demo-section">
        <h2>Direct Button Component</h2>
        @component('button', ['label' => 'Standalone Button'])
      </div>

      <div class="demo-section" style="background: #e3f2fd; padding: 15px; border-radius: 5px;">
        <strong>🔍 Debug Info:</strong><br>
        Component: home<br>
        Instance: @echo($state->_instance)<br>
        State:
        <pre style="background: white; padding: 10px; border-radius: 3px; margin-top: 5px;"><?php echo json_encode(get_object_vars($state), JSON_PRETTY_PRINT); ?></pre>
      </div>
    </body>

    </html>
<?php
  });
});

// Render the page
$instanceId = 'home_' . uniqid();
echo nova('home', ['_instance' => $instanceId]);
