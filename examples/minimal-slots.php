<?php

require_once __DIR__ . '/../bootstrap.php';

use Luxid\Nova\Slot;

// A simple layout component with a slot
component('layout', function ($c) {
  $c->state(function () {
    return ['title' => 'Layout Title'];
  });

  $c->view(function ($state) {
?>
    <div style="border: 2px solid #333; padding: 20px; margin: 10px;">
      <h1><?php echo htmlspecialchars($state->title); ?></h1>
      <div class="content">
        <?php echo Slot::render('main', '<p>Default content</p>'); ?>
      </div>
      <div class="footer">
        <?php echo Slot::render('footer', '<small>Layout footer</small>'); ?>
      </div>
    </div>
  <?php
  });
});

// Page component that uses the layout
component('page', function ($c) {
  $c->state(function () {
    return ['pageTitle' => 'My Page'];
  });

  $c->view(function ($state) use ($c) {
    // Set up slots
    Slot::start('main');
  ?>
    <h2>Welcome to <?php echo htmlspecialchars($state->pageTitle); ?></h2>
    <p>This content is injected into the layout's main slot.</p>
    <p>You can put any HTML here!</p>
    <?php
    Slot::end();

    Slot::start('footer');
    ?>
    <strong>Custom Footer</strong> - Page rendered at <?php echo date('H:i:s'); ?>
<?php
    Slot::end();

    // Render the layout with slots
    echo $c->renderComponent('layout', ['title' => $state->pageTitle]);
  });
});

// Render the page
echo "<!DOCTYPE html><html><body>";
echo "<h1>Minimal Slots Demo</h1>";
echo nova('page', ['_instance' => 'page_' . uniqid()]);
echo "</body></html>";
