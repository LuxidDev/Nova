<?php

namespace Luxid\Nova;

class Slot
{
  /** Captured slot contents: name → html */
  protected static array $slots = [];

  /** Stack of active ob_start() captures: [name, skip] */
  protected static array $stack = [];

  /**
   * Slot names that were already captured by the caller and must not be
   * overwritten by the component's own default slot definitions.
   */
  protected static array $frozen = [];

  /**
   * Begin capturing a slot.
   * If the slot was already filled by a parent @slot() directive, this call
   * becomes a no-op capture (we still run ob_start/ob_get_clean to consume
   * the output, but we discard it instead of overwriting).
   */
  public static function start(string $name): void
  {
    $skip = isset(self::$frozen[$name]);
    self::$stack[] = ['name' => $name, 'skip' => $skip];
    ob_start();
  }

  /**
   * End the current slot capture. Saves content unless this was a skip.
   */
  public static function end(): void
  {
    $content = ob_get_clean();

    if (empty(self::$stack)) {
      return;
    }

    $item = array_pop(self::$stack);

    if (!$item['skip']) {
      self::$slots[$item['name']] = $content;
    }
  }

  /**
   * Return the content for a slot, or $default if it was never filled.
   */
  public static function render(string $name, string $default = ''): string
  {
    return self::$slots[$name] ?? $default;
  }

  public static function has(string $name): bool
  {
    return isset(self::$slots[$name]);
  }

  /**
   * Freeze all currently-captured slots so that when the component renders
   * its own view (which contains default @slot blocks), those defaults cannot
   * overwrite what the caller already provided.
   *
   * Call this immediately before rendering the component view, then call
   * thaw() immediately after to reset for the next component.
   */
  public static function freeze(): void
  {
    self::$frozen = array_fill_keys(array_keys(self::$slots), true);
  }

  /**
   * Clear frozen state and all slot contents after the component has finished
   * rendering so the next component starts with a clean slate.
   */
  public static function thaw(): void
  {
    self::$frozen = [];
    self::$slots  = [];
  }

  /**
   * Hard reset — use between top-level component renders.
   */
  public static function clear(): void
  {
    self::$slots   = [];
    self::$stack   = [];
    self::$frozen  = [];
  }
}
