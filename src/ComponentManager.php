<?php

namespace Luxid\Nova;

class ComponentManager
{
  protected static array $components = [];

  /**
   * Register a component blueprint
   */
  public static function register(string $name, Component $component): void
  {
    static::$components[$name] = $component;
  }

  /**
   * Create a new instance of a component
   */
  public static function make(string $name, ?string $instanceId = null): Component
  {
    $blueprint = static::$components[$name] ?? null;

    if (!$blueprint) {
      throw new \RuntimeException("Component '{$name}' not found");
    }

    // Use getter method to access the definition
    return new Component($name, $blueprint->getDefinition(), $instanceId);
  }

  /**
   * Resolve a component (for backward compatibility)
   */
  public static function resolve(string $name): ?Component
  {
    return static::$components[$name] ?? null;
  }

  /**
   * Check if a component exists
   */
  public static function has(string $name): bool
  {
    return isset(static::$components[$name]);
  }

  /**
   * Get all registered components
   */
  public static function all(): array
  {
    return static::$components;
  }

  /**
   * Remove a component
   */
  public static function unregister(string $name): void
  {
    unset(static::$components[$name]);
  }

  /**
   * Clear all components
   */
  public static function clear(): void
  {
    static::$components = [];
  }
}
