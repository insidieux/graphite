<?php
namespace Graphite\Loader;

/**
 * Class Autoloader
 *
 * \\Graphite\...                     -> /system/library
 * \\Modules\<module>                 -> /modules/<module>/Module.php
 * \\Modules\<module>\...             -> /modules/<module>/lib
 * \\Modules\<module>\Controller\..   -> /modules/<module>/controller
 * \\<other>\...                      -> /vendor/<other>
 *
 * @deprecated
 */
class Autoloader
{
    private static $_basePath = '';
    private static $_registered = false;

    /**
     * @param string $basePath
     */
    public static function register($basePath = '')
    {
        if (!self::$_registered) {
            self::$_basePath = $basePath;
            spl_autoload_register(array(__CLASS__, 'load'));
            self::$_registered = true;
        }
    }

    /**
     * @param string $className
     */
    public static function load($className)
    {
        $parts = explode('\\', ltrim($className, '\\'));

        // @todo переписать это порно

        switch ($parts[0]) { // map by root namespace
            case 'Graphite' : {
                array_shift($parts);
                $path = '/library/';
                break;
            }
            case 'Modules' : {
                array_shift($parts);
                $path = '/modules/' . array_shift($parts) . '/lib';
                break;
            }
        }

        if (!empty($path)) {
            $path = self::$_basePath . '/' . $path . '/' . implode('/', $parts) . '.php';
            if (file_exists($path)) {
                require_once $path;
            }
        }
    }
}
