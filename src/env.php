<?php
if (!function_exists('env')) {
    function env($name)
    {
        static $envs = null, $__ENV = null, $__SERVER = null;

        if (null === $envs) {
            $files = array();
            if (func_num_args() > 1) {
                $files = func_get_args();
            } elseif (is_string($name)) {
                $files = array($name);
            } elseif (is_array($name)) {
                $files = $name;
            } elseif (!empty($_SERVER['DOCUMENT_ROOT'])) {
                $files = array($_SERVER['DOCUMENT_ROOT'].'/env.json');
            }

            if (!empty($files)) {
                $baseDir = debug_backtrace();
                $baseDir = dirname($baseDir[0]['file']);
                foreach ($files as $file) {
                    if (is_string($file)) {
                        if (!$file = realpath($baseDir.DIRECTORY_SEPARATOR.$file)) {
                            continue;
                        }

                        // Initialize
                        if (null === $envs) {
                            // Cache Globals
                            $__ENV = $_ENV;
                            $__SERVER = $_SERVER;
                            $defined = get_defined_constants(true);
                            $envs = (!empty($defined['user'])) ? $defined['user'] : array();
                        }

                        $content = file_get_contents($file);
                        $json = json_decode($content, true);
                        if (empty($json)) {
                            if (json_last_error() !== JSON_ERROR_NONE) {
                                trigger_error(json_last_error_msg(), E_USER_WARNING);
                            }
                            continue;
                        }

                        $dirname = dirname($file);
                        $filtered = $envs;
                        foreach ($json as $key => $val) {
                            if (is_string($val)) {
                                $val = str_replace(array('{__dirname__}', '{__DIR__}'), $dirname, $val);
                                if (preg_match_all('/(\{([^\}]+)\})/i', $val, $matches)) {
                                    foreach ($matches[2] as $n => $holder) {
                                        if (array_key_exists($holder, $filtered)) {
                                            $val = str_replace($matches[1][$n], $filtered[$holder], $val);
                                        } else {
                                            $val = str_replace($matches[1][$n], '', $val);
                                        }
                                    }
                                }
                                if (is_file($val) || is_dir($val)) {
                                    $val = realpath($val);
                                }
                            }

                            if (is_scalar($val)) {
                                $put = $val;
                                if (null === $val) {
                                    $put = '';
                                } elseif (true === $val) {
                                    $put = 'true';
                                } elseif (false === $val) {
                                    $put = 'false';
                                }

                                if (function_exists('putenv')) {
                                    putenv($key.'='.$put);
                                }

                                if (function_exists('apache_setenv')) {
                                    apache_setenv($key, $put);
                                }
                            }

                            $_ENV[$key] = $val;
                            $_SERVER[$key] = $val;
                            $filtered[$key] = $val;
                        }

                        $envs = $filtered;
                    }
                }
            }
        }

        if (null !== $envs) {
            if (true === $name) {
                return $envs;
            } elseif (is_string($name) && !empty($envs)) {
                if (array_key_exists($name, $envs)) {
                    return $envs[$name];
                } elseif (function_exists('getenv') && ($val = getenv($name))) {
                    return $val;
                } elseif (function_exists('apache_getenv') && ($val = apache_getenv($name))) {
                    return $val;
                } elseif (array_key_exists($name, $_ENV)) {
                    return $val;
                } elseif (array_key_exists($name, $_SERVER)) {
                    return $val;
                } elseif (defined($name)) {
                    return constant($name);
                }
            } elseif (2 === func_num_args() && array(false, false) == func_get_args()) {
                foreach ($envs as $key => $val) {
                    if (function_exists('putenv')) {
                        putenv($key);
                    }

                    if (function_exists('apache_setenv')) {
                        apache_setenv($key, null);
                    }
                }

                $_ENV = $__ENV;
                $_SERVER = $__SERVER;
                $envs = null;
            }
        }
    }
}
