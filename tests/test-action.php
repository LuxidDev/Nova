<?php

require_once dirname(__DIR__) . '/bootstrap.php';

// Render the component
$instanceId = 'test-counter_' . uniqid();
echo nova('test-counter', ['_instance' => $instanceId]);
