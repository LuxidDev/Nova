<?php

declare(strict_types=1);

/**
 * Standalone Nova bootstrap.
 *
 * Boots the component registry, answers component action calls and serves the
 * client runtime. Applications built on the full framework do not need this
 * file — the engine's front controller covers the same ground — but it lets
 * Nova run on its own.
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/components.php';

use Luxid\Nova\ActionDispatcher;
use Luxid\Nova\ComponentCache;
use Luxid\Nova\Compiler;
use Luxid\Nova\Performance;

// Errors are shown only when the environment asks for it. Displaying them
// unconditionally leaks paths, queries and stack traces to anyone who can
// trigger one.
$novaDebug = filter_var($_ENV['NOVA_DEBUG'] ?? getenv('NOVA_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL);
error_reporting($novaDebug ? E_ALL : E_ALL & ~E_DEPRECATED);
ini_set('display_errors', $novaDebug ? '1' : '0');

$config = is_file(__DIR__ . '/config/nova.php') ? require __DIR__ . '/config/nova.php' : [];

if ($config['cache']['enabled'] ?? false) {
    Compiler::setCachePath($config['cache']['path']);
    Compiler::enableDebug($config['cache']['debug'] ?? false);
}

if ($config['component_cache']['enabled'] ?? false) {
    ComponentCache::enable($config['component_cache']['path']);
}

if ($config['performance']['enabled'] ?? false) {
    Performance::enable();
}

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}

$requestUri = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

// Serve the client runtime.
$runtimeAssets = [
    '/nova.js' => __DIR__ . '/public/nova.js',
    '/nova-alpine.js' => __DIR__ . '/public/nova-alpine.js',
];

if (isset($runtimeAssets[$requestUri])) {
    header('Content-Type: application/javascript; charset=utf-8');
    header('Cache-Control: public, max-age=3600');

    if (is_file($runtimeAssets[$requestUri])) {
        readfile($runtimeAssets[$requestUri]);
    } else {
        http_response_code(404);
        echo '// Not found';
    }

    exit;
}

// Answer component action calls.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $payload = json_decode((string) file_get_contents('php://input'), true);

    if (!is_array($payload)) {
        // Fall back to a plain form post, mapping its reserved fields.
        $payload = [
            'component' => $_POST['_component'] ?? null,
            'action' => $_POST['_action'] ?? null,
            'instance' => $_POST['_instance'] ?? null,
            '_token' => $_POST['_token'] ?? null,
            'params' => array_diff_key($_POST, array_flip(['_component', '_action', '_instance', '_token'])),
        ];
    }

    if (ActionDispatcher::handles($payload)) {
        header('Content-Type: text/html; charset=utf-8');

        try {
            echo ActionDispatcher::dispatch($payload);
        } catch (RuntimeException $e) {
            http_response_code(403);
            echo '<div class="nova-error">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        } catch (InvalidArgumentException $e) {
            http_response_code(400);
            echo '<div class="nova-error">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</div>';
        } catch (Throwable $e) {
            http_response_code(500);
            // The real reason goes to the log, not to the client.
            error_log('[Nova] Action failed: ' . $e->getMessage());
            echo '<div class="nova-error">The action could not be completed.</div>';
        }

        exit;
    }
}
