<?php
date_default_timezone_set('UTC');
define('BASE_PATH', __DIR__);
require_once __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();

$app->register(new \Knp\Provider\ConsoleServiceProvider(), [
  'console.name'              => 'Shift locals CSV importer exporter',
  'console.version'           => '0.1.0',
  'console.project_directory' => __DIR__ . '/bin',
]);

return $app;