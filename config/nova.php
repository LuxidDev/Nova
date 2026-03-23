<?php

return [
  // Template caching
  'cache' => [
    'enabled' => true,
    'path' => __DIR__ . '/../storage/framework/nova',
    'debug' => false,
  ],

  // Component caching
  'component_cache' => [
    'enabled' => true,
    'path' => __DIR__ . '/../storage/framework/nova/components',
  ],

  // State management
  'state' => [
    'compression' => true,
    'compression_threshold' => 1024, // bytes
  ],

  // Performance monitoring
  'performance' => [
    'enabled' => false, // Enable in development only
  ],

  // Security
  'security' => [
    'csrf_protection' => true,
    'session_prefix' => 'nova_',
  ],
];
