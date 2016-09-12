<?php
header('Content-Type: text/plain');
include dirname(__DIR__).'/vendor/autoload.php';

$file1 = '../tests/env1.json';
$file2 = '../tests/env2.json';
$path1 = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.$file1);
$path2 = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.$file2);
$json1 = json_decode(file_get_contents($path1), true);
$json2 = json_decode(file_get_contents($path2), true);
$json = array_replace($json1, $json2);

env($file1);

var_dump(env(true));

env(false, false);
var_dump(env('ENV_STRING'));


env(array($file1, $file2));
var_dump(env('ENV_STRING'));

env(false, false);
var_dump(env('ENV_STRING'));

env($file1, $file2);
var_dump(count(env(true)), count($json));
