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

  public function __construct(string $name, Closure $definition, ?string $instanceId = null)
  {
    $this->name = $name;
    $this->definition = $definition;
    $this->instanceId = $instanceId;

    $this->processDefinition();
  }

  /**
   * Get the component's definition (for cloning)
   */
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

  public function render(array $props = []): string
  {
    $this->initializeStateManager();

    $stateArray = $this->stateManager->all();

    $props['_instance'] = $this->stateManager->getInstanceId();

    $mergedState = array_merge($stateArray, $props);

    return $this->compileView($mergedState);
  }

  protected function compileView(array $state): string
  {
    if (!$this->view) {
      throw new \RuntimeException("Component '{$this->name}' has no view defined");
    }

    ob_start();

    if (!isset($state['_instance'])) {
      $state['_instance'] = $this->stateManager->getInstanceId();
    }

    $state = (object) $state;

    $view = $this->view;
    $view($state);

    return ob_get_clean();
  }

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
