<?php

use Illuminate\Container\Container;
use Illuminate\Config\Repository as ConfigRepository;
use Mockery as m;

class HelperTest extends \PHPUnit\Framework\TestCase
{
    public static $functions;

    public function setUp(): void
    {
        self::$functions = m::mock();

        $container = new Container();

        // Set up config service
        $config = new ConfigRepository([
            'setting' => [
                'driver' => 'memory',
                'database' => [
                    'connection' => null,
                    'table' => 'settings',
                    'key' => 'key',
                    'value' => 'value',
                ],
                'json' => [
                    'path' => sys_get_temp_dir() . '/settings.json',
                ],
                'cache' => [
                    'enabled' => false,
                    'key' => 'setting',
                    'ttl' => 3600,
                    'auto_clear' => true,
                ],
                'fallback' => [],
                'override' => [],
                'required_extra_columns' => [],
                'encrypted_keys' => [],
                'auto_save' => false,
            ]
        ]);

        $container->singleton('config', function () use ($config) {
            return $config;
        });

        Container::setInstance($container);

        $store = m::mock('Akaunting\Setting\Contracts\Driver');

        app()->bind('setting', function () use ($store) {
            return $store;
        });
    }

    /** @test */
    public function helper_without_parameters_returns_store()
    {
        $this->assertInstanceOf('Akaunting\Setting\Contracts\Driver', setting());
    }

    /** @test */
    public function single_parameter_get_a_key_from_store()
    {
        app('setting')->shouldReceive('get')->with('foo', null)->once()->andReturn('value');

        $result = setting('foo');
        $this->assertEquals('value', $result);
    }

    /** @test */
    public function two_parameters_return_a_default_value()
    {
        app('setting')->shouldReceive('get')->with('foo', 'bar')->once()->andReturn('bar');

        $result = setting('foo', 'bar');
        $this->assertEquals('bar', $result);
    }

    /** @test */
    public function array_parameter_call_set_method_into_store()
    {
        app('setting')->shouldReceive('set')->with(['foo', 'bar'])->once();

        $result = setting(['foo', 'bar']);
        $this->assertInstanceOf('Akaunting\Setting\Contracts\Driver', $result);
    }
}
