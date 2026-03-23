<?php

require_once __DIR__ . '/../bootstrap.php';

component('state-test', function ($c) {
  $c->state(function () {
    return [
      'message' => 'Hello World!',
      'count' => 42
    ];
  });

  $c->view(function ($state) {
    // Direct PHP output - no directives
    echo "<div style='padding: 20px; border: 1px solid blue;'>";
    echo "<h2>State Test</h2>";
    echo "<p>Message: " . $state->message . "</p>";
    echo "<p>Count: " . $state->count . "</p>";
    echo "<p>Instance: " . ($state->_instance ?? 'unknown') . "</p>";
    echo "</div>";
  });
});

// Test with directive
component('directive-test', function ($c) {
  $c->state(function () {
    return [
      'name' => 'Directive Test',
      'items' => ['Item 1', 'Item 2', 'Item 3']
    ];
  });

  $c->view(function ($state) {
?>
    <div style="padding: 20px; border: 1px solid green; margin-top: 20px;">
      <h2>@echo($state->name)</h2>
      <ul>
        @foreach($state->items as $item)
        <li>@echo($item)</li>
        @endforeach
      </ul>
    </div>
<?php
  });
});

echo "<!DOCTYPE html><html><body>";
echo "<h1>Simple State Test</h1>";
echo nova('state-test');
echo nova('directive-test');
echo "</body></html>";
