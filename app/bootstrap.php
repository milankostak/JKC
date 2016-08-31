<?php

// Load Nette Framework
require __DIR__ . "/../libs/autoload.php";

// Configure application
$configurator = new Nette\Config\Configurator;

// Enable Nette Debugger for error visualisation & logging
//$configurator->setDebugMode("23.75.345.200"); // enable for your remote IP
//$configurator->setDebugMode(false);
$configurator->enableDebugger(__DIR__ . "/../log");
          
// Specify folder for cache
$configurator->setTempDirectory(__DIR__ . "/../temp");

// Enable RobotLoader - this will load all classes automatically
$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

// Create Dependency Injection container from config.neon file
$configurator->addConfig(__DIR__ . "/config/config.neon");

//require __DIR__ . "/../libs/Nette/Nella/Callback.php";
//\Addons\Panels\Callback::register($container);

$container = $configurator->createContainer();

return $container;
