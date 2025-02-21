<?php

// Define a mapping of namespaces to base directories
$autoloadNamespaces = [
    'Vanderbilt\\HarmonistHubExternalModule\\' => __DIR__ . '/classes/',
    // Add more namespaces and paths as needed
];

// The autoloader function
spl_autoload_register(function ($class) use ($autoloadNamespaces) {
    // Iterate over the namespace to directory mapping
    foreach ($autoloadNamespaces as $namespace => $baseDir) {
        // Check if the class name starts with the namespace
        if (strpos($class, $namespace) === 0) {
            // Remove the namespace prefix from the class name
            $relativeClass = substr($class, strlen($namespace));

            // Convert namespace separators to directory separators
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            // If the file exists, require it
            if (file_exists($file)) {
                require $file;
            }

            // Exit the function if the class file was found and included
            return;
        }
    }
});
