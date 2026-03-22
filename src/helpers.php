<?php

if (!function_exists('component')) {
  function component(string $name, callable $definition): void
  {
    $component = new \Luxid\Nova\Component($name, $definition);
    \Luxid\Nova\ComponentManager::register($name, $component);
  }
}

if (!function_exists('nova')) {
  function nova(string $name, array $props = []): string
  {
    // Check if an instance ID was passed in props
    $instanceId = $props['_instance'] ?? null;

    // Create a new component instance
    $component = \Luxid\Nova\ComponentManager::make($name, $instanceId);

    return $component->render($props);
  }
}

if (!function_exists('nova_action')) {
  /**
   * Call an action on a component instance
   * 
   * @param string $componentName
   * @param string $instanceId
   * @param string $action
   * @param array $params
   * @return string
   */
  function nova_action(string $componentName, string $instanceId, string $action, array $params = []): string
  {
    $component = \Luxid\Nova\ComponentManager::make($componentName, $instanceId);
    return $component->callAction($action, $params);
  }
}

// Keep existing helper functions...
if (!function_exists('nova_component_exists')) {
  function nova_component_exists(string $name): bool
  {
    return \Luxid\Nova\ComponentManager::has($name);
  }
}

if (!function_exists('nova_get_components')) {
  function nova_get_components(): array
  {
    return \Luxid\Nova\ComponentManager::all();
  }
}
