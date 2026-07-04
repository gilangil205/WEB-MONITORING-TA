<?php return array (
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nunomaduro/termwind' => 
  array (
    'providers' => 
    array (
      0 => 'Termwind\\Laravel\\TermwindServiceProvider',
    ),
  ),
  'php-mqtt/laravel-client' => 
  array (
    'aliases' => 
    array (
      'MQTT' => 'PhpMqtt\\Client\\Facades\\MQTT',
    ),
    'providers' => 
    array (
      0 => 'PhpMqtt\\Client\\MqttClientServiceProvider',
    ),
  ),
);