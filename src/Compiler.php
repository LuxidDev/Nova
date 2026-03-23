<?php

namespace Luxid\Nova;

class Compiler
{
  protected static array $cache = [];
  protected static ?string $cachePath = null;
  protected static bool $useFileCache = false;

  public static function setCachePath(string $path): void
  {
    self::$cachePath = rtrim($path, '/');
    self::$useFileCache = true;
    if (!is_dir(self::$cachePath)) {
      mkdir(self::$cachePath, 0755, true);
    }
  }

  public static function compile(string $template, ?string $cacheKey = null): string
  {
    if ($cacheKey && isset(self::$cache[$cacheKey])) {
      return self::$cache[$cacheKey];
    }

    if ($cacheKey && self::$useFileCache && self::$cachePath) {
      $cacheFile = self::$cachePath . '/' . md5($cacheKey) . '.php';
      if (file_exists($cacheFile)) {
        $compiled = file_get_contents($cacheFile);
        self::$cache[$cacheKey] = $compiled;
        return $compiled;
      }
    }

    $compiled = self::compileString($template);

    if ($cacheKey) {
      self::$cache[$cacheKey] = $compiled;
      if (self::$useFileCache && self::$cachePath) {
        file_put_contents($cacheFile, $compiled);
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
    error_log("parseSlotBlock called at position $position");

    // Parse @slot('name')
    $pattern = '/^@slot\(([^)]+)\)/';
    if (!preg_match($pattern, substr($template, $position), $matches)) {
      error_log("  No match for @slot pattern");
      return null;
    }

    $slotName = trim($matches[1]);
    error_log("  Found slot: $slotName");

    $startLength = strlen($matches[0]);
    $contentStart = $position + $startLength;
    error_log("  Content starts at $contentStart");

    // Find matching @endslot
    $endPos = self::findMatchingEndDirective($template, $contentStart, '@endslot');
    if ($endPos === false) {
      error_log("  Could not find matching @endslot");
      return null;
    }

    error_log("  Found @endslot at $endPos");

    $content = substr($template, $contentStart, $endPos - $contentStart);
    error_log("  Content length: " . strlen($content));
    error_log("  Content preview: " . substr($content, 0, 100));

    // Inner content of a caller-context slot may itself contain components,
    // but NOT more slot injections at this level — compile normally.
    $compiledContent = self::compileString($content);
    error_log("  Compiled content length: " . strlen($compiledContent));

    $slotNamePhp = "'" . addslashes(trim($slotName, " '\"")) . "'";

    if ($callerContext) {
      // Caller context: just capture the slot content into the registry.
      // No render() — the component's own view will call render() when it
      // processes its own @slot('name')..@endslot default definition.
      $php = "<?php \\Luxid\\Nova\\Slot::start({$slotNamePhp}); ?>" .
        $compiledContent .
        "<?php \\Luxid\\Nova\\Slot::end(); ?>";
    } else {
      // Component view context: capture the default content, then render —
      // outputs injected content if frozen, or the default if not.
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
    error_log("parseComponentBlock called at position $position");

    // Parse @component('name', ['prop' => 'value'])
    $pattern = '/^@component\(([^,]+)(?:,\s*\[(.*)\])?\)/';
    if (!preg_match($pattern, substr($template, $position), $matches)) {
      error_log("  No match for @component pattern");
      return null;
    }

    $componentName = trim($matches[1], '\'"');
    $props = isset($matches[2]) ? trim($matches[2]) : '[]';
    error_log("  Found component: $componentName");

    if ($props !== '[]') {
      $props = '[' . $props . ']';
    }

    $startLength = strlen($matches[0]);
    $contentStart = $position + $startLength;
    error_log("  Content starts at $contentStart");

    // Find matching @endcomponent — may be absent for self-closing usage
    $endPos = self::findMatchingEndDirective($template, $contentStart, '@endcomponent');
    if ($endPos === false) {
      // Not a block component — caller will try parseInlineComponent instead
      return null;
    }

    error_log("  Found @endcomponent at $endPos");

    // Extract and compile content (this becomes the default slot)
    $content = substr($template, $contentStart, $endPos - $contentStart);
    error_log("  Content length: " . strlen($content));
    error_log("  Content preview: " . substr($content, 0, 100));

    // Compile the content between @component and @endcomponent in "caller
    // context" — @slot blocks inside are injections, not default definitions,
    // so they emit Slot::start/end only (no render()).  The compiled output
    // runs immediately before renderComponent(), populating $slots so that
    // freeze() inside renderComponent() captures the right values.
    $compiledContent = self::compileString($content, true);
    error_log("  Compiled content length: " . strlen($compiledContent));

    $php = $compiledContent .
      "<?php echo \$component->renderComponent({$matches[1]}, {$props}); ?>";

    return [
      'php' => $php,
      // FIX: @endcomponent is 13 characters, not 12
      'end' => $endPos + strlen('@endcomponent')
    ];
  }

  protected static function findMatchingEndDirective(string $template, int $startPos, string $endDirective): int|false
  {
    // Slots are never nested — just find the very next @endslot.
    if ($endDirective === '@endslot') {
      $pos = strpos($template, '@endslot', $startPos);
      return $pos !== false ? $pos : false;
    }

    if ($endDirective === '@endcomponent') {
      $pos = $startPos;
      $length = strlen($template);
      $componentLen  = strlen('@component');
      $endcomponentLen = strlen('@endcomponent');
      $slotLen       = strlen('@slot');
      $endslotLen    = strlen('@endslot');

      while ($pos < $length) {
        // @endcomponent check must come before @component check so we don't
        // match "@component" as a prefix of "@endcomponent".
        if (substr($template, $pos, $endcomponentLen) === '@endcomponent') {
          return $pos;
        }

        // Skip over @slot('name')...@endslot blocks entirely.
        // A @component tag that appears inside a @slot block belongs to that
        // slot's content — it must NOT affect our depth tracking.
        if (substr($template, $pos, $slotLen) === '@slot') {
          $afterSlotTag = $pos + $slotLen;
          // Find the matching @endslot for this @slot
          $slotEnd = strpos($template, '@endslot', $afterSlotTag);
          if ($slotEnd !== false) {
            // Jump past the entire @slot..@endslot block
            $pos = $slotEnd + $endslotLen;
            continue;
          }
          // No @endslot found — malformed template, give up
          return false;
        }

        // Skip over nested block @component...@endcomponent pairs.
        // Self-closing @component (no @endcomponent) just advances past the tag.
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

  /**
   * Handle @component('name', [...]) with no @endcomponent — renders with no
   * slots (or only the default empty slot).
   */
  protected static function parseInlineComponent(string $template, int $position): ?array
  {
    $pattern = '/^@component\(([^,]+)(?:,\s*(\[.*?\]))?\)/s';
    if (!preg_match($pattern, substr($template, $position), $matches)) {
      return null;
    }

    $props = isset($matches[2]) ? trim($matches[2]) : '[]';
    $componentName = $matches[1]; // keep quotes intact for renderComponent call

    $php = "<?php echo \$component->renderComponent({$componentName}, {$props}); ?>";
    $end = $position + strlen($matches[0]);

    return ['php' => $php, 'end' => $end];
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
    }
  }
}
