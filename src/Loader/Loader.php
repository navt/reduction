<?php
/**
 * Flight: An extensible micro-framework.
 *
 * @copyright   Copyright (c) 2011, Mike Cao <mike@mikecao.com>
 * @license     MIT, http://flightphp.com/license
 */

namespace Loader;

class Loader 
{
    /**
     * Autoload directories.
     *
     * @var mixed[]
     */
    protected static $dirs = [];

    /**
     * Starts/stops autoloader.
     *
     * @param bool $enabled Enable/disable autoloading
     * @param mixed[] $dirs Autoload directories
     */
    public static function autoload(bool $enabled = true, array $dirs = []):void {
        if ($enabled) {
            spl_autoload_register(array(__CLASS__, 'loadClass'));
        }
        else {
            spl_autoload_unregister(array(__CLASS__, 'loadClass'));
        }

        if (!empty($dirs)) {
            self::addDirectory($dirs);
        }
    }

    /**
     * Autoloads classes.
     *
     * @param string $class Class name
     */
    public static function loadClass(string $class): void {
        $class_file = str_replace(array('\\', '_'), '/', $class).'.php';
        foreach (self::$dirs as $dir) {
            $file = $dir.'/'.$class_file;
            if (file_exists($file)) {
                require $file;
                return;
            }
        }
    }

    /**
     * Adds a directory for autoloading classes.
     *
     * @param mixed[] $dir Directory path
     */
    public static function addDirectory(array $dir): void {
        
        if (is_array($dir)) {
            foreach ($dir as $value) {
                if (is_array($value)) {
                    self::addDirectory($value);
                } else if (is_string($value)) {
                    if (!in_array($value, self::$dirs)) self::$dirs[] = $value;
                }
            }
        }

    }

}
