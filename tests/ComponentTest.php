<?php

namespace Luxid\Nova\Tests;

use PHPUnit\Framework\TestCase;
use Luxid\Nova\Component;
use Luxid\Nova\ComponentManager;

// Include the helpers file to ensure functions are loaded
require_once __DIR__ . '/../src/helpers.php';

class ComponentTest extends TestCase
{
  protected function setUp(): void
  {
    parent::setUp();
    ComponentManager::clear();
  }

  public function testComponentRegistration()
  {
    // Register a component - now component() is in global namespace
    component('test', function ($component) {
      $component->view(function ($state) {
        echo "Hello World";
      });
    });

    // Check if registered
    $this->assertTrue(ComponentManager::has('test'));
    $this->assertNotNull(ComponentManager::resolve('test'));
  }

  public function testComponentRendering()
  {
    // Register a component
    component('greeting', function ($component) {
      $component->view(function ($state) {
        echo "Hello, @echo(\$state->name)!";
      });
    });

    // Render with props
    $output = nova('greeting', ['name' => 'World']);

    // Check output (directive not processed yet, so raw output)
    $this->assertStringContainsString("Hello, @echo(\$state->name)!", $output);
  }

  public function testComponentState()
  {
    $component = new Component('test', function ($component) {
      $component->state(function () {
        return ['count' => 42];
      });

      $component->view(function ($state) {
        echo "Count: @echo(\$state->count)";
      });
    });

    $state = $component->getState();
    $this->assertEquals(42, $state['count']);
  }

  public function testComponentActions()
  {
    $component = new Component('test', function ($component) {
      $component->actions([
        'increment' => function (&$state) {
          $state['count']++;
        }
      ]);

      $component->state(function () {
        return ['count' => 0];
      });
    });

    $this->assertTrue($component->hasAction('increment'));
    $this->assertFalse($component->hasAction('decrement'));

    $actions = $component->getActions();
    $this->assertArrayHasKey('increment', $actions);
    $this->assertIsCallable($actions['increment']);
  }

  public function testComponentNotFound()
  {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Component 'missing' not found");

    nova('missing');
  }

  public function testComponentWithoutView()
  {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage("Component 'incomplete' has no view defined");

    component('incomplete', function ($component) {
      // No view defined
    });

    nova('incomplete');
  }
}
