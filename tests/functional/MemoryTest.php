<?php

class MemoryTest extends AbstractFunctionalTest
{
    protected function assertStoreEquals($store, $expected, $message = '')
    {
        $this->assertEquals($expected, $store->all(), $message ?: 'Memory store data should match expected');
        // removed persistance test assertions
    }

    protected function assertStoreKeyEquals($store, $key, $expected, $message = '')
    {
        $this->assertEquals($expected, $store->get($key), $message ?: 'Memory store key should match expected');
        // removed persistance test assertions
    }

    protected function createStore(array $data = null)
    {
        return new \Akaunting\Setting\Drivers\Memory($data);
    }
}
