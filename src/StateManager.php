<?php

namespace Luxid\Nova;

class StateManager
{
  private const SESSION_PREFIX = 'nova_state_';
  private const COMPRESSION_THRESHOLD = 1024; // Compress if > 1KB

  private string $instanceId;
  private string $componentName;
  private array $data;
  private bool $isDirty = false;
  private bool $userCompression = true;

  public function __construct(string $componentName, ?string $instanceId = null, bool $userCompression = true)
  {
    $this->componentName = $componentName;
    $this->instanceId = $instanceId ?? $this->generateInstanceId();
    $this->userCompression = $userCompression;
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
    if (php_sapi_name() !== 'cli' && !headers_sent() && session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    $key = $this->getSessionKey();

    if (isset($_SESSION[$key])) {
      $data = $_SESSION[$key];

      // Decompress if needed
      if ($this->useCompression && is_string($data) && str_starts_with($data, 'gzcompress:')) {
        $compressed = substr($data, 11);
        $data = unserialize(gzuncompress(base64_decode($compressed)));
      }

      $this->data = $data;
    } else {
      $this->data = [];
    }
  }

  protected function save(): void
  {
    if (!$this->isDirty) {
      return;
    }

    if (php_sapi_name() !== 'cli' && !headers_sent() && session_status() === PHP_SESSION_NONE) {
      session_start();
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
      $key = $this->getSessionKey();
      $data = $this->data;

      // Compress if enabled and data is large
      if ($this->useCompression) {
        $serialized = serialize($data);
        if (strlen($serialized) > self::COMPRESSION_THRESHOLD) {
          $compressed = base64_encode(gzcompress($serialized, 9));
          $data = 'gzcompress:' . $compressed;
        }
      }

      $_SESSION[$key] = $data;
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
