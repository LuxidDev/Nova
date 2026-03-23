<?php

require_once __DIR__ . '/../bootstrap.php';

component('counter-demo', function ($c) {
  $c->state(function () {
    return [
      'count' => 0,
      'message' => 'Click the buttons!'
    ];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
      $state['message'] = "Count incremented to {$state['count']}";
    },
    'decrement' => function (&$state) {
      $state['count']--;
      $state['message'] = "Count decremented to {$state['count']}";
    },
    'reset' => function (&$state) {
      $state['count'] = 0;
      $state['message'] = "Counter reset!";
    }
  ]);

  $c->view(function ($state) {
?>
    <div style="text-align: center; padding: 20px; font-family: sans-serif;">
      <h2>@echo($state->message)</h2>

      <div style="font-size: 72px; margin: 20px; font-weight: bold;">
        @echo($state->count)
      </div>

      <div style="display: flex; gap: 10px; justify-content: center;">
        <button @click="decrement" style="padding: 10px 20px; font-size: 18px;">
          -
        </button>
        <button @click="reset" style="padding: 10px 20px; font-size: 18px;">
          Reset
        </button>
        <button @click="increment" style="padding: 10px 20px; font-size: 18px;">
          +
        </button>
      </div>

      <div style="margin-top: 20px; font-size: 12px; color: #666;">
        Instance ID: @echo($state->_instance)
      </div>
    </div>
  <?php
  });
});

// Form example with validation
component('form-demo', function ($c) {
  $c->state(function () {
    return [
      'name' => '',
      'email' => '',
      'submitted' => false,
      'errors' => []
    ];
  });

  $c->actions([
    'submitForm' => function (&$state, $data) {
      $state['errors'] = [];

      if (empty($data['name'])) {
        $state['errors']['name'] = 'Name is required';
      }

      if (empty($data['email'])) {
        $state['errors']['email'] = 'Email is required';
      } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $state['errors']['email'] = 'Invalid email format';
      }

      if (empty($state['errors'])) {
        $state['name'] = $data['name'];
        $state['email'] = $data['email'];
        $state['submitted'] = true;
      }
    },
    'resetForm' => function (&$state) {
      $state['name'] = '';
      $state['email'] = '';
      $state['submitted'] = false;
      $state['errors'] = [];
    }
  ]);

  $c->view(function ($state) {
  ?>
    <div style="max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">
      <h2>Form Demo</h2>

      @if($state->submitted)
      <div style="background: #4CAF50; color: white; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
        <h3>Thank you for submitting!</h3>
        <p>Name: @echo($state->name)</p>
        <p>Email: @echo($state->email)</p>
        <button @click="resetForm" style="margin-top: 10px;">Submit Another</button>
      </div>
      @else
      <form @submit="submitForm">
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px;">Name:</label>
          <input type="text" name="name" value="@echo($state->name)"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          @if(isset($state->errors['name']))
          <div style="color: #f44336; font-size: 12px; margin-top: 5px;">
            @echo($state->errors['name'])
          </div>
          @endif
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px;">Email:</label>
          <input type="email" name="email" value="@echo($state->email)"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          @if(isset($state->errors['email']))
          <div style="color: #f44336; font-size: 12px; margin-top: 5px;">
            @echo($state->errors['email'])
          </div>
          @endif
        </div>

        <button type="submit" style="background: #4CAF50; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
          Submit
        </button>
      </form>
      @endif
    </div>
<?php
  });
});

// Render the demo
$instanceId = 'demo_' . uniqid();
?>
<!DOCTYPE html>
<html>

<head>
  <title>Nova JavaScript Demo</title>
  <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      background: #f5f5f5;
    }

    .demo-section {
      background: white;
      margin: 20px 0;
      padding: 20px;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    button {
      cursor: pointer;
      transition: transform 0.1s;
    }

    button:active {
      transform: scale(0.95);
    }

    input:focus {
      outline: none;
      border-color: #4CAF50;
    }
  </style>
</head>

<body>
  <h1>Luxid Nova - JavaScript Integration Demo</h1>

  <div class="demo-section">
    <h2>Counter with AJAX</h2>
    <?php echo nova('counter-demo', ['_instance' => $instanceId . '_counter']); ?>
  </div>

  <div class="demo-section">
    <h2>Form with Validation</h2>
    <?php echo nova('form-demo', ['_instance' => $instanceId . '_form']); ?>
  </div>

  <div class="demo-section">
    <h2>Instructions</h2>
    <ul>
      <li>Click the counter buttons - they work without page reload!</li>
      <li>Submit the form with validation - errors show inline</li>
      <li>All actions are handled by Nova.js with loading states</li>
      <li>Check the browser console for debug output (if enabled)</li>
    </ul>
  </div>

  <!-- Load Nova JavaScript -->
  <script src="/nova.js"></script>
</body>

</html>
