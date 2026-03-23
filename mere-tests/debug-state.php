<?php

require_once __DIR__ . '/../bootstrap.php';

// Simple component to test state
component('debug-state', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Test Title',
      'count' => 42,
      'items' => ['one', 'two', 'three']
    ];
  });

  $c->view(function ($state) {
    echo "State object: ";
    var_dump($state);
    echo "\n\n";

    echo "Title: " . $state->title . "\n";
    echo "Count: " . $state->count . "\n";
    echo "Items: " . implode(', ', $state->items) . "\n";
  });
});

// Render and see what happens
echo "=== Debug State Test ===\n";
$output = nova('debug-state');
echo $output;
echo "\n=== End Test ===\n";
