<?php

namespace Luxid\Nova;

use Closure;

class Component
{
  protected string $name;
  protected Closure $definition;
  protected ?StateManager $stateManager = null;
  protected ?Closure $stateInitializer = null;
  protected array $actions = [];
  protected ?Closure $view = null;
  protected ?string $instanceId = null;

  // Track if the view has been compiled
  protected ?string $compiledView = null;

  public function __construct(string $name, Closure $definition, ?string $instanceId = null)
  {
    $this->name = $name;
    $this->definition = $definition;
    $this->instanceId = $instanceId;

    $this->processDefinition();
  }

  public function getDefinition(): Closure
  {
    return $this->definition;
  }

  protected function processDefinition(): void
  {
    $definition = $this->definition;
    $definition($this);
  }

  public function state(Closure $initializer): self
  {
    $this->stateInitializer = $initializer;
    return $this;
  }

  public function actions(array $actions): self
  {
    $this->actions = $actions;
    return $this;
  }

  public function view(Closure $view): self
  {
    $this->view = $view;
    return $this;
  }

  public function instanceId(string $id): self
  {
    $this->instanceId = $id;
    return $this;
  }

  protected function initializeStateManager(): void
  {
    if ($this->stateManager !== null) {
      return;
    }

    $this->stateManager = new StateManager($this->name, $this->instanceId);

    if ($this->stateInitializer) {
      $initializer = $this->stateInitializer;
      $defaultState = $initializer();

      if (empty($this->stateManager->all())) {
        $this->stateManager->initialize($defaultState);
      }
    }
  }

  /**
   * Get the raw template string from the view closure
   */
  protected function getRawTemplate(): string
  {
    if (!$this->view) {
      throw new \RuntimeException("Component '{$this->name}' has no view defined");
    }

    // Capture the output of the view closure with a dummy state
    // This gives us the raw template with directives
    $dummyState = new \stdClass();

    ob_start();
    $view = $this->view;
    $view($dummyState);
    $template = ob_get_clean();

    return $template;
  }

  /**
   * Compile the view template
   */
  protected function compileViewTemplate(): string
  {
    $rawTemplate = $this->getRawTemplate();
    $cacheKey = $this->name . '_' . md5($rawTemplate);

    // Compile directives to PHP
    $compiled = Compiler::compile($rawTemplate, $cacheKey);

    return $compiled;
  }

  /**
   * Render the component
   */
  public function render(array $props = []): string
  {
    $this->initializeStateManager();

    $stateArray = $this->stateManager->all();

    // Always add the instance ID to props
    $props['_instance'] = $this->stateManager->getInstanceId();

    // Merge props with state
    $mergedState = array_merge($stateArray, $props);

    return $this->executeView($mergedState);
  }

  /**
   * Execute the compiled view with the given state
   */
  protected function executeView(array $state): string
  {
    if ($this->compiledView === null) {
      $this->compiledView = $this->compileViewTemplate();
    }

    ob_start();

    try {
      // Extract the state as an object variable
      $state = (object) $state;

      // Execute the compiled code - it will have access to $state
      eval('?>' . $this->compiledView);

      return ob_get_clean();
    } catch (\ParseError $e) {
      ob_end_clean();
      error_log("Compilation error in component '{$this->name}': " . $e->getMessage());
      error_log("Compiled code:\n" . ($this->compiledView ?? 'null'));
      throw new \RuntimeException(
        "Compilation error in component '{$this->name}': " . $e->getMessage()
      );
    }
  }

  /**
   * Call an action on the component
   */
  public function callAction(string $action, array $params = []): string
  {
    if (!isset($this->actions[$action])) {
      throw new \RuntimeException("Action '{$action}' not found in component '{$this->name}'");
    }

    $this->initializeStateManager();

    $state = $this->stateManager->all();

    $actionFn = $this->actions[$action];
    $result = $actionFn($state, ...$params);

    foreach ($state as $key => $value) {
      $this->stateManager->set($key, $value);
    }

    return $result ?? $this->render();
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function getStateManager(): ?StateManager
  {
    $this->initializeStateManager();
    return $this->stateManager;
  }

  public function getState(): array
  {
    $this->initializeStateManager();
    return $this->stateManager->all();
  }

  public function hasAction(string $action): bool
  {
    return isset($this->actions[$action]);
  }

  public function getActions(): array
  {
    return $this->actions;
  }

  public function getInstanceId(): string
  {
    $this->initializeStateManager();
    return $this->stateManager->getInstanceId();
  }
}
