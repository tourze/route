<?php

namespace tourze\Route\Component;

use tourze\Base\Component;
use tourze\Base\Helper\Arr;
use tourze\Route\Route as BaseRoute;

/**
 * 路由组件
 *
 * @package tourze\Route\Component
 */
class Route extends Component implements RouteInterface
{

    /**
     * @var array
     */
    public $config = [];

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();
        foreach ($this->config as $routeName => $routeConfig)
        {
            if (is_numeric($routeName))
            {
                $routeName = Arr::get($routeConfig, 'name', uniqid());
            }
            $this->set(
                $routeName,
                Arr::get($routeConfig, 'uri'),
                Arr::get($routeConfig, 'regex'),
                Arr::get($routeConfig, 'force',false),
                Arr::get($routeConfig, 'defaults', [])
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($name, $uri = null, $regex = null, $force = false, $defaults = [])
    {
        BaseRoute::set($name, $uri, $regex, $force)->defaults($defaults);
    }

    /**
     * {@inheritdoc}
     */
    public function replace($name, $uri = null, $regex = null, $defaults = [])
    {
        BaseRoute::replace($name, $uri, $regex)->defaults($defaults);
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        return BaseRoute::get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function url($name, array $params = null, $protocol = null)
    {
        return BaseRoute::url($name, $params, $protocol);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($uri, array $regex = null)
    {
        return BaseRoute::compile($uri, $regex);
    }
}
