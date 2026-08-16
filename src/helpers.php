<?php

/**
 * Register a component (supports both old and new syntax)
 */
if (!function_exists('component')) {
  function component(string $name, callable $definition): void
  {
    // Check if it's the new simplified syntax (no $c parameter)
    $reflection = new ReflectionFunction($definition);
    $parameters = $reflection->getParameters();

    if (count($parameters) === 0) {
      // New syntax: component('name', function() { $state(...); $actions(...); $view(...); })
      \Luxid\Nova\ComponentManager::register($name, $definition);
    } else {
      // Old syntax: component('name', function($c) { ... })
      $component = new \Luxid\Nova\Component($name, $definition);
      \Luxid\Nova\ComponentManager::register($name, $component);
    }
  }
}

/**
 * Define component state (for use inside component definition)
 */
if (!function_exists('state')) {
  function state(callable $initializer): void
  {
    $component = \Luxid\Nova\ComponentManager::getCurrentComponent();
    if (!$component) {
      throw new \RuntimeException('state() can only be called inside component definition');
    }
    $component->state($initializer);
  }
}

/**
 * Define component actions (for use inside component definition)
 */
if (!function_exists('actions')) {
  function actions(array $actions): void
  {
    $component = \Luxid\Nova\ComponentManager::getCurrentComponent();
    if (!$component) {
      throw new \RuntimeException('actions() can only be called inside component definition');
    }
    $component->actions($actions);
  }
}

/**
 * Define component view (for use inside component definition)
 */
if (!function_exists('view')) {
  function view(callable $view): void
  {
    $component = \Luxid\Nova\ComponentManager::getCurrentComponent();
    if (!$component) {
      throw new \RuntimeException('view() can only be called inside component definition');
    }
    $component->view($view);
  }
}

/**
 * Define component props (for use inside component definition)
 */
if (!function_exists('props')) {
  function props(array $defaultProps = []): void
  {
    $component = \Luxid\Nova\ComponentManager::getCurrentComponent();
    if (!$component) {
      throw new \RuntimeException('props() can only be called inside component definition');
    }
    $component->setDefaultProps($defaultProps);
  }
}

/**
 * Declare which state keys a component may publish to the browser.
 *
 * State is not serialised into the page unless it is named here, so a component
 * holding a loaded database row does not leak it into the HTML.
 *
 * @param list<string> $keys State keys the browser is allowed to see
 *
 * @throws RuntimeException When called outside a component definition
 */
if (!function_exists('expose')) {
  function expose(array $keys): void
  {
    $component = \Luxid\Nova\ComponentManager::getCurrentComponent();

    if (!$component) {
      throw new \RuntimeException('expose() can only be called inside a component definition');
    }

    $component->expose($keys);
  }
}

/**
 * Render a Nova component
 */
if (!function_exists('nova')) {
  function nova(string $name, array $props = []): string
  {
    $instanceId = $props['_instance'] ?? null;
    $component = \Luxid\Nova\ComponentManager::make($name, $instanceId);
    return $component->render($props);
  }
}

/**
 * Call an action on a component instance
 */
if (!function_exists('nova_action')) {
  function nova_action(string $componentName, string $instanceId, string $action, array $params = []): string
  {
    $component = \Luxid\Nova\ComponentManager::make($componentName, $instanceId);
    return $component->callAction($action, $params);
  }
}

/**
 * Check if a component exists
 */
if (!function_exists('nova_component_exists')) {
  function nova_component_exists(string $name): bool
  {
    return \Luxid\Nova\ComponentManager::has($name);
  }
}

/**
 * Get all registered components
 */
if (!function_exists('nova_get_components')) {
  function nova_get_components(): array
  {
    return \Luxid\Nova\ComponentManager::all();
  }
}
