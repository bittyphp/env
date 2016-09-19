<?php
/**
 * BittyPHP/Env
 *
 * Licensed under The MIT License
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
namespace BittyPHP;

class Env
{
    private static $_ENV;

    /**
     * Filter method retry count
     */
    const FILTER_RETRY_MAX = 5;

    /**
     * Loaded files data
     * @var array
     */
    private static $files = array();

    /**
     * Stored data
     * @var array
     */
    private static $storage = array();

    /**
     * Current file path for self::filter method
     * @var string
     */
    private static $targetFile;

    /**
     * self::filter method's current retry count
     * @var integer
     */
    private static $filterRetry = 0;


    /***************************************************************************
     * Public methods
     ***************************************************************************/

    /**
     * Add JSON file
     *
     * @param mixed Single Arg or Multi Args or Single Array
     * @return bool File load success
     */
    public static function file()
    {
        if (!$datas = self::loadFile(func_get_args())) {
            return false;
        }

        foreach ($datas as $file => $json) {
            self::$targetFile = $file;
            $values = array();
            foreach ($json as $key => $val) {
                $values[$key] = $val;
            }
            $values = self::filterRecursive($values);
            self::$files[$file] = $values;
            self::$storage = array_replace_recursive(self::$storage, $values);
        }

        self::$targetFile = null;

        self::$storage = self::filterRecursive(self::$storage);
        self::$storage = self::getFixedPathValue(self::$storage);

        $is_putenv = function_exists('putenv');
        $is_apache = function_exists('apache_setenv');
        foreach (self::$storage as $key => $val) {
            if (is_scalar($val)) {
                if ($is_putenv) {
                    putenv($key.'='.$val);
                }
                if ($is_apache) {
                    apache_setenv($key, $val);
                }
            }

            $_ENV[$key] = $val;
        }

        return true;
    }

    /**
     * Get item data
     *
     * @param  string $name Key name
     * @return mixed  Item data
     */
    public static function get($name)
    {
        if (!is_string($name)) {
            return;
        }

        if (array_key_exists($name, self::$storage)) {
            return self::$storage[$name];
        } elseif (function_exists('getenv') && ($val = getenv($name))) {
            return $val;
        } elseif (function_exists('apache_getenv') && ($val = apache_getenv($name))) {
            return $val;
        } elseif (defined($name)) {
            return constant($name);
        } elseif (array_key_exists($name, $_ENV)) {
            return $_ENV[$name];
        } elseif (array_key_exists($name, $_SERVER)) {
            return $_SERVER[$name];
        } elseif (false !== strpos($name, '.')) {
            $src = self::$storage;
            foreach (explode('.', $name) as $val) {
                if (array_key_exists($val, $src)) {
                    $src = $src[$val];
                } else {
                    return;
                }
            }
            return $src;
        }
    }

    /**
     * Get all data
     *
     * @return array Stored data
     */
    public static function all()
    {
        return self::$storage;
    }

    /**
     * Clear all stored data
     */
    public static function clear()
    {
        $is_putenv = function_exists('putenv');
        $is_apache = function_exists('apache_setenv');
        foreach (self::$storage as $key => $val) {
            if ($is_putenv) {
                putenv($key);
            }
            if ($is_apache) {
                apache_setenv($key, null);
            }
        }

        self::$storage = array();

        if (null !== self::$_ENV) {
            $_ENV = self::$_ENV;
        }
    }


    /***************************************************************************
     * Private methods
     ***************************************************************************/

    /**
     * Get caller file's directory path
     *
     * @return string Directory path
     */
     private static function getBaseDir()
     {
         $dir = __DIR__;
         foreach (debug_backtrace() as $key => $val) {
             if (!empty($val['file']) && !empty($val['function'])) {
                 if (
                     // NOTE: Not current dir and current class and called "file" method
                     (
                         dirname($val['file']) !== __DIR__
                         && !empty($val['class']) && __CLASS__ == $val['class']
                         && 'file' === $val['function']
                     )

                     // NOTE: Not current class and called "env" function
                     || (
                         (empty($val['class']) || __CLASS__ !== $val['class'])
                         && 'env' === $val['function']
                     )
                 ) {
                     $dir = dirname($val['file']);
                     break;
                 }
             }
         }
         return $dir;
     }

    /**
     * Load JSON file(s)
     *
     * @param  array $args File paths
     * @return mixed Decoded JSON data
     */
    private static function loadFile($args)
    {
        $files = array();
        if (count($args) > 1) {
            $files = $args;
        } elseif (is_string($args[0])) {
            $files = array($args[0]);
        } elseif (is_array($args[0])) {
            $files = $args[0];
        }

        if (empty($files)) {
            return;
        }

        $dir = null;
        $datas = array();
        foreach ($files as $file) {
            if (!is_string($file)) {
                continue;
            }

            $filepath = $file;

            // If not Absolute path
            if ('/' !== $filepath{0} || !is_dir(dirname($filepath))) {
                if (null === $dir) {
                    $dir = self::getBaseDir();
                }
                $filepath = $dir.DIRECTORY_SEPARATOR;
                $filepath .= preg_replace('#\A'.preg_quote($dir).'#', '', $file);
            }

            // If file not exists or already loaded
            if (!($path = realpath($filepath))) {
                $errors[] = $file;
                continue;
            } elseif (!empty(self::$files[$path])) {
                $datas[$path] = self::$files[$path];
            } else {
                $content = file_get_contents($path);
                $json = json_decode($content, true);
                if (empty($json)) {
                    if (JSON_ERROR_NONE !== ($code = json_last_error())) {
                        $message = 'Invalid JSON file.';
                        if (function_exists('json_last_error_msg')) {
                            $message = json_last_error_msg();
                        }
                        throw new \Exception($message, $code);
                    }
                } elseif (is_array($json)) {
                    $datas[$path] = self::$files[$path] = $json;
                }
            }
        }

        if (!empty($datas)) {
            if (null === self::$_ENV) {
                self::$_ENV = $_ENV;
            }

            return $datas;
        }
    }

    /**
     * If value is Path String, convert realpath
     *
     * @param array Source data
     * @return array Fixed data
     */
    private static function getFixedPathValue($data)
    {
        if (
            is_string($data)
            && (false !== strpos($data, '/') || false !== strpos($data, '\\'))
            && (is_dir($data) || is_file($data))
        ) {
            $data = realpath($data);
        } elseif (is_array($data)) {
            foreach ($data as $key => $val) {
                $data[$key] = self::getFixedPathValue($val);
            }
        }

        return $data;
    }

    /**
     * Apply self::filter method recursive
     *
     * @param  array $source Filtering data
     * @return array Filtered data
     */
    private static function filterRecursive($source)
    {
        $source = self::filter($source);
        if (self::$filterRetry > 0 && self::$filterRetry <= self::FILTER_RETRY_MAX) {
            self::$filterRetry = false;
            return self::filterRecursive($source);
        }
        self::$filterRetry = 0;
        return $source;
    }

    /**
     * Replace placeholders
     *
     * @param  mixed $value  Filtering data
     * @param  array $source Replace source data
     * @return mixed Filtered data
     */
    private static function filter($value, $source = array())
    {
        if (is_string($value) && preg_match_all('/(\{([\w\-\.]+)\})/isu', $value, $matches)) {
            $replaces = array();
            foreach ($matches[2] as $i => $name) {
                $match = $matches[0][$i];
                if (false !== strpos($name, '.')) {
                    $src = $source;
                    foreach (explode('.', $name) as $val) {
                        if (array_key_exists($val, $src)) {
                            $src = $src[$val];
                        } else {
                            $src = null;
                            break;
                        }
                    }
                    if (!is_array($src)) {
                        $replaces[$match] = $src;
                    }
                } else {
                    $upper = strtoupper($name);
                    if ('__DIR__' === $upper || '__DIRNAME__' === $upper) {
                        if (!empty(self::$targetFile)) {
                            $replaces[$match] = dirname(self::$targetFile);
                        }
                    } elseif ('__FILE__' === $upper || '__FILENAME__' === $upper) {
                        if (!empty(self::$targetFile)) {
                            $replaces[$match] = self::$targetFile;
                        }
                    } elseif ($val = self::get($name)) {
                        $replaces[$match] = $val;
                    }
                }
            }
            if (!empty($replaces)) {
                $value = str_replace(array_keys($replaces), array_values($replaces), $value);
                if (preg_match('/(\{([\w\-\.]+)\})/isu', $value)) {
                    ++self::$filterRetry;
                }
            }
        } elseif (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = self::filter($val, $value);
            }
        }

        return $value;
    }
}
