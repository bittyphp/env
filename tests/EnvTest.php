<?php
class EnvTest extends \PHPUnit_Framework_TestCase
{
    private $file1;
    private $file2;
    private $json;

    public $backupGlobals = false;

    public function setUp()
    {
        $this->file1 = './env1.json';
        $this->file2 = './env2.json';
        $path1 = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.$this->file1);
        $path2 = realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.$this->file2);
        $json1 = json_decode(file_get_contents($path1), true);
        $json2 = json_decode(file_get_contents($path2), true);
        $this->json = array_replace($json1, $json2);
    }

    public function testLoadSingleFile()
    {
        env($this->file1);
        $this->assertEquals(env('ENV_STRING'), $this->json['ENV_STRING']);
    }

    public function testClear()
    {
        env(false, false);
        $this->assertNull(env('ENV_STRING'));
    }

    public function testLoadFileByArray()
    {
        env(false, false);
        env(array($this->file1, $this->file2));
        $this->assertEquals(env('ENV_STRING'), $this->json['ENV_STRING']);
    }

    public function testLoadFileByArgs()
    {
        env(false, false);
        env($this->file1, $this->file2);
        $this->assertEquals(env('ENV_STRING'), $this->json['ENV_STRING']);
    }

    public function testGetAll()
    {
        $all = env(true);
        $this->assertGreaterThanOrEqual(count($this->json), count($all));
    }

    public function testGetNormal()
    {
        $this->assertEquals(env('ENV_STRING'), $this->json['ENV_STRING']);
    }

    public function testGetGlobalEnv()
    {
        $this->assertEquals($_ENV['ENV_STRING'], $this->json['ENV_STRING']);
    }

    public function testGetGlobalServer()
    {
        $this->assertEquals($_SERVER['ENV_STRING'], $this->json['ENV_STRING']);
    }

    public function testGetGetenv()
    {
        if (function_exists('getenv')) {
            $this->assertEquals(getenv('ENV_STRING'), $this->json['ENV_STRING']);
        }
    }

    public function testGetApacheGetenv()
    {
        if (function_exists('apache_getenv')) {
            $this->assertEquals(apache_getenv('ENV_STRING'), $this->json['ENV_STRING']);
        }
    }

    public function testNested1()
    {
        $this->assertEquals(env('ENV_NESTED_ITEM2'), 'Item1 after Item2');
    }

    public function testNested2()
    {
        $this->assertEquals(env('ENV_NESTED_ITEM3'), 'Item1 after Item2 after Item3');
    }
}
