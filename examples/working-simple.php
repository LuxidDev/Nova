<?php

require_once __DIR__ . '/../bootstrap.php';

// Simple component with state
component('test-component', function ($c) {
  $c->state(function () {
    return [
      'name' => 'Luxid Nova',
      'version' => '0.2.0',
      'features' => ['Components', 'Slots', 'State']
    ];
  });

  $c->view(function ($state) {
?>
    <div style="padding: 20px; border: 1px solid #4CAF50; border-radius: 5px; margin: 10px;">
      <h2><?php echo htmlspecialchars($state->name); ?></h2>
      <p>Version: <?php echo htmlspecialchars($state->version); ?></p>
      <ul>
        <?php foreach ($state->features as $feature): ?>
          <li><?php echo htmlspecialchars($feature); ?></li>
        <?php endforeach; ?>
      </ul>
      <p>Instance ID: <?php echo htmlspecialchars($state->_instance ?? 'unknown'); ?></p>
    </div>
<?php
  });
});

echo "<!DOCTYPE html><html><body>";
echo "<h1>Working Component Test</h1>";
echo nova('test-component');
echo "</body></html>";
