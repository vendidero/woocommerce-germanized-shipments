<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit22eb139677a2b0a83908c547f6bc1202
{
    public static $prefixLengthsPsr4 = array (
        's' => 
        array (
            'setasign\\Fpdi\\' => 14,
        ),
        'V' => 
        array (
            'Vendidero\\Germanized\\Shipments\\' => 31,
        ),
        'A' => 
        array (
            'Automattic\\Jetpack\\Autoloader\\' => 30,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'setasign\\Fpdi\\' => 
        array (
            0 => __DIR__ . '/..' . '/setasign/fpdi/src',
        ),
        'Vendidero\\Germanized\\Shipments\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
        'Automattic\\Jetpack\\Autoloader\\' => 
        array (
            0 => __DIR__ . '/..' . '/automattic/jetpack-autoloader/src',
        ),
    );

    public static $classMap = array (
        'FPDF' => __DIR__ . '/..' . '/setasign/fpdf/fpdf.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit22eb139677a2b0a83908c547f6bc1202::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit22eb139677a2b0a83908c547f6bc1202::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit22eb139677a2b0a83908c547f6bc1202::$classMap;

        }, null, ClassLoader::class);
    }
}
