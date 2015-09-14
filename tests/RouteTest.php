<?php

namespace tourze\Route;

use PHPUnit_Framework_TestCase;

class RouteTest extends PHPUnit_Framework_TestCase
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        Route::set('test', 'route-test/<id>', [
            'id' => '\w+',
        ]);
    }

    /**
     * 测试[Route::url()]
     */
    public function testUrl()
    {
        $this->assertEquals('/route-test/1', Route::url('test', ['id' => 1]));
        $this->assertEquals('/route-test/HELLO', Route::url('test', ['id' => 'HELLO']));
    }

    /**
     * 测试[Route::exists()]
     */
    public function testExists()
    {
        $this->assertTrue(Route::exists('test'));
    }
}
