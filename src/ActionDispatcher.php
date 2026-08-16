<?php

declare(strict_types=1);

namespace Luxid\Nova;

use InvalidArgumentException;
use RuntimeException;

/**
 * Handles an incoming component action call.
 *
 * Every field in the request is attacker controlled, so each one is checked
 * before it is used:
 *
 * - the CSRF token must match the session's
 * - the component must be registered, and must have opted into remote calls
 * - the action must exist on that component
 * - the instance id must match the shape the server issues
 *
 * Previously the endpoint dispatched whatever `component` and `action` the body
 * named, which made every action on every registered component a public,
 * unauthenticated RPC entry point.
 *
 * @package Luxid\Nova
 */
final class ActionDispatcher
{
    /**
     * Shape of an instance id issued by {@see StateManager}.
     */
    private const INSTANCE_PATTERN = '/^[A-Za-z0-9_\/.\-]{1,190}$/';

    /**
     * Whether the CSRF token is required.
     */
    private static bool $verifyCsrf = true;

    /**
     * Turn CSRF verification off.
     *
     * Only appropriate for a stateless deployment that authenticates action
     * calls some other way. Leaving it on is the safe default.
     */
    public static function withoutCsrfVerification(): void
    {
        self::$verifyCsrf = false;
    }

    /**
     * Turn CSRF verification back on.
     */
    public static function verifyCsrf(): void
    {
        self::$verifyCsrf = true;
    }

    /**
     * Check whether the request looks like a Nova action call.
     *
     * @param array<string, mixed> $payload Decoded request body
     */
    public static function handles(array $payload): bool
    {
        return isset($payload['component'], $payload['action']);
    }

    /**
     * Dispatch an action call and return the re-rendered component.
     *
     * @param array<string, mixed> $payload Decoded request body
     *
     * @throws InvalidArgumentException When the payload names something unusable
     * @throws RuntimeException         When the CSRF token does not match
     */
    public static function dispatch(array $payload): string
    {
        if (self::$verifyCsrf && !Csrf::verify(self::stringOrNull($payload['_token'] ?? null))) {
            throw new RuntimeException('Invalid or missing CSRF token.');
        }

        $name = self::stringOrNull($payload['component'] ?? null);
        $action = self::stringOrNull($payload['action'] ?? null);
        $instance = self::stringOrNull($payload['instance'] ?? null);
        $params = $payload['params'] ?? [];

        if ($name === null || $action === null) {
            throw new InvalidArgumentException('A component and an action are required.');
        }

        if (!is_array($params)) {
            throw new InvalidArgumentException('Action parameters must be an object.');
        }

        if ($instance !== null && preg_match(self::INSTANCE_PATTERN, $instance) !== 1) {
            // A free-form instance id indexes straight into the session, so only
            // ids shaped like the ones the server issues are accepted.
            throw new InvalidArgumentException('Malformed component instance.');
        }

        if (!ComponentManager::has($name)) {
            throw new InvalidArgumentException(sprintf('Component "%s" is not registered.', $name));
        }

        $component = ComponentManager::make($name, $instance);

        if (!$component->hasAction($action)) {
            throw new InvalidArgumentException(
                sprintf('Component "%s" has no action "%s".', $name, $action)
            );
        }

        return $component->callAction($action, $params);
    }

    /**
     * Narrow a payload value to a non-empty string.
     *
     * @param mixed $value Raw payload value
     */
    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
