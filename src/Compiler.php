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

  protected static function compileString(string $template): string
  {
    $output = [];
    $length = strlen($template);
    $position = 0;
    $inPhpTag = false;

    while ($position < $length) {
      // Look for @ directives
      if ($template[$position] === '@' && $position + 1 < $length) {
        $directive = self::parseDirective($template, $position);
        if ($directive) {
          $output[] = $directive['php'];
          $position = $directive['end'];
          continue;
        }
      }

      // Regular text - find next @ or end
      $nextAt = strpos($template, '@', $position);
      if ($nextAt === false) {
        // No more directives, output remaining text
        $text = substr($template, $position);
        $output[] = self::escapeText($text);
        break;
      }

      // Output text before next directive
      $text = substr($template, $position, $nextAt - $position);
      $output[] = self::escapeText($text);
      $position = $nextAt;
    }

    return implode('', $output);
  }

  protected static function parseDirective(string $template, int $position): ?array
  {
    // Check for @endforeach (11 chars)
    if (substr($template, $position, 11) === '@endforeach') {
      $next = $position + 11;
      return [
        'php' => '<?php endforeach; ?>',
        'end' => $next
      ];
    }

    // Check for @endfor (7 chars)
    if (substr($template, $position, 7) === '@endfor') {
      $next = $position + 7;
      return [
        'php' => '<?php endfor; ?>',
        'end' => $next
      ];
    }

    // Check for @endif (6 chars)
    if (substr($template, $position, 6) === '@endif') {
      $next = $position + 6;
      return [
        'php' => '<?php endif; ?>',
        'end' => $next
      ];
    }

    // Check for @else (5 chars)
    if (substr($template, $position, 5) === '@else') {
      $next = $position + 5;
      return [
        'php' => '<?php else: ?>',
        'end' => $next
      ];
    }

    // Check for @echo
    if (preg_match('/^@echo\(/', substr($template, $position))) {
      return self::parseEchoDirective($template, $position);
    }

    // Check for @if
    if (preg_match('/^@if\(/', substr($template, $position))) {
      return self::parseIfDirective($template, $position);
    }

    // Check for @elseif
    if (preg_match('/^@elseif\(/', substr($template, $position))) {
      return self::parseElseIfDirective($template, $position);
    }

    // Check for @foreach (must come before @for)
    if (preg_match('/^@foreach\(/', substr($template, $position))) {
      return self::parseForEachDirective($template, $position);
    }

    // Check for @for
    if (preg_match('/^@for\(/', substr($template, $position))) {
      return self::parseForDirective($template, $position);
    }

    return null;
  }

  protected static function parseEchoDirective(string $template, int $position): ?array
  {
    $pattern = '/^@echo\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/';
    if (preg_match($pattern, substr($template, $position), $matches)) {
      $expression = trim($matches[1]);
      $php = "<?php echo htmlspecialchars({$expression}, ENT_QUOTES, 'UTF-8'); ?>";
      $end = $position + strlen($matches[0]);
      return ['php' => $php, 'end' => $end];
    }
    return null;
  }

  protected static function parseIfDirective(string $template, int $position): ?array
  {
    $pattern = '/^@if\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/';
    if (preg_match($pattern, substr($template, $position), $matches)) {
      $condition = trim($matches[1]);
      $php = "<?php if ({$condition}): ?>";
      $end = $position + strlen($matches[0]);
      return ['php' => $php, 'end' => $end];
    }
    return null;
  }

  protected static function parseElseIfDirective(string $template, int $position): ?array
  {
    $pattern = '/^@elseif\(((?:[^()]+|\((?:[^()]+|\([^()]*\))*\))*)\)/';
    if (preg_match($pattern, substr($template, $position), $matches)) {
      $condition = trim($matches[1]);
      $php = "<?php elseif ({$condition}): ?>";
      $end = $position + strlen($matches[0]);
      return ['php' => $php, 'end' => $end];
    }
    return null;
  }

  protected static function parseForEachDirective(string $template, int $position): ?array
  {
    $pattern = '/^@foreach\(([^)]+)\)/';
    if (preg_match($pattern, substr($template, $position), $matches)) {
      $expression = trim($matches[1]);
      $php = "<?php foreach ({$expression}): ?>";
      $end = $position + strlen($matches[0]);
      return ['php' => $php, 'end' => $end];
    }
    return null;
  }

  protected static function parseForDirective(string $template, int $position): ?array
  {
    $pattern = '/^@for\(([^)]+)\)/';
    if (preg_match($pattern, substr($template, $position), $matches)) {
      $expression = trim($matches[1]);
      $php = "<?php for ({$expression}): ?>";
      $end = $position + strlen($matches[0]);
      return ['php' => $php, 'end' => $end];
    }
    return null;
  }

  protected static function escapeText(string $text): string
  {
    // Text is already raw HTML, just output as is
    return $text;
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
