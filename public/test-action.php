<?php

require_once dirname(__DIR__) . '/bootstrap.php';

// Register a simple test component
component('test-counter', function ($c) {
  $c->state(function () {
    return ['count' => 0];
  });

  $c->actions([
    'increment' => function (&$state) {
      $state['count']++;
      return $state['count'];
    }
  ]);

  $c->view(function ($state) {
?>
    <div style="padding: 20px; border: 1px solid #ccc; text-align: center;">
      <h2>Test Counter</h2>
      <div style="font-size: 48px; margin: 20px;">@echo($state->count)</div>
      <button onclick="testAction()" style="padding: 10px 20px;">Increment via JS</button>
    </div>

    <script>
      async function testAction() {
        const component = document.querySelector('[data-nova-component]');
        const componentId = component.dataset.novaId;

        console.log('Calling action on component:', componentId);

        const response = await fetch(window.location.href, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            component: componentId,
            action: 'increment',
            params: {}
          })
        });

        const html = await response.text();
        component.outerHTML = html;

        // Re-attach the testAction function to the new button
        const newButton = document.querySelector('button');
        if (newButton) {
          newButton.onclick = testAction;
        }
      }
    </script>
<?php
  });
});

// Render the component
$instanceId = 'test-counter_' . uniqid();
echo nova('test-counter', ['_instance' => $instanceId]);
