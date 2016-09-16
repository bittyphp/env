<?php
use \BittyPHP\Env;

class EnvClassTest extends \PHPUnit_Framework_TestCase
{
    private $file1;
    private $file2;
    private $json;

    public $backupGlobals = false;

    public function __construct()
    {
        $this->file1 = __DIR__.'/env1.json';
        $this->file2 = __DIR__.'/env2.json';
        $json1 = json_decode(file_get_contents($this->file1), true);
        $json2 = json_decode(file_get_contents($this->file2), true);
        $this->json = array_replace($json1, $json2);
    }

    public function __destruct()
    {
        Env::clear();
    }

    public function testLoadNotFoundFile()
    {
        $this->assertFalse(Env::file('xxx.json'));
    }

    public function testLoadNotValidFile()
    {
        try {
            Env::file(__DIR__.'/env-notvalid.json');
        } catch (\Exception $e) {
            $this->assertTrue(true);
        }
    }

    public function testLoadFile()
    {
        $this->assertTrue(Env::file($this->file1, $this->file2));
    }

    public function testClear()
    {
        Env::clear();
        $this->assertNull(Env::get('ENV_STRING'));
        Env::file($this->file1, $this->file2);
    }

    public function testGetAll()
    {
        $all = Env::all();
        $this->assertEquals($all['ENV_STRING'], $this->json['ENV_STRING']);
    }

    public function testGetNormal()
    {
        $this->assertEquals(Env::get('ENV_STRING'), $this->json['ENV_STRING']);
    }

    public function testGetGetEnv()
    {
        $this->assertEquals(getenv('ENV_STRING'), $this->json['ENV_STRING']);
    }

    public function testGetGlobalEnv()
    {
        $this->assertEquals($_ENV['ENV_STRING'], $this->json['ENV_STRING']);
    }

    public function testGetApacheGetenv()
    {
        if (function_exists('apache_getenv')) {
            $this->assertEquals(apache_getenv('ENV_STRING'), $this->json['ENV_STRING']);
        }
    }

    public function testGetNormalNested()
    {
        $this->assertEquals(Env::get('ENV_HIERARCHICAL_ARRAY.Level1'), $this->json['ENV_HIERARCHICAL_ARRAY']['Level1']);
    }

    public function testNested1()
    {
        $this->assertEquals(Env::get('ENV_NESTED_ITEM3'), 'Item1 after Item3');
    }

    public function testNested2()
    {
        $this->assertEquals(Env::get('ENV_NESTED_ITEM2'), 'Item1 after Item3 after Item2');
    }

    public function testNestedArray()
    {
        $value = env('ENV_NESTED_ARRAY');
        $this->assertTrue(is_array($value));
    }

    public function testNestedArray1()
    {
        $value = Env::get('ENV_NESTED_ARRAY');
        $this->assertEquals($value[0], 'Item1 after Array1');
    }
}
