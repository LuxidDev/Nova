<?php

require_once __DIR__ . '/../bootstrap.php';

use Luxid\Nova\Validation\Validator;
use Luxid\Nova\Form\Form;

component('registration-form', function ($c) {
  $c->state(function () {
    return [
      'name' => '',
      'email' => '',
      'password' => '',
      'password_confirmation' => '',
      'errors' => [],
      'success' => false
    ];
  });

  $c->actions([
    'register' => function (&$state, $data) {
      // Validate
      $validator = new Validator($data, [
        'name' => 'required|min:2|max:50',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
        'password_confirmation' => 'required'
      ]);

      if ($validator->validate()) {
        // Simulate user registration
        $state['name'] = $data['name'];
        $state['email'] = $data['email'];
        $state['success'] = true;
        $state['errors'] = [];
      } else {
        $state['errors'] = $validator->getErrors();
        $state['success'] = false;

        // Keep the submitted values
        $state['name'] = $data['name'] ?? '';
        $state['email'] = $data['email'] ?? '';
      }
    }
  ]);

  $c->view(function ($state) {
?>
    <div style="max-width: 500px; margin: 0 auto; padding: 20px; font-family: sans-serif;">
      <h2>Create an Account</h2>

      <?php if ($state->success): ?>
        <div style="background: #4CAF50; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
          <h3>Registration Successful!</h3>
          <p>Welcome, <?php echo htmlspecialchars($state->name); ?>!</p>
          <p>We've sent a confirmation email to <?php echo htmlspecialchars($state->email); ?>.</p>
        </div>
      <?php endif; ?>

      <form @submit="register" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Name *</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($state->name); ?>"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          <?php if (isset($state->errors['name'])): ?>
            <div style="color: #f44336; font-size: 12px; margin-top: 5px;">
              <?php echo htmlspecialchars($state->errors['name'][0]); ?>
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Email *</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($state->email); ?>"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          <?php if (isset($state->errors['email'])): ?>
            <div style="color: #f44336; font-size: 12px; margin-top: 5px;">
              <?php echo htmlspecialchars($state->errors['email'][0]); ?>
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Password *</label>
          <input type="password" name="password"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
          <?php if (isset($state->errors['password'])): ?>
            <div style="color: #f44336; font-size: 12px; margin-top: 5px;">
              <?php echo htmlspecialchars($state->errors['password'][0]); ?>
            </div>
          <?php endif; ?>
        </div>

        <div style="margin-bottom: 15px;">
          <label style="display: block; margin-bottom: 5px; font-weight: bold;">Confirm Password *</label>
          <input type="password" name="password_confirmation"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <button type="submit" style="width: 100%; padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">
          Register
        </button>
      </form>

      <div style="margin-top: 20px; font-size: 12px; color: #999;">
        <p>Instance: @echo($state->_instance)</p>
      </div>
    </div>
<?php
  });
});

// Render the form
$instanceId = 'registration_' . uniqid();
?>
<!DOCTYPE html>
<html>

<head>
  <title>Registration Form - Nova Validation Demo</title>
  <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }

    .container {
      max-width: 600px;
      margin: 0 auto;
    }

    .card {
      background: white;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    h1 {
      color: #333;
      margin-top: 0;
    }

    input:focus {
      outline: none;
      border-color: #4CAF50;
    }

    button:hover {
      background: #45a049;
    }

    button:active {
      transform: scale(0.98);
    }

    [data-nova-loading] {
      opacity: 0.6;
      pointer-events: none;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="card">
      <h1>✨ Nova Validation Demo</h1>
      <p>Try submitting the form with invalid data to see validation in action!</p>
      <?php echo nova('registration-form', ['_instance' => $instanceId]); ?>
    </div>
  </div>

  <script src="/nova.js"></script>
</body>

</html>
