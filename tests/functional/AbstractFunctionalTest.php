<?php

use Akaunting\Setting\Drivers\Database;
use Illuminate\Config\Repository as ConfigRepository;

abstract class AbstractFunctionalTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        // Set up config service for all functional tests
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

        $container = new \Illuminate\Container\Container();
        $container->singleton('config', function () use ($config) {
            return $config;
        });

        \Illuminate\Container\Container::setInstance($container);
    }

    abstract protected function createStore(array $data = []);

    protected function assertStoreEquals($store, $expected, $message = '')
    {
        $this->assertEquals($expected, $store->all(), $message ?: 'Store data should match expected');
        $store->save();
        $store = $this->createStore();
        $this->assertEquals($expected, $store->all(), $message ?: 'Store data should persist after save');
    }

    protected function assertStoreKeyEquals($store, $key, $expected, $message = '')
    {
        $this->assertEquals($expected, $store->get($key), $message ?: 'Store key should match expected');
        $store->save();
        $store = $this->createStore();
        $this->assertEquals($expected, $store->get($key), $message ?: 'Store key should persist after save');
    }

    /** @test */
    public function store_is_initially_empty()
    {
        $store = $this->createStore();
        $this->assertEquals([], $store->all());
    }

    /** @test */
    public function written_changes_are_saved()
    {
        $store = $this->createStore();
        $store->set('foo', 'bar');
        $this->assertStoreKeyEquals($store, 'foo', 'bar');
    }

    /** @test */
    public function nested_keys_are_nested()
    {
        $store = $this->createStore();
        $store->set('foo.bar', 'baz');
        $this->assertStoreEquals($store, ['foo' => ['bar' => 'baz']]);
    }

    /** @test */
    public function cannot_set_nested_key_on_non_array_member()
    {
        $store = $this->createStore();
        $store->set('foo', 'bar');
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Non-array segment encountered');
        $store->set('foo.bar', 'baz');
    }

    /** @test */
    public function can_forget_key()
    {
        $store = $this->createStore();
        $store->set('foo', 'bar');
        $store->set('bar', 'baz');
        $this->assertStoreEquals($store, ['foo' => 'bar', 'bar' => 'baz']);

        $store->forget('foo');
        $this->assertStoreEquals($store, ['bar' => 'baz']);
    }

    /** @test */
    public function can_forget_nested_key()
    {
        $store = $this->createStore();
        $store->set('foo.bar', 'baz');
        $store->set('foo.baz', 'bar');
        $store->set('bar.foo', 'baz');
        $this->assertStoreEquals($store, [
            'foo' => [
                'bar' => 'baz',
                'baz' => 'bar',
            ],
            'bar' => [
                'foo' => 'baz',
            ],
        ]);

        $store->forget('foo.bar');
        $this->assertStoreEquals($store, [
            'foo' => [
                'baz' => 'bar',
            ],
            'bar' => [
                'foo' => 'baz',
            ],
        ]);

        $store->forget('bar.foo');
        $expected = [
            'foo' => [
                'baz' => 'bar',
            ],
            'bar' => [
            ],
        ];
        if ($store instanceof Database) {
            unset($expected['bar']);
        }
        $this->assertStoreEquals($store, $expected);
    }

    /** @test */
    public function can_forget_all()
    {
        $store = $this->createStore(['foo' => 'bar']);
        $this->assertStoreEquals($store, ['foo' => 'bar']);
        $store->forgetAll();
        $this->assertStoreEquals($store, []);
    }
}
