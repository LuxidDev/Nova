<?php

namespace Luxid\Nova;

class ComponentCache
{
  protected static array $instances = [];
  protected static ?string $cachePath = null;
  protected static bool $enabled = false;

  public static function enable(string $path = null): void
  {
    self::$enabled = true;

    if ($path) {
      self::$cachePath = rtrim($path, '/');
      if (!is_dir(self::$cachePath)) {
        mkdir(self::$cachePath, 0755, true);
      }
    }
  }

  public static function disable(): void
  {
    self::$enabled = false;
  }

  public static function remember(string $key, callable $callback)
  {
    if (!self::$enabled) {
      return $callback();
    }

    // Check memory cache
    if (isset(self::$instances[$key])) {
      return self::$instances[$key];
    }

    // Check file cache
    if (self::$cachePath) {
      $cacheFile = self::$cachePath . '/' . md5($key) . '.cache';
      if (file_exists($cacheFile)) {
        $cached = unserialize(file_get_contents($cacheFile));
        if ($cached && $cached['expires'] > time()) {
          self::$instances[$key] = $cached['data'];
          return $cached['data'];
        }
      }
    }

    // Generate fresh
    $data = $callback();

    // Store in memory
    self::$instances[$key] = $data;

    // Store in file cache
    if (self::$cachePath) {
      $cacheFile = self::$cachePath . '/' . md5($key) . '.cache';
      file_put_contents($cacheFile, serialize([
        'data' => $data,
        'expires' => time() + 3600
      ]), LOCK_EX);
    }

    return $data;
  }

  public static function forget(string $key): void
  {
    unset(self::$instances[$key]);

    if (self::$cachePath) {
      $cacheFile = self::$cachePath . '/' . md5($key) . '.cache';
      if (file_exists($cacheFile)) {
        unlink($cacheFile);
      }
    }
  }

  public static function clear(): void
  {
    self::$instances = [];

    if (self::$cachePath && is_dir(self::$cachePath)) {
      $files = glob(self::$cachePath . '/*.cache');
      foreach ($files as $file) {
        unlink($file);
      }
    }
  }
}
