<?php
if (!function_exists('env')) {
    /**
     * Env class alias
     * @param  mixed $name Key name
     * @return mixed
     */
    function env($arg = null)
    {
        // Get all data
        if (0 === func_num_args()) {
            return \BittyPHP\Env::all();
        }

        // Clear all data
        elseif (PHP_EOL === $arg) {
            return \BittyPHP\Env::clear();
        }

        // Add JSON file
        elseif (is_string($arg)) {
            return \BittyPHP\Env::get($arg);
        }

        // Add JSON file
        elseif (is_array($arg)) {
            return \BittyPHP\Env::file($arg);
        }
    }
}
