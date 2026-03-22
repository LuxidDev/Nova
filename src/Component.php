<?php

namespace Luxid\Nova;

use Closure;

/**
 * Component Class
 * 
 * Represents a server-side rendered component with state and actions.
 * 
 * A component is defined with three parts:
 * 1. State: Data that persists between requests
 * 2. Actions: Functions that modify state
 * 3. View: HTML template that renders the component
 */
class Component
{
  /**
   * The unique name of this component
   * @var string
   */
  protected string $name;

  /**
   * The component definition (from the user's closure)
   * @var Closure
   */
  protected Closure $definition;

  /**
   * The component's state (initialized later)
   * @var array|null
   */
  protected ?array $state = null;

  /**
   * The component's state initializer (optional)
   * @var Closure|null
   */
  protected ?Closure $stateInitializer = null;

  /**
   * The component's actions
   * @var array
   */
  protected array $actions = [];

  /**
   * The component's view renderer
   * @var Closure|null
   */
  protected ?Closure $view = null;

  /**
   * Constructor
   * 
   * @param string $name The component's unique identifier
   * @param Closure $definition The definition closure
   */
  public function __construct(string $name, Closure $definition)
  {
    $this->name = $name;
    $this->definition = $definition;

    // Process the definition to extract component parts
    $this->processDefinition();
  }

  /**
   * Process the definition closure to extract state, actions, and view
   */
  protected function processDefinition(): void
  {
    // Execute the definition closure, passing $this as context
    // This allows the user to call methods like $component->state()
    $definition = $this->definition;
    $definition($this);
  }

  /**
   * Set the component's state initializer
   * 
   * @param Closure $initializer Function that returns initial state array
   * @return self
   */
  public function state(Closure $initializer): self
  {
    $this->stateInitializer = $initializer;
    return $this;
  }

  /**
   * Set the component's actions
   * 
   * @param array $actions Array of action names to functions
   * @return self
   */
  public function actions(array $actions): self
  {
    $this->actions = $actions;
    return $this;
  }

  /**
   * Set the component's view renderer
   * 
   * @param Closure $view Function that outputs HTML
   * @return self
   */
  public function view(Closure $view): self
  {
    $this->view = $view;
    return $this;
  }

  /**
   * Render the component
   * 
   * @param array $props Properties passed to the component
   * @return string Rendered HTML
   */
  public function render(array $props = []): string
  {
    // Initialize state if not already done
    $this->initializeState();

    // Merge props with state (props override state)
    $mergedState = array_merge($this->state, $props);

    // Compile and render the view
    return $this->compileView($mergedState);
  }

  /**
   * Initialize the component's state
   */
  protected function initializeState(): void
  {
    if ($this->state !== null) {
      return; // Already initialized
    }

    if ($this->stateInitializer) {
      $initializer = $this->stateInitializer;
      $this->state = $initializer();
    } else {
      $this->state = [];
    }
  }

  /**
   * Compile and execute the view
   * 
   * @param array $state The component's state
   * @return string Rendered output
   */
  protected function compileView(array $state): string
  {
    if (!$this->view) {
      throw new \RuntimeException("Component '{$this->name}' has no view defined");
    }

    // Start output buffering to capture the view's output
    ob_start();

    // Make state available in the view
    $state = (object) $state; // Convert to object for property access

    // Execute the view closure
    $view = $this->view;
    $view($state);

    // Capture and return the output
    $output = ob_get_clean();

    // TODO: Apply directive compilation (will be added in Phase 3)
    // For now, we just return raw output

    return $output;
  }

  /**
   * Get the component's name
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * Get the component's current state
   */
  public function getState(): array
  {
    $this->initializeState();
    return $this->state;
  }

  /**
   * Check if the component has an action
   */
  public function hasAction(string $action): bool
  {
    return isset($this->actions[$action]);
  }

  /**
   * Get the component's actions
   */
  public function getActions(): array
  {
    return $this->actions;
  }
}
