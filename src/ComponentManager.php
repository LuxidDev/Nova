<?php

namespace Luxid\Nova;

class ComponentManager
{
  protected static array $components = [];
  protected static ?Component $currentComponent = null;

  /**
   * Register a component blueprint
   */
  public static function register(string $name, $component): void
  {
    static::$components[$name] = $component;
  }

  /**
   * Set the current component being built (for helper functions)
   */
  public static function setCurrentComponent(?Component $component): void
  {
    self::$currentComponent = $component;
  }

  /**
   * Get the current component being built (for helper functions)
   */
  public static function getCurrentComponent(): ?Component
  {
    return self::$currentComponent;
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

    // If it's a Component instance, use its definition
    if ($blueprint instanceof Component) {
      return new Component($name, $blueprint->getDefinition(), $instanceId);
    }

    // If it's a callable (simplified syntax), create component from it
    if (is_callable($blueprint)) {
      return Component::createFromDefinition($name, $blueprint, $instanceId);
    }

    throw new \RuntimeException("Invalid component registration for '{$name}'");
  }

  /**
   * Resolve a component (for backward compatibility)
   */
  public static function resolve(string $name): ?Component
  {
    $blueprint = static::$components[$name] ?? null;

    if ($blueprint instanceof Component) {
      return $blueprint;
    }

    if (is_callable($blueprint)) {
      return Component::createFromDefinition($name, $blueprint);
    }

    return null;
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

  /**
   * Clear request-scoped state, keeping the registry warm.
   *
   * The component registry itself is deliberately preserved: rebuilding it per
   * request is exactly the cost a worker runtime exists to avoid. Only the
   * pointer to the component currently being defined is per-request.
   */
  public static function resetRequestState(): void
  {
    self::$currentComponent = null;
  }
}
