<?php
/**
 * Kijtra/Env (helper function)
 *
 * Licensed under The MIT License
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
if (!function_exists('env')) {
    if (!class_exists('\\Kijtra\\Env')) {
        include(__DIR__.'/Env.php');
    }

    /**
     * Env class alias
     * @param  mixed $name Key name
     * @return mixed
     */
    function env($arg = null)
    {
        // Get all data
        if (0 === func_num_args()) {
            return \Kijtra\Env::all();
        }

        // Clear all data
        elseif (PHP_EOL === $arg) {
            return \Kijtra\Env::clear();
        }

        // Add JSON file
        elseif (is_string($arg)) {
            return \Kijtra\Env::get($arg);
        }

        // Add JSON file
        elseif (is_array($arg)) {
            return \Kijtra\Env::file($arg);
        }
    }
}
