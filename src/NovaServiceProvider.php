<?php

declare(strict_types=1);

namespace Luxid\Nova;

/**
 * Wires Nova into a Luxid application.
 *
 * Discovered through `extra.luxid.providers`. Nova does not depend on
 * `luxid/engine` and stays usable standalone, so everything touching the engine
 * is guarded by a class check rather than an import.
 *
 * @package Luxid\Nova
 */
class NovaServiceProvider
{
    /**
     * Fully qualified name of the engine's request scope registry.
     */
    private const REQUEST_SCOPE = 'Luxid\\Foundation\\RequestScope';

    /**
     * Configure the compiler and caches.
     *
     * @param object|null $app The booting application, when there is one
     */
    public function register(?object $app = null): void
    {
        $config = $this->config($app);

        if ($config['cache']['enabled'] ?? false) {
            Compiler::setCachePath($this->resolvePath($app, $config['cache']['path'] ?? ''));
            Compiler::enableDebug($config['cache']['debug'] ?? false);
        }

        if ($config['component_cache']['enabled'] ?? false) {
            ComponentCache::enable($this->resolvePath($app, $config['component_cache']['path'] ?? ''));
        }

        if ($config['performance']['enabled'] ?? false) {
            Performance::enable();
        }
    }

    /**
     * Register Nova's per-request state with the engine's reset registry.
     *
     * Slot captures, the in-flight component pointer and collected timings are
     * all request-scoped statics. Under PHP-FPM they vanish with the process;
     * under a worker they would bleed into the next request.
     *
     * @param object|null $app The booting application, when there is one
     */
    public function boot(?object $app = null): void
    {
        if (!class_exists(self::REQUEST_SCOPE)) {
            return;
        }

        $scope = self::REQUEST_SCOPE;

        $scope::onReset(static function (): void {
            Slot::reset();
            ComponentManager::resetRequestState();
            Performance::reset();
        }, 'nova.request-state');
    }

    /**
     * Read Nova's configuration from the application root.
     *
     * @param object|null $app The booting application, when there is one
     *
     * @return array<string, mixed>
     */
    protected function config(?object $app): array
    {
        $root = $this->root($app);

        foreach (['/nova/nova.json', '/config/nova.php'] as $candidate) {
            $path = $root . $candidate;

            if (!is_file($path)) {
                continue;
            }

            $config = str_ends_with($path, '.json')
                ? json_decode((string) file_get_contents($path), true)
                : require $path;

            if (is_array($config)) {
                // nova.json nests compiler settings; config/nova.php does not.
                return $config['compiler'] ?? $config;
            }
        }

        return [];
    }

    /**
     * Resolve a configured path against the application root.
     *
     * @param object|null $app  The booting application, when there is one
     * @param string      $path Configured path, absolute or root-relative
     */
    protected function resolvePath(?object $app, string $path): string
    {
        if ($path === '') {
            return $this->root($app) . '/storage/framework/nova';
        }

        return str_starts_with($path, '/') ? $path : $this->root($app) . '/' . $path;
    }

    /**
     * Get the application root directory.
     *
     * @param object|null $app The booting application, when there is one
     */
    protected function root(?object $app): string
    {
        if ($app !== null && property_exists($app, 'ROOT_DIR')) {
            return (string) $app::$ROOT_DIR;
        }

        return defined('LUXID_ROOT') ? (string) constant('LUXID_ROOT') : getcwd();
    }
}
