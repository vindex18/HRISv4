<?php

require_once __DIR__ . '/../vendor/autoload.php';
$user = new \App\Controllers\HomeController; $user->index(); var_dump($user); die();
// Instantiate the app
$app = new \Slim\App(['settings' => require __DIR__ . '/../config/settings.php']);
$container = $app->getContainer();
// Set up dependencies
//require  __DIR__ . '/container.php';

// Register middleware
//require __DIR__ . '/middleware.php';

// Register routes
//require __DIR__ . '/routes.php';

return $app;