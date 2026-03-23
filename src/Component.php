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
  protected ?string $compiledView = null;
  protected array $props = [];
  protected array $children = [];

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

  public function setProps(array $props): self
  {
    $this->props = $props;
    return $this;
  }

  public function prop(string $key, $default = null)
  {
    return $this->props[$key] ?? $default;
  }

  public function addChild(Component $child): self
  {
    $this->children[] = $child;
    return $this;
  }

  public function renderComponent(string $name, array $props = []): string
  {
    if (!ComponentManager::has($name)) {
      throw new \RuntimeException("Component '{$name}' not found");
    }

    $instanceId = $props['_instance'] ?? $this->getInstanceId() . '_' . $name;
    $component = ComponentManager::make($name, $instanceId);
    $component->setProps($props);
    $this->addChild($component);

    // Snapshot slots and freeze them
    Slot::freeze();
    $html = $component->render($props);
    Slot::thaw();

    return $html;
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

  protected function getRawTemplate(): string
  {
    if (!$this->view) {
      throw new \RuntimeException("Component '{$this->name}' has no view defined");
    }

    $this->initializeStateManager();
    $state = (object) $this->stateManager->all();

    ob_start();
    $view = $this->view;
    $view($state);
    return ob_get_clean();
  }

  protected function compileViewTemplate(): string
  {
    $rawTemplate = $this->getRawTemplate();
    $cacheKey = $this->name . '_' . md5($rawTemplate);
    return Compiler::compile($rawTemplate, $cacheKey);
  }

  public function render(array $props = []): string
  {
    Performance::startTimer('component_render_' . $this->name);

    $this->initializeStateManager();

    $stateArray = $this->stateManager->all();
    $props['_instance'] = $this->stateManager->getInstanceId();
    $mergedState = array_merge($stateArray, $props);

    $result = $this->executeView($mergedState);

    $timer = Performance::endTimer('component_render_' . $this->name);

    if ($timer && Compiler::$debug) {
      error_log("[Nova] Rendered '{$this->name}' in {$timer['time']}ms, memory: {$timer['memory']} bytes");
    }

    return $result;
  }

  protected function executeView(array $state): string
  {
    if ($this->compiledView === null) {
      $this->compiledView = $this->compileViewTemplate();
    }

    $state = (object) $state;
    $component = $this;
    $compiledView = $this->compiledView;

    $render = function () use ($state, $compiledView, $component) {
      ob_start();
      eval('?>' . $compiledView);
      return ob_get_clean();
    };

    return $render();
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
