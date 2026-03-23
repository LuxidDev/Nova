<?php

require_once __DIR__ . '/../bootstrap.php';

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
