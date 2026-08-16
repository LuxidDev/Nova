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
  protected array $props = [];
  protected array $defaultProps = [];
  protected array $children = [];

  /**
   * State keys published to the browser via `data-nova-state`.
   *
   * @var list<string>
   */
  protected array $exposedState = [];

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

  /**
   * Render the component and wrap it in its client-side envelope.
   *
   * @param array<string, mixed> $props Props supplied by the caller
   */
  public function render(array $props = []): string
  {
    $this->initializeStateManager();

    $props['_instance'] = $this->stateManager->getInstanceId();
    $mergedState = array_merge($this->stateManager->all(), $props);

    return $this->executeView($mergedState);
  }

  /**
   * Compile and execute the view, wrapped in the element the client runtime
   * uses to locate this instance.
   *
   * @param array<string, mixed> $state Merged state and props
   */
  protected function executeView(array $state): string
  {
    $rawTemplate = $this->getRawTemplate();
    $instanceId = $this->getInstanceId();

    $html = Compiler::render(
      $rawTemplate,
      $this->name . ':' . hash('xxh128', $rawTemplate),
      [
        'state' => (object) $state,
        'component' => $this,
      ]
    );

    return '<div data-nova-component'
      . ' data-nova-id="' . htmlspecialchars($instanceId, ENT_QUOTES) . '"'
      . ' data-nova-component-name="' . htmlspecialchars($this->name, ENT_QUOTES) . '"'
      . $this->stateAttribute($state)
      . '>' . $html . '</div>';
  }

  /**
   * Render the `data-nova-state` attribute, if the component exposes state.
   *
   * State is opt-in rather than automatic. Serialising everything meant any
   * value a component happened to hold — a loaded user row, an API token —
   * was published into the page's HTML for anyone to read.
   *
   * @param array<string, mixed> $state Merged state and props
   */
  protected function stateAttribute(array $state): string
  {
    if ($this->exposedState === []) {
      return '';
    }

    $exposed = array_intersect_key($state, array_flip($this->exposedState));

    if ($exposed === []) {
      return '';
    }

    $encoded = json_encode($exposed, JSON_THROW_ON_ERROR);

    return ' data-nova-state="' . htmlspecialchars($encoded, ENT_QUOTES) . '"';
  }

  /**
   * Declare which state keys may be published to the client.
   *
   * @param list<string> $keys State keys the browser is allowed to see
   */
  public function expose(array $keys): self
  {
    $this->exposedState = $keys;

    return $this;
  }

  /**
   * Get the state keys published to the client.
   *
   * @return list<string>
   */
  public function getExposedState(): array
  {
    return $this->exposedState;
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
