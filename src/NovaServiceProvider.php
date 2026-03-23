<?php

namespace Luxid\Nova;

class NovaServiceProvider
{
  protected array $config;

  public function __construct(array $config = [])
  {
    $this->config = $config;
  }

  public function register(): void
  {
    // Configure compiler cache
    if ($this->config['cache']['enabled'] ?? false) {
      Compiler::setCachePath($this->config['cache']['path']);
      Compiler::enableDebug($this->config['cache']['debug'] ?? false);
    }

    // Configure component cache
    if ($this->config['component_cache']['enabled'] ?? false) {
      ComponentCache::enable($this->config['component_cache']['path']);
    }

    // Configure performance monitoring
    if ($this->config['performance']['enabled'] ?? false) {
      Performance::enable();
    }
  }

  public function boot(): void
  {
    // Boot logic 
  }

  protected function loadComponentsFrom(string $path): void
  {
    if (is_dir($path)) {
      $files = glob($path . '/*.php');
      foreach ($files as $file) {
        require_once $file;
      }
    }
  }
}
