<?php

namespace tourze\Route\Component;

use tourze\Base\ComponentInterface;
use tourze\Route\Entry;

/**
 * 默认的路由器接口
 *
 * @package tourze\Route
 */
interface RouteInterface extends ComponentInterface
{

    /**
     * 设置和保存指定的路由信息
     *
     * @param string $name  路由名称
     * @param string $uri   URI规则
     * @param array  $regex 匹配规则
     * @param bool   $force
     * @param array  $defaults
     */
    public function set($name, $uri = null, $regex = null, $force = false, $defaults = []);

    /**
     * 强制替换指定路由，如果不存在的话，那就新增
     *
     * @param string $name
     * @param string $uri
     * @param array  $regex
     * @param array  $defaults
     */
    public function replace($name, $uri = null, $regex = null, $defaults = []);

    /**
     * @param string $name
     * @return Entry
     */
    public function get($name);

    /**
     * 根据指定的路由返回URL，等同于下面的代码：
     *
     *     echo URL::site(Route::get($name)->uri($params), $protocol);
     *
     * @param  string $name     路由名
     * @param  array  $params   URI参数
     * @param  mixed  $protocol 协议字符串、布尔值、等等
     * @return string
     */
    public function url($name, array $params = null, $protocol = null);

    /**
     * 解析和返回路由规则的参数
     *
     * @param  string $uri
     * @param  array  $regex
     * @return string
     */
    public function compile($uri, array $regex = null);
}
