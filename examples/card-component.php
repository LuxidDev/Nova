<?php

require_once __DIR__ . '/../bootstrap.php';

component('card', function ($c) {
  $c->state(function () {
    return [
      'title' => 'Card Title',
      'variant' => 'default' // default, primary, warning
    ];
  });

  $c->view(function ($state) {
    $variantClasses = [
      'default' => 'border-gray-300',
      'primary' => 'border-blue-500 bg-blue-50',
      'warning' => 'border-yellow-500 bg-yellow-50'
    ];

    $class = $variantClasses[$state->variant] ?? $variantClasses['default'];
?>
    <div class="card <?php echo $class; ?>" style="border: 1px solid; padding: 15px; margin: 10px; border-radius: 5px;">
      <h2>@echo($state->title)</h2>

      <div class="card-content">
        @slot('content')
        Default content goes here
        @endslot
      </div>

      <div class="card-footer">
        @slot('footer')
        <button @click="close">Close</button>
        @endslot
      </div>
    </div>
  <?php
  });
});

component('button', function ($c) {
  $c->state(function () {
    return [
      'label' => 'Button',
      'type' => 'button',
      'variant' => 'primary'
    ];
  });

  $c->actions([
    'click' => function (&$state) {
      // Handle click action
    }
  ]);

  $c->view(function ($state) {
    $variantStyles = [
      'primary' => 'background: #4CAF50; color: white;',
      'danger' => 'background: #f44336; color: white;',
      'default' => 'background: #e0e0e0; color: #333;'
    ];

    $style = $variantStyles[$state->variant] ?? $variantStyles['default'];
  ?>
    <button type="@echo($state->type)"
      style="@echo($style) padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;"
      @click="click">
      @echo($state->label)
    </button>
<?php
  });
});
