<?php
//Autoloader for User classes
require __DIR__ . '/../vendor/autoload.php';
spl_autoload_register(function($class) {
	$parts = explode('\\', ltrim($class, '\\'));
	if ($parts[0] === 'MaphperLoader') {
		array_shift($parts);
		require_once 'src/' . implode(DIRECTORY_SEPARATOR, $parts) . '.php';
	}
});
