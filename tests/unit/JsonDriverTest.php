<?php

use Mockery as m;
use Illuminate\Config\Repository as ConfigRepository;

class JsonDriverTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        // Set up config service
        $config = new ConfigRepository([
            'setting' => [
                'driver' => 'json',
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

        $container = new \Illuminate\Container\Container();
        $container->singleton('config', function () use ($config) {
            return $config;
        });

        \Illuminate\Container\Container::setInstance($container);
    }

    public function tearDown(): void
    {
        m::close();
    }

    protected function mockFilesystem()
    {
        return m::mock('Illuminate\Filesystem\Filesystem');
    }

    protected function makeStore($files, $path = 'fakepath')
    {
        return new Akaunting\Setting\Drivers\Json($files, $path);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throws_exception_when_file_not_writeable()
    {
        $files = $this->mockFilesystem();
        $files->shouldReceive('exists')->once()->with('fakepath')->andReturn(true);
        $files->shouldReceive('isWritable')->once()->with('fakepath')->andReturn(false);
        $store = $this->makeStore($files);
    }

    /**
     * @test
     * @expectedException InvalidArgumentException
     */
    public function throws_exception_when_files_put_fails()
    {
        $files = $this->mockFilesystem();
        $files->shouldReceive('exists')->once()->with('fakepath')->andReturn(false);
        $files->shouldReceive('put')->once()->with('fakepath', '{}')->andReturn(false);
        $store = $this->makeStore($files);
    }

    /**
     * @test
     * @expectedException RuntimeException
     */
    public function throws_exception_when_file_contains_invalid_json()
    {
        $files = $this->mockFilesystem();
        $files->shouldReceive('exists')->once()->with('fakepath')->andReturn(true);
        $files->shouldReceive('isWritable')->once()->with('fakepath')->andReturn(true);
        $files->shouldReceive('get')->once()->with('fakepath')->andReturn('[[!1!11]');

        $store = $this->makeStore($files);
        $store->get('foo');
    }
}
