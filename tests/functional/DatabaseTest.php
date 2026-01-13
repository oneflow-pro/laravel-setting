<?php

use Illuminate\Config\Repository as ConfigRepository;

class DatabaseTest extends AbstractFunctionalTest
{
    public function setUp(): void
    {
        $this->container = new \Illuminate\Container\Container();

        // Set up config service
        $config = new ConfigRepository([
            'setting' => [
                'driver' => 'database',
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

        $this->container->singleton('config', function () use ($config) {
            return $config;
        });

        // Set the container instance for Laravel helpers
        \Illuminate\Container\Container::setInstance($this->container);

        $this->capsule = new \Illuminate\Database\Capsule\Manager($this->container);
        $this->capsule->setAsGlobal();
        $this->container['db'] = $this->capsule;
        $this->capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        $this->capsule->schema()->create('settings', function ($t) {
            $t->string('key', 64)->unique();
            $t->string('value', 4096);
        });
    }

    public function tearDown(): void
    {
        $this->capsule->schema()->drop('settings');
        unset($this->capsule);
        unset($this->container);
    }

    protected function createStore(array $data = [])
    {
        if ($data) {
            $store = $this->createStore();
            $store->set($data);
            $store->save();
            unset($store);
        }

        return new \Akaunting\Setting\Drivers\Database(
            $this->capsule->getConnection()
        );
    }
}
