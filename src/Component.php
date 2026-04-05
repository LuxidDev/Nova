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
  protected array $defaultProps = [];
  protected array $children = [];

  public function __construct(string $name, Closure $definition, ?string $instanceId = null)
  {
    $this->name = $name;
    $this->definition = $definition;
    $this->instanceId = $instanceId;
    $this->processDefinition();
  }

  /**
   * Create a component from a simplified definition (no $c parameter)
   */
  public static function createFromDefinition(string $name, callable $definition, ?string $instanceId = null): self
  {
    // Create a temporary component to capture the definition
    $component = new self($name, function ($c) {}, $instanceId);

    // Set as current component for helper functions
    ComponentManager::setCurrentComponent($component);

    // Execute the user's definition (which uses $state, $actions, $view)
    $definition();

    // Clear current component
    ComponentManager::setCurrentComponent(null);

    return $component;
  }

  public function getDefinition(): Closure
  {
    return $this->definition;
  }

  /**
   * Set default props (called via props() helper)
   */
  public function setDefaultProps(array $props): self
  {
    $this->defaultProps = $props;
    return $this;
  }

  /**
   * Get default props
   */
  public function getDefaultProps(): array
  {
    return $this->defaultProps;
  }

  /**
   * Set props with defaults merged
   */
  public function setProps(array $props): self
  {
    $this->props = array_merge($this->defaultProps, $props);
    return $this;
  }

  protected function processDefinition(): void
  {
    ($this->definition)($this);
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

    Slot::freeze();
    $html = $component->render($props);
    Slot::thaw();

    return $html;
  }

  protected function initializeStateManager(): void
  {
    if ($this->stateManager !== null) return;

    $this->stateManager = new StateManager($this->name, $this->instanceId);

    if ($this->stateInitializer) {
      $defaultState = ($this->stateInitializer)();
      if (empty($this->stateManager->all())) {
        $this->stateManager->initialize($defaultState);
      }
    }
  }

  protected function getRawTemplate(): string
  {
    if (!$this->view) throw new \RuntimeException("Component '{$this->name}' has no view defined");

    $this->initializeStateManager();
    $state = (object) $this->stateManager->all();

    ob_start();
    ($this->view)($state);
    return ob_get_clean();
  }

  protected function compileViewTemplate(): string
  {
    $rawTemplate = $this->getRawTemplate();
    return Compiler::compile($rawTemplate, $this->name . '_' . md5($rawTemplate));
  }

  public function render(array $props = []): string
  {
    $this->initializeStateManager();

    $props['_instance'] = $this->stateManager->getInstanceId();
    $mergedState = array_merge($this->stateManager->all(), $props);

    return $this->executeView($mergedState);
  }

  protected function executeView(array $state): string
  {
    if ($this->compiledView === null) {
      $this->compiledView = $this->compileViewTemplate();
    }

    $state        = (object) $state;
    $component    = $this;
    $compiledView = $this->compiledView;

    return (function () use ($state, $compiledView, $component) {
      ob_start();

      $instanceId = $component->getInstanceId();
      $stateArray = get_object_vars($state);

      echo '<div 
        data-nova-component 
        data-nova-id="' . htmlspecialchars($instanceId) . '"
        data-nova-component-name="' . htmlspecialchars($component->getName()) . '"';
      if (!empty($stateArray)) {
        echo ' data-nova-state="' . htmlspecialchars(json_encode($stateArray), ENT_QUOTES) . '"';
      }
      echo '>';
      eval('?>' . $compiledView);
      echo '</div>';

      return ob_get_clean();
    })();
  }

  public function callAction(string $action, array $params = []): string
  {
    if (!isset($this->actions[$action])) {
      throw new \RuntimeException("Action '{$action}' not found in component '{$this->name}'");
    }

    $this->initializeStateManager();

    $state = $this->stateManager->all();

    // Pass the params as a single array argument
    ($this->actions[$action])($state, $params);

    // Write mutated values back into the state manager
    foreach ($state as $key => $value) {
      $this->stateManager->set($key, $value);
    }

    // Persist to session immediately
    $this->stateManager->flush();

    return $this->render();
  }

  public function getName(): string
  {
    return $this->name;
  }

  public function hasAction(string $action): bool
  {
    return isset($this->actions[$action]);
  }

  public function getActions(): array
  {
    return $this->actions;
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

  public function getInstanceId(): string
  {
    $this->initializeStateManager();
    return $this->stateManager->getInstanceId();
  }
}
