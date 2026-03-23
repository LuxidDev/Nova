<?php
// components.php - Shared component definitions

// Register test counter component
component('test-counter', function ($c) {
  $c->state(function () {
    return ['count' => 0];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
      return $state['count'];
    },
    'decrement' => function (&$state) {
      $state['count']--;
      return $state['count'];
    }
  ]);

  $c->view(function ($state) {
?>
    <div style="padding: 20px; border: 1px solid #ccc; text-align: center;">
      <h2>Test Counter</h2>
      <div style="font-size: 48px; margin: 20px;">@echo($state->count)</div>
      <button @click="decrement" style="padding: 10px 20px; margin: 5px;">-</button>
      <button @click="increment" style="padding: 10px 20px; margin: 5px;">+</button>
      <p style="font-size: 12px; color: #666; margin-top: 10px;">Instance: @echo($state->_instance)</p>
    </div>
  <?php
  });
});

// Register working counter component
component('working-counter', function ($c) {
  $c->state(function () {
    return ['count' => 0];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
    },
    'decrement' => function (&$state) {
      $state['count']--;
    }
  ]);

  $c->view(function ($state) {
  ?>
    <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
      <div style="font-size: 72px; font-weight: bold; margin: 20px 0;">
        @echo($state->count)
      </div>

      <div style="display: flex; gap: 15px; justify-content: center;">
        <button @click="decrement" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          ➖ Decrement
        </button>
        <button @click="increment" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          ➕ Increment
        </button>
      </div>

      <div style="margin-top: 20px; font-size: 12px; opacity: 0.7;">
        Instance: @echo($state->_instance)
      </div>
    </div>
  <?php
  });
});

// Register live counter component (for js-demo)
component('live-counter', function ($c) {
  $c->state(function () {
    return [
      'count' => 0,
      'message' => 'Ready for clicks!'
    ];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
      $state['message'] = "Count is now {$state['count']}";
    },
    'decrement' => function (&$state) {
      $state['count']--;
      $state['message'] = "Count is now {$state['count']}";
    },
    'reset' => function (&$state) {
      $state['count'] = 0;
      $state['message'] = "Counter reset!";
    }
  ]);

  $c->view(function ($state) {
  ?>
    <div style="text-align: center; padding: 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white;">
      <h2 style="margin: 0 0 10px 0;">@echo($state->message)</h2>
      <div style="font-size: 72px; font-weight: bold; margin: 20px 0;">
        @echo($state->count)
      </div>
      <div style="display: flex; gap: 15px; justify-content: center;">
        <button @click="decrement" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          ➖ Decrement
        </button>
        <button @click="reset" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          🔄 Reset
        </button>
        <button @click="increment" style="padding: 12px 24px; font-size: 18px; background: rgba(255,255,255,0.2); border: none; border-radius: 8px; color: white; cursor: pointer;">
          ➕ Increment
        </button>
      </div>
      <div style="margin-top: 20px; font-size: 12px; opacity: 0.7;">
        Instance ID: @echo($state->_instance)
      </div>
    </div>
  <?php
  });
});

// Register contact form component
component('contact-form', function ($c) {
  $c->state(function () {
    return [
      'name' => '',
      'email' => '',
      'message' => '',
      'submitted' => false,
      'errors' => []
    ];
  });

  $c->actions([
    'submit' => function (&$state, $data) {
      $state['errors'] = [];

      if (empty($data['name'])) {
        $state['errors']['name'] = 'Name is required';
      }

      if (empty($data['email'])) {
        $state['errors']['email'] = 'Email is required';
      } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $state['errors']['email'] = 'Please enter a valid email address';
      }

      if (empty($data['message'])) {
        $state['errors']['message'] = 'Message is required';
      } elseif (strlen($data['message']) < 10) {
        $state['errors']['message'] = 'Message must be at least 10 characters';
      }

      if (empty($state['errors'])) {
        $state['name'] = $data['name'];
        $state['email'] = $data['email'];
        $state['message'] = $data['message'];
        $state['submitted'] = true;
      }
    },
    'reset' => function (&$state) {
      $state['name'] = '';
      $state['email'] = '';
      $state['message'] = '';
      $state['submitted'] = false;
      $state['errors'] = [];
    }
  ]);

  $c->view(function ($state) {
  ?>
    <div style="background: white; border-radius: 12px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
      <h2 style="margin: 0 0 20px 0; color: #333;">Contact Form</h2>

      @if($state->submitted)
      <div style="background: #4CAF50; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3 style="margin: 0 0 10px 0;">Thank you for contacting us!</h3>
        <p><strong>Name:</strong> @echo($state->name)</p>
        <p><strong>Email:</strong> @echo($state->email)</p>
        <p><strong>Message:</strong> @echo($state->message)</p>
        <button @click="reset" style="margin-top: 15px; padding: 10px 20px; background: white; border: none; border-radius: 6px; cursor: pointer;">
          Send Another Message
        </button>
      </div>
      @else
      <form @submit="submit">
        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; font-weight: 500;">Name:</label>
          <input type="text" name="name" value="@echo($state->name)"
            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
          @if(isset($state->errors['name']))
          <div style="color: #f44336; font-size: 14px; margin-top: 5px;">
            @echo($state->errors['name'])
          </div>
          @endif
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; font-weight: 500;">Email:</label>
          <input type="email" name="email" value="@echo($state->email)"
            style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">
          @if(isset($state->errors['email']))
          <div style="color: #f44336; font-size: 14px; margin-top: 5px;">
            @echo($state->errors['email'])
          </div>
          @endif
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display: block; margin-bottom: 5px; font-weight: 500;">Message:</label>
          <textarea name="message" rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 16px;">@echo($state->message)</textarea>
          @if(isset($state->errors['message']))
          <div style="color: #f44336; font-size: 14px; margin-top: 5px;">
            @echo($state->errors['message'])
          </div>
          @endif
        </div>

        <button type="submit" style="width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 6px; font-size: 16px; cursor: pointer;">
          Send Message
        </button>
      </form>
      @endif
    </div>
<?php
  });
});
