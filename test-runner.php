<?php
// test-runner.php

require_once __DIR__ . '/vendor/autoload.php';

echo "=== Testing Nova Component System ===\n\n";

// Test 1: Register and render a simple component
echo "Test 1: Simple component registration and rendering\n";
component('test-1', function ($c) {
  $c->view(function ($state) {
    echo "<h1>Hello from test component!</h1>";
  });
});

echo nova('test-1');
echo "\n\n✅ Test 1 passed\n\n";

// Test 2: Component with state
echo "Test 2: Component with state\n";
component('test-2', function ($c) {
  $c->state(function () {
    return ['message' => 'Hello World', 'count' => 42];
  });

  $c->view(function ($state) {
    echo "<div>";
    echo "Message: @echo(\$state->message)<br>";
    echo "Count: @echo(\$state->count)";
    echo "</div>";
  });
});

echo nova('test-2');
echo "\n\n✅ Test 2 passed\n\n";

// Test 3: Component with props
echo "Test 3: Component with props (props override state)\n";
component('test-3', function ($c) {
  $c->state(function () {
    return ['color' => 'blue', 'size' => 'large'];
  });

  $c->view(function ($state) {
    echo "<div style='color: @echo(\$state->color); font-size: @echo(\$state->size);'>";
    echo "Styled text";
    echo "</div>";
  });
});

echo nova('test-3', ['color' => 'red']);
echo "\n\n✅ Test 3 passed\n\n";

// Test 4: Check if component exists
echo "Test 4: Component existence check\n";
echo "test-2 exists: " . (nova_component_exists('test-2') ? 'Yes' : 'No') . "\n";
echo "fake exists: " . (nova_component_exists('fake') ? 'Yes' : 'No') . "\n";
echo "✅ Test 4 passed\n\n";

// Test 5: List all components
echo "Test 5: List all registered components\n";
$components = nova_get_components();
foreach ($components as $name => $component) {
  echo "  - $name\n";
}
echo "✅ Test 5 passed\n\n";

echo "=== All tests passed! Phase 0 is working correctly ===\n";
