<?php

namespace Luxid\Nova;

class Compiler
{
  protected static array $cache = [];
  protected static ?string $cachePath = null;
  protected static bool $useFileCache = false;
  public static bool $debug = false;

  public static function setCachePath(string $path): void
  {
    self::$cachePath = rtrim($path, '/');
    self::$useFileCache = true;
    if (!is_dir(self::$cachePath)) {
      mkdir(self::$cachePath, 0755, true);
    }
  }

  public static function enableDebug(bool $enable = true): void
  {
    self::$debug = $enable;
  }

  public static function compile(string $template, ?string $cacheKey = null): string
  {
    // Check memory cache
    if ($cacheKey && isset(self::$cache[$cacheKey])) {
      return self::$cache[$cacheKey];
    }

    // Check file cache
    if ($cacheKey && self::$useFileCache && self::$cachePath) {
      $cacheFile = self::$cachePath . '/' . md5($cacheKey) . '.php';
      if (file_exists($cacheFile)) {
        $compiled = file_get_contents($cacheFile);
        self::$cache[$cacheKey] = $compiled;
        return $compiled;
      }
    }

    $compiled = self::compileString($template);

    // Store in cache
    if ($cacheKey) {
      self::$cache[$cacheKey] = $compiled;

      if (self::$useFileCache && self::$cachePath) {
        $cacheFile = self::$cachePath . '/' . md5($cacheKey) . '.php';
        file_put_contents($cacheFile, $compiled, LOCK_EX);

        if (self::$debug) {
          error_log("[Nova] Cached template: {$cacheKey}");
        }
      }
    }

    return $compiled;
  }

  protected static function compileString(string $template, bool $callerContext = false): string
  {
    $output = [];
    $length = strlen($template);
    $position = 0;

    while ($position < $length) {
      if ($template[$position] === '@' && $position + 1 < $length) {
        // Check for block directives first
        if (substr($template, $position, 5) === '@slot') {
          $result = self::parseSlotBlock($template, $position, $callerContext);
          if ($result) {
            $output[] = $result['php'];
            $position = $result['end'];
            continue;
          }
        }

        if (substr($template, $position, 10) === '@component') {
          $result = self::parseComponentBlock($template, $position);
          if ($result) {
            $output[] = $result['php'];
            $position = $result['end'];
            continue;
          }
          // Fall through to parseInlineDirective for self-closing @component
          $result = self::parseInlineComponent($template, $position);
          if ($result) {
            $output[] = $result['php'];
            $position = $result['end'];
            continue;
          }
        }

        // Handle inline directives
        $directive = self::parseInlineDirective($template, $position);
        if ($directive) {
          $output[] = $directive['php'];
          $position = $directive['end'];
          continue;
        }
      }

      // Regular text - find next @
      $nextAt = strpos($template, '@', $position + 1);
      if ($nextAt === false) {
        $output[] = substr($template, $position);
        break;
      }

      $output[] = substr($template, $position, $nextAt - $position);
      $position = $nextAt;
    }

    return implode('', $output);
  }

  protected static function parseSlotBlock(string $template, int $position, bool $callerContext = false): ?array
  {
    $pattern = '/^@slot\(([^)]+)\)/';
    if (!preg_match($pattern, substr($template, $position), $matches)) {
      return null;
    }

    $slotName = trim($matches[1]);
    $startLength = strlen($matches[0]);
    $contentStart = $position + $startLength;

    $endPos = self::findMatchingEndDirective($template, $contentStart, '@endslot');
    if ($endPos === false) {
      return null;
    }

    $content = substr($template, $contentStart, $endPos - $contentStart);
    $compiledContent = self::compileString($content);
    $slotNamePhp = "'" . addslashes(trim($slotName, " '\"")) . "'";

    if ($callerContext) {
      // Caller context: just capture the slot content
      $php = "<?php \\Luxid\\Nova\\Slot::start({$slotNamePhp}); ?>" .
        $compiledContent .
        "<?php \\Luxid\\Nova\\Slot::end(); ?>";
    } else {
      // Component view context: capture default content and render
      $php = "<?php \\Luxid\\Nova\\Slot::start({$slotNamePhp}); ?>" .
        $compiledContent .
        "<?php \\Luxid\\Nova\\Slot::end(); ?>" .
        "<?php echo \\Luxid\\Nova\\Slot::render({$slotNamePhp}); ?>";
    }

    return [
      'php' => $php,
      'end' => $endPos + strlen('@endslot')
    ];
  }

  protected static function parseComponentBlock(string $template, int $position): ?array
  {
    $pattern = '/^@component\(([^,]+)(?:,\s*\[(.*)\])?\)/';
    if (!preg_match($pattern, substr($template, $position), $matches)) {
      return null;
    }

    $componentName = trim($matches[1], '\'"');
    $props = isset($matches[2]) ? trim($matches[2]) : '[]';
    if ($props !== '[]') {
      $props = '[' . $props . ']';
    }

    $startLength = strlen($matches[0]);
    $contentStart = $position + $startLength;

    $endPos = self::findMatchingEndDirective($template, $contentStart, '@endcomponent');
    if ($endPos === false) {
      return null;
    }

    $content = substr($template, $contentStart, $endPos - $contentStart);
    $compiledContent = self::compileString($content, true);

    $php = $compiledContent .
      "<?php echo \$component->renderComponent({$matches[1]}, {$props}); ?>";

    return [
      'php' => $php,
      'end' => $endPos + strlen('@endcomponent')
    ];
  }

  protected static function parseInlineComponent(string $template, int $position): ?array
  {
    $pattern = '/^@component\(([^,]+)(?:,\s*(\[.*?\]))?\)/s';
    if (!preg_match($pattern, substr($template, $position), $matches)) {
      return null;
    }

    $props = isset($matches[2]) ? trim($matches[2]) : '[]';
    $componentName = $matches[1];

    $php = "<?php echo \$component->renderComponent({$componentName}, {$props}); ?>";
    $end = $position + strlen($matches[0]);

    return ['php' => $php, 'end' => $end];
  }

  protected static function findMatchingEndDirective(string $template, int $startPos, string $endDirective): int|false
  {
    if ($endDirective === '@endslot') {
      $pos = strpos($template, '@endslot', $startPos);
      return $pos !== false ? $pos : false;
    }

    if ($endDirective === '@endcomponent') {
      $pos = $startPos;
      $length = strlen($template);
      $componentLen = strlen('@component');
      $endcomponentLen = strlen('@endcomponent');
      $slotLen = strlen('@slot');
      $endslotLen = strlen('@endslot');

      while ($pos < $length) {
        if (substr($template, $pos, $endcomponentLen) === '@endcomponent') {
          return $pos;
        }

        // Skip over @slot blocks entirely
        if (substr($template, $pos, $slotLen) === '@slot') {
          $afterSlotTag = $pos + $slotLen;
          $slotEnd = strpos($template, '@endslot', $afterSlotTag);
          if ($slotEnd !== false) {
            $pos = $slotEnd + $endslotLen;
            continue;
          }
          return false;
        }

        // Skip over nested component blocks
        if (substr($template, $pos, $componentLen) === '@component') {
          $afterOpening = $pos + $componentLen;
          $nestedEnd = self::findMatchingEndDirective($template, $afterOpening, '@endcomponent');
          if ($nestedEnd !== false) {
            $pos = $nestedEnd + $endcomponentLen;
          } else {
            $pos = $afterOpening;
          }
          continue;
        }

        $pos++;
      }

      return false;
    }

    return false;
  }

  protected static function parseInlineDirective(string $template, int $position): ?array
  {
    // @echo
    if (preg_match('/^@echo\(/', substr($template, $position))) {
      $pattern = '/^@echo\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/';
      if (preg_match($pattern, substr($template, $position), $matches)) {
        $expression = trim($matches[1]);
        $php = "<?php echo htmlspecialchars({$expression}, ENT_QUOTES, 'UTF-8'); ?>";
        $end = $position + strlen($matches[0]);
        return ['php' => $php, 'end' => $end];
      }
    }

    // @if
    if (preg_match('/^@if\(/', substr($template, $position))) {
      $pattern = '/^@if\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/';
      if (preg_match($pattern, substr($template, $position), $matches)) {
        $condition = trim($matches[1]);
        $php = "<?php if ({$condition}): ?>";
        $end = $position + strlen($matches[0]);
        return ['php' => $php, 'end' => $end];
      }
    }

    // @elseif
    if (preg_match('/^@elseif\(/', substr($template, $position))) {
      $pattern = '/^@elseif\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/';
      if (preg_match($pattern, substr($template, $position), $matches)) {
        $condition = trim($matches[1]);
        $php = "<?php elseif ({$condition}): ?>";
        $end = $position + strlen($matches[0]);
        return ['php' => $php, 'end' => $end];
      }
    }

    // @else
    if (substr($template, $position, 5) === '@else') {
      return [
        'php' => '<?php else: ?>',
        'end' => $position + 5
      ];
    }

    // @endif
    if (substr($template, $position, 6) === '@endif') {
      return [
        'php' => '<?php endif; ?>',
        'end' => $position + 6
      ];
    }

    // @foreach
    if (preg_match('/^@foreach\(/', substr($template, $position))) {
      $pattern = '/^@foreach\(([^)]+)\)/';
      if (preg_match($pattern, substr($template, $position), $matches)) {
        $expression = trim($matches[1]);
        $php = "<?php foreach ({$expression}): ?>";
        $end = $position + strlen($matches[0]);
        return ['php' => $php, 'end' => $end];
      }
    }

    // @endforeach
    if (substr($template, $position, 11) === '@endforeach') {
      return [
        'php' => '<?php endforeach; ?>',
        'end' => $position + 11
      ];
    }

    // @for
    if (preg_match('/^@for\(/', substr($template, $position))) {
      $pattern = '/^@for\(([^)]+)\)/';
      if (preg_match($pattern, substr($template, $position), $matches)) {
        $expression = trim($matches[1]);
        $php = "<?php for ({$expression}): ?>";
        $end = $position + strlen($matches[0]);
        return ['php' => $php, 'end' => $end];
      }
    }

    // @endfor
    if (substr($template, $position, 7) === '@endfor') {
      return [
        'php' => '<?php endfor; ?>',
        'end' => $position + 7
      ];
    }

    return null;
  }

  public static function clearCache(): void
  {
    self::$cache = [];

    if (self::$useFileCache && self::$cachePath && is_dir(self::$cachePath)) {
      $files = glob(self::$cachePath . '/*.php');
      foreach ($files as $file) {
        unlink($file);
      }

      if (self::$debug) {
        error_log("[Nova] Cleared template cache: " . count($files) . " files");
      }
    }
  }

  public static function getCacheStats(): array
  {
    $stats = [
      'memory_cache_count' => count(self::$cache),
      'file_cache_enabled' => self::$useFileCache,
      'file_cache_path' => self::$cachePath,
    ];

    if (self::$useFileCache && self::$cachePath && is_dir(self::$cachePath)) {
      $files = glob(self::$cachePath . '/*.php');
      $stats['file_cache_count'] = count($files);
      $stats['file_cache_size'] = 0;

      foreach ($files as $file) {
        $stats['file_cache_size'] += filesize($file);
      }
    }

    return $stats;
  }
}
