<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit659c1e4b6022163ecfbc6d3cd5a9cdcc
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInit659c1e4b6022163ecfbc6d3cd5a9cdcc', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit659c1e4b6022163ecfbc6d3cd5a9cdcc', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit659c1e4b6022163ecfbc6d3cd5a9cdcc::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
