<?php

require_once __DIR__ . '/../bootstrap.php';

use Luxid\Nova\Slot;

// Simple card component using PHP functions
component('simple-card', function ($c) {
  $c->state(function () {
    return ['title' => 'Default Card Title'];
  });

  $c->view(function ($state) {
?>
    <div style="border: 1px solid #ccc; padding: 20px; margin: 10px; border-radius: 5px; background: white;">
      <h2 style="margin-top: 0; color: #333;"><?php echo htmlspecialchars($state->title); ?></h2>

      <div class="content" style="margin: 15px 0;">
        <?php echo Slot::render('content', '<p style="color: #666;">Default content</p>'); ?>
      </div>

      <div class="footer" style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #eee; font-size: 12px; color: #999;">
        <?php echo Slot::render('footer', '<small>Card footer</small>'); ?>
      </div>
    </div>
  <?php
  });
});

// Simple button component
component('simple-button', function ($c) {
  $c->state(function () {
    return ['label' => 'Click Me'];
  });

  $c->view(function ($state) {
  ?>
    <button style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px;">
      <?php echo htmlspecialchars($state->label); ?>
    </button>
  <?php
  });
});

// Main component using nesting
component('main-page', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Nova Slot Demo',
      'showMessage' => true
    ];
  });

  $c->view(function ($state) use ($c) {
  ?>
    <!DOCTYPE html>
    <html>

    <head>
      <title><?php echo htmlspecialchars($state->title); ?></title>
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

        .demo {
          margin: 20px 0;
        }

        h1 {
          color: #333;
          border-bottom: 2px solid #4CAF50;
          padding-bottom: 10px;
        }

        .info {
          background: #e3f2fd;
          padding: 15px;
          border-radius: 5px;
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
      <h1><?php echo htmlspecialchars($state->title); ?></h1>

      <div class="info">
        <strong>✨ How Slots Work:</strong>
        <ul style="margin-top: 10px; margin-left: 20px;">
          <li>Use <code>Slot::start('name')</code> to begin capturing content</li>
          <li>Use <code>Slot::end()</code> to stop capturing</li>
          <li>Use <code>Slot::render('name')</code> to display slot content</li>
          <li>Components can have multiple named slots</li>
        </ul>
      </div>

      <div class="demo">
        <h2>Card with Custom Content</h2>
        <?php
        // Start capturing content for the 'content' slot
        Slot::start('content');
        ?>
        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px;">
          <p>✨ This content is injected from the parent component!</p>
          <p>It can contain any HTML or even other components.</p>

          <div style="margin-top: 10px;">
            <?php
            // Render a button inside the slot
            echo $c->renderComponent('simple-button', ['label' => 'Click Me!']);
            ?>
          </div>
        </div>
        <?php
        Slot::end();

        // Start capturing content for the 'footer' slot
        Slot::start('footer');
        ?>
        <button onclick="alert('Custom footer button clicked!')"
          style="background: #ff9800; color: white; padding: 5px 15px; border: none; border-radius: 3px; cursor: pointer;">
          Custom Footer Action
        </button>
        <?php
        Slot::end();

        // Render the card with our custom slots
        echo $c->renderComponent('simple-card', ['title' => 'Custom Card with Slots']);
        ?>
      </div>

      <div class="demo">
        <h2>Default Card (No Custom Slots)</h2>
        <?php
        // Clear slots to ensure we don't accidentally use previous content
        Slot::clear();
        echo $c->renderComponent('simple-card', ['title' => 'Default Card']);
        ?>
      </div>

      <div class="demo">
        <h2>Standalone Button</h2>
        <?php echo $c->renderComponent('simple-button', ['label' => 'Standalone Button']); ?>
      </div>

      <div class="info" style="background: #f0f0f0;">
        <strong>🔍 Debug Info:</strong><br>
        Component: main-page<br>
        Instance ID: <?php echo $state->_instance; ?><br>
        State: <?php echo json_encode(get_object_vars($state)); ?>
      </div>
    </body>

    </html>
<?php
  });
});

// Render the main page
$instanceId = 'main_' . uniqid();
echo nova('main-page', ['_instance' => $instanceId]);
