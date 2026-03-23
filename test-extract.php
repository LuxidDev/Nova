<?php
$instanceId = "working-counter_69c12b8317fdf";
$parts = explode('_', $instanceId);
array_pop($parts);
$componentName = implode('_', $parts);
echo "Instance ID: $instanceId\n";
echo "Component name: $componentName\n";
