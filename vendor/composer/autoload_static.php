<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit2db48c63b999edc69b3306c79676c033
{
    public static $prefixLengthsPsr4 = array (
        'A' => 
        array (
            'App\\' => 4,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'App\\' => 
        array (
            0 => __DIR__ . '/../..' . '/App',
        ),
    );

    public static $prefixesPsr0 = array (
        'B' => 
        array (
            'Bramus' => 
            array (
                0 => __DIR__ . '/..' . '/bramus/router/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit2db48c63b999edc69b3306c79676c033::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit2db48c63b999edc69b3306c79676c033::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit2db48c63b999edc69b3306c79676c033::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit2db48c63b999edc69b3306c79676c033::$classMap;

        }, null, ClassLoader::class);
    }
}
