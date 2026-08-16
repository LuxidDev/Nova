<?php

namespace Luxid\Nova;

class Performance
{
  protected static array $timers = [];
  protected static array $queries = [];
  protected static bool $enabled = false;

  public static function enable(): void
  {
    self::$enabled = true;
  }

  public static function disable(): void
  {
    self::$enabled = false;
  }

  public static function startTimer(string $name): void
  {
    if (!self::$enabled) return;

    self::$timers[$name] = [
      'start' => microtime(true),
      'memory' => memory_get_usage()
    ];
  }

  public static function endTimer(string $name): array
  {
    if (!self::$enabled || !isset(self::$timers[$name])) {
      return [];
    }

    $timer = self::$timers[$name];
    $result = [
      'name' => $name,
      'time' => (microtime(true) - $timer['start']) * 1000,
      'memory' => memory_get_usage() - $timer['memory']
    ];

    unset(self::$timers[$name]);

    return $result;
  }

  public static function recordQuery(string $sql, float $time): void
  {
    if (!self::$enabled) return;

    self::$queries[] = [
      'sql' => $sql,
      'time' => $time
    ];
  }

  public static function getReport(): array
  {
    return [
      'timers' => self::$timers,
      'queries' => self::$queries,
      'total_queries' => count(self::$queries),
      'total_query_time' => array_sum(array_column(self::$queries, 'time')),
      'memory_peak' => memory_get_peak_usage(true)
    ];
  }

  public static function clear(): void
  {
    self::$timers = [];
    self::$queries = [];
  }

  /**
   * Discard collected timings and queries.
   *
   * These accumulate for the life of the process, so a worker would grow them
   * without bound.
   */
  public static function reset(): void
  {
    self::$timers = [];
    self::$queries = [];
  }
}
