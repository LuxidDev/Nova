<?php

// Define helper functions in the global namespace
if (!function_exists('component')) {
  /**
   * Register a Nova component
   *
   * @param string $name Component name
   * @param callable $definition Component definition closure
   */
  function component(string $name, callable $definition): void
  {
    $component = new \Luxid\Nova\Component($name, $definition);
    \Luxid\Nova\ComponentManager::register($name, $component);
  }
}

if (!function_exists('nova')) {
  /**
   * Render a Nova component
   *
   * @param string $name Component name
   * @param array $props Component properties
   * @return string Rendered HTML
   */
  function nova(string $name, array $props = []): string
  {
    $component = \Luxid\Nova\ComponentManager::resolve($name);

    if (!$component) {
      throw new \RuntimeException("Component '{$name}' not found");
    }

    return $component->render($props);
  }
}

if (!function_exists('nova_component_exists')) {
  /**
   * Check if a component exists
   *
   * @param string $name Component name
   * @return bool
   */
  function nova_component_exists(string $name): bool
  {
    return \Luxid\Nova\ComponentManager::has($name);
  }
}

if (!function_exists('nova_get_components')) {
  /**
   * Get all registered components
   *
   * @return array
   */
  function nova_get_components(): array
  {
    return \Luxid\Nova\ComponentManager::all();
  }
}
