<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInit1d2f0e603eb4138c600f95a97a9c4d20
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

        spl_autoload_register(array('ComposerAutoloaderInit1d2f0e603eb4138c600f95a97a9c4d20', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInit1d2f0e603eb4138c600f95a97a9c4d20', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInit1d2f0e603eb4138c600f95a97a9c4d20::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}
