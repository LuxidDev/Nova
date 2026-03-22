<?php

namespace Luxid\Nova;

/**
 * Component Manager
 * 
 * Registry for all Nova components. Components are registered
 * globally and can be resolved by name.
 */
class ComponentManager
{
  /**
   * Registered components
   * @var array<string, Component>
   */
  protected static array $components = [];

  /**
   * Register a component
   * 
   * @param string $name Component name
   * @param Component $component Component instance
   */
  public static function register(string $name, Component $component): void
  {
    static::$components[$name] = $component;
  }

  /**
   * Resolve a component by name
   * 
   * @param string $name Component name
   * @return Component|null
   */
  public static function resolve(string $name): ?Component
  {
    return static::$components[$name] ?? null;
  }

  /**
   * Check if a component exists
   * 
   * @param string $name Component name
   * @return bool
   */
  public static function has(string $name): bool
  {
    return isset(static::$components[$name]);
  }

  /**
   * Get all registered components
   * 
   * @return array<string, Component>
   */
  public static function all(): array
  {
    return static::$components;
  }

  /**
   * Remove a component from the registry
   * 
   * @param string $name Component name
   */
  public static function unregister(string $name): void
  {
    unset(static::$components[$name]);
  }

  /**
   * Clear all components (useful for testing)
   */
  public static function clear(): void
  {
    static::$components = [];
  }
}
