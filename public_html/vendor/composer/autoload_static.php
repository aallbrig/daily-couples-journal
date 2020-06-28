<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf8cfd06863ab7e8e1a185ab540ce9e4a
{
    public static $prefixLengthsPsr4 = array (
        'V' => 
        array (
            'Valitron\\' => 9,
        ),
        'T' => 
        array (
            'Twilio\\' => 7,
        ),
        'S' => 
        array (
            'Stripe\\' => 7,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Valitron\\' => 
        array (
            0 => __DIR__ . '/..' . '/vlucas/valitron/src/Valitron',
        ),
        'Twilio\\' => 
        array (
            0 => __DIR__ . '/..' . '/twilio/sdk/src/Twilio',
        ),
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf8cfd06863ab7e8e1a185ab540ce9e4a::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf8cfd06863ab7e8e1a185ab540ce9e4a::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
