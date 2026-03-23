<?php

require_once __DIR__ . '/../bootstrap.php';

// Simple card component with slots
component('simple-card', function ($c) {
  $c->state(function () {
    return ['title' => 'Simple Card'];
  });

  $c->view(function ($state) {
?>
    <div style="border: 1px solid #ccc; padding: 15px; margin: 10px; border-radius: 5px;">
      <h3>@echo($state->title)</h3>
      <div class="content">
        @slot('content')
        <p>Default content</p>
        @endslot
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
    <button style="background: #4CAF50; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer;">
      @echo($state->label)
    </button>
  <?php
  });
});

// Main component that uses nesting
component('main', function ($c) {
  $c->state(function () {
    return ['message' => 'Hello from main'];
  });

  $c->view(function ($state) {
  ?>
    <div>
      <h1>@echo($state->message)</h1>

      @component('simple-card', ['title' => 'My Card'])
      @slot('content')
      <p>This content is injected!</p>
      @component('simple-button', ['label' => 'Click Here'])
      @endslot
      @endcomponent
    </div>
<?php
  });
});

// Render the main component
$instanceId = 'main_' . uniqid();
echo nova('main', ['_instance' => $instanceId]);
