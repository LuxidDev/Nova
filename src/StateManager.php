<?php

declare(strict_types=1);

namespace Luxid\Nova;

/**
 * Per-instance component state, persisted in the session.
 *
 * Every rendered component instance keeps a slot in the session. Without a
 * ceiling that grows for the lifetime of the session — one entry per component
 * per page view — so the oldest slots are evicted once the cap is reached.
 *
 * @package Luxid\Nova
 */
class StateManager
{
    /**
     * Session key prefix for component state.
     */
    private const SESSION_PREFIX = 'nova_state_';

    /**
     * Session key holding the eviction order of stored instances.
     */
    private const INDEX_KEY = 'nova_state_index';

    /**
     * Maximum number of component instances kept per session.
     */
    private const MAX_INSTANCES = 200;

    /**
     * Characters permitted in an instance id.
     */
    private const INSTANCE_PATTERN = '/^[A-Za-z0-9_\/.\-]{1,190}$/';

    /**
     * Identifier for this component instance.
     */
    private string $instanceId;

    /**
     * Name of the component this state belongs to.
     */
    private string $componentName;

    /**
     * The stored state.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * Whether the state has changed since it was loaded.
     */
    private bool $isDirty = false;

    /**
     * @param string      $componentName Component name
     * @param string|null $instanceId    Instance id, or null to mint one
     */
    public function __construct(string $componentName, ?string $instanceId = null)
    {
        $this->componentName = $componentName;
        $this->instanceId = $this->normalizeInstanceId($instanceId);

        $this->load();
    }

    /**
     * Accept a caller-supplied instance id, or mint a fresh one.
     *
     * Ids reach the session key directly, so anything not shaped like an id the
     * server issues is replaced rather than trusted.
     *
     * @param string|null $instanceId Candidate instance id
     */
    private function normalizeInstanceId(?string $instanceId): string
    {
        if ($instanceId !== null && preg_match(self::INSTANCE_PATTERN, $instanceId) === 1) {
            return $instanceId;
        }

        return $this->componentName . '_' . bin2hex(random_bytes(8));
    }

    /**
     * Get the session key holding this instance's state.
     */
    private function getSessionKey(): string
    {
        return self::SESSION_PREFIX . $this->componentName . '_' . $this->instanceId;
    }

    /**
     * Start the session if the SAPI allows it.
     */
    private function bootSession(): void
    {
        if (PHP_SAPI === 'cli' || headers_sent()) {
            return;
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Load this instance's state from the session.
     */
    protected function load(): void
    {
        $this->bootSession();

        $stored = $_SESSION[$this->getSessionKey()] ?? [];
        $this->data = is_array($stored) ? $stored : [];
    }

    /**
     * Write this instance's state back to the session.
     */
    protected function save(): void
    {
        if (!$this->isDirty) {
            return;
        }

        $this->bootSession();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $key = $this->getSessionKey();
        $_SESSION[$key] = $this->data;
        $this->isDirty = false;

        $this->touchIndex($key);
    }

    /**
     * Record this instance as most recently used and evict the oldest.
     *
     * @param string $key Session key just written
     */
    private function touchIndex(string $key): void
    {
        $index = $_SESSION[self::INDEX_KEY] ?? [];
        $index = is_array($index) ? $index : [];

        // Re-appending moves the key to the end, making the array an LRU list.
        unset($index[$key]);
        $index[$key] = true;

        while (count($index) > self::MAX_INSTANCES) {
            $oldest = array_key_first($index);
            unset($index[$oldest], $_SESSION[$oldest]);
        }

        $_SESSION[self::INDEX_KEY] = $index;
    }

    /**
     * Flush pending changes to the session immediately.
     */
    public function flush(): void
    {
        $this->save();
    }

    /**
     * Read a state value.
     *
     * @param string $key     State key
     * @param mixed  $default Value returned when the key is absent
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Write a state value.
     *
     * @param string $key   State key
     * @param mixed  $value Value to store
     */
    public function set(string $key, mixed $value): self
    {
        $this->data[$key] = $value;
        $this->isDirty = true;

        return $this;
    }

    /**
     * Check whether a state key is set.
     *
     * @param string $key State key
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a state key.
     *
     * @param string $key State key
     */
    public function remove(string $key): self
    {
        unset($this->data[$key]);
        $this->isDirty = true;

        return $this;
    }

    /**
     * Get the whole state.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Seed the state, but only when it is still empty.
     *
     * @param array<string, mixed> $defaults Initial state
     */
    public function initialize(array $defaults): self
    {
        if ($this->data === []) {
            $this->data = $defaults;
            $this->isDirty = true;
        }

        return $this;
    }

    /**
     * Get this instance's identifier.
     */
    public function getInstanceId(): string
    {
        return $this->instanceId;
    }

    /**
     * Drop every component state slot in the session.
     */
    public static function purge(): void
    {
        foreach (array_keys($_SESSION ?? []) as $key) {
            if (str_starts_with((string) $key, self::SESSION_PREFIX)) {
                unset($_SESSION[$key]);
            }
        }

        unset($_SESSION[self::INDEX_KEY]);
    }

    /**
     * Persist pending changes as the request winds down.
     */
    public function __destruct()
    {
        $this->save();
    }

    /**
     * Read a state value as a property.
     *
     * @param string $name State key
     */
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }

    /**
     * Write a state value as a property.
     *
     * @param string $name  State key
     * @param mixed  $value Value to store
     */
    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value);
    }

    /**
     * Check a state key as a property.
     *
     * @param string $name State key
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Remove a state key as a property.
     *
     * @param string $name State key
     */
    public function __unset(string $name): void
    {
        $this->remove($name);
    }
}
