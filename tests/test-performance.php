<?php

require_once __DIR__ . '/../bootstrap.php';

use Luxid\Nova\Performance;
use Luxid\Nova\Compiler;
use Luxid\Nova\Slot;

// Enable performance monitoring
Performance::enable();
Compiler::enableDebug(true);

echo "=== Nova Performance Benchmark ===\n\n";

// Test 1: Simple component rendering
echo "Test 1: Simple component rendering (100 iterations)\n";
Performance::startTimer('simple_rendering');

for ($i = 0; $i < 100; $i++) {
  // Register component only once
  if ($i === 0) {
    component('bench-simple', function ($c) {
      $c->state(fn() => ['message' => 'Hello World']);
      $c->view(function ($s) {
        echo "<div>" . htmlspecialchars($s->message) . "</div>";
      });
    });
  }

  nova('bench-simple');
}

$result = Performance::endTimer('simple_rendering');
echo "  Time: " . number_format($result['time'], 2) . "ms\n";
echo "  Memory: " . number_format($result['memory'] / 1024, 2) . " KB\n\n";

// Test 2: Component with loops
echo "Test 2: Component with 10 items loop (100 iterations)\n";
Performance::startTimer('loop_rendering');

component('bench-loop', function ($c) {
  $c->state(fn() => ['items' => range(1, 10)]);
  $c->view(function ($s) {
?>
    <ul>
      <?php foreach ($s->items as $item): ?>
        <li><?php echo htmlspecialchars($item); ?></li>
      <?php endforeach; ?>
    </ul>
  <?php
  });
});

for ($i = 0; $i < 100; $i++) {
  nova('bench-loop');
}

$result = Performance::endTimer('loop_rendering');
echo "  Time: " . number_format($result['time'], 2) . "ms\n";
echo "  Memory: " . number_format($result['memory'] / 1024, 2) . " KB\n\n";

// Test 3: Component with slots
echo "Test 3: Component with slots (100 iterations)\n";
Performance::startTimer('slot_rendering');

component('bench-layout', function ($c) {
  $c->view(function ($s) {
  ?>
    <div>
      <?php echo Slot::render('content', '<p>Default</p>'); ?>
    </div>
<?php
  });
});

component('bench-page', function ($c) {
  $c->view(function ($s) use ($c) {
    Slot::start('content');
    echo "<p>Injected content</p>";
    Slot::end();
    echo $c->renderComponent('bench-layout');
  });
});

for ($i = 0; $i < 100; $i++) {
  nova('bench-page');
}

$result = Performance::endTimer('slot_rendering');
echo "  Time: " . number_format($result['time'], 2) . "ms\n";
echo "  Memory: " . number_format($result['memory'] / 1024, 2) . " KB\n\n";

// Cache statistics
echo "Cache Statistics:\n";
$stats = Compiler::getCacheStats();
echo "  Memory cache: {$stats['memory_cache_count']} items\n";
echo "  File cache enabled: " . ($stats['file_cache_enabled'] ? 'Yes' : 'No') . "\n";
if ($stats['file_cache_enabled']) {
  echo "  File cache count: {$stats['file_cache_count']} files\n";
  echo "  File cache size: " . number_format($stats['file_cache_size'] / 1024, 2) . " KB\n";
}

echo "\n✅ Performance benchmark completed!\n";
