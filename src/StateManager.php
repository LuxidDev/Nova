<?php

namespace Luxid\Nova;

class StateManager
{
  private const SESSION_PREFIX = 'nova_state_';

  private string $instanceId;
  private string $componentName;
  private array $data;
  private bool $isDirty = false;

  public function __construct(string $componentName, ?string $instanceId = null)
  {
    $this->componentName = $componentName;
    $this->instanceId = $instanceId ?? $this->generateInstanceId();

    $this->load();
  }

  private function generateInstanceId(): string
  {
    return uniqid($this->componentName . '_', true);
  }

  private function getSessionKey(): string
  {
    return self::SESSION_PREFIX . $this->componentName . '_' . $this->instanceId;
  }

  protected function load(): void
  {
    // Only start session if we're in a web context and headers not sent
    if (php_sapi_name() !== 'cli' && !headers_sent() && session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $key = $this->getSessionKey();
    $this->data = $_SESSION[$key] ?? [];
  }

  protected function save(): void
  {
    if (!$this->isDirty) {
      return;
    }

    // Only save if we're in a web context
    if (php_sapi_name() !== 'cli' && !headers_sent() && session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
      $key = $this->getSessionKey();
      $_SESSION[$key] = $this->data;
      $this->isDirty = false;
    }
  }

  public function get(string $key, $default = null)
  {
    return $this->data[$key] ?? $default;
  }

  public function set(string $key, $value): self
  {
    $this->data[$key] = $value;
    $this->isDirty = true;
    return $this;
  }

  public function has(string $key): bool
  {
    return isset($this->data[$key]);
  }

  public function remove(string $key): self
  {
    unset($this->data[$key]);
    $this->isDirty = true;
    return $this;
  }

  public function all(): array
  {
    return $this->data;
  }

  public function initialize(array $defaults): self
  {
    if (empty($this->data)) {
      $this->data = $defaults;
      $this->isDirty = true;
    }

    return $this;
  }

  public function getInstanceId(): string
  {
    return $this->instanceId;
  }

  public function __destruct()
  {
    $this->save();
  }

  public function __get($name)
  {
    return $this->get($name);
  }

  public function __set($name, $value)
  {
    $this->set($name, $value);
  }

  public function __isset($name)
  {
    return $this->has($name);
  }

  public function __unset($name)
  {
    $this->remove($name);
  }
}
