<?php
class Autoloader
{
    private $autoloadNamespaces = [];

    /**
     * Register a namespace with its corresponding directories or files.
     *
     * @param string $namespace The namespace to register.
     * @param array $baseDirs List of base directories or PHP files for this namespace.
     */
    public function registerNamespace(string $namespace, array $baseDirs)
    {
        $this->autoloadNamespaces[$namespace] = $baseDirs;
    }

    /**
     * Autoload classes and include PHP files containing functions.
     *
     * @param string $class The fully-qualified class name.
     */
    public function autoload($class)
    {
        foreach ($this->autoloadNamespaces as $namespace => $baseDirs) {
            foreach ($baseDirs as $baseDir) {
                if (strpos($class, $namespace) === 0) {
                    if (is_file($baseDir) && str_ends_with($baseDir, '.php')) {
                        $this->includeFunctionFile($baseDir);
                    } else {
                        $relativeClass = substr($class, strlen($namespace));
                        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                        if (file_exists($file)) {
                            require_once $file;
                        }
                    }
                }
            }
        }
    }

    /**
     * Include a PHP file containing functions.
     *
     * @param string $filePath The path to the PHP file.
     */
    private function includeFunctionFile(string $filePath)
    {
        if (file_exists($filePath)) {
            include_once $filePath;
        }
    }
}

// Instantiate the autoloader
$autoloader = new Autoloader();

// Register namespaces with directories or specific function files
$autoloader->registerNamespace('Vanderbilt\\HarmonistHubExternalModule\\', [
    __DIR__ . '/classes/', // Directory containing classes
    __DIR__ . '/email.php', // File containing functions
    __DIR__ . '/functions.php' // File containing functions
]);

// Register the autoloader with PHP's SPL autoloader
spl_autoload_register([$autoloader, 'autoload']);
