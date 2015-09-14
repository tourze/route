<?php

namespace tourze\Route;

use tourze\Base\Base;
use tourze\Base\Exception\BaseException;
use tourze\Base\Helper\Url;
use tourze\Base\Object;
use tourze\Route\Exception\RouteNotFoundException;

/**
 * 路由处理类
 *
 * @property string identify
 * @property array  regex
 * @property string uri
 * @property string routeRegex
 * @package tourze\Route
 */
class Route extends Object
{

    /**
     * @const  URI组信息的正则规则
     */
    const REGEX_GROUP = '\(((?:(?>[^()]+)|(?R))*)\)';

    /**
     * @const  <key>的正则匹配规则
     */
    const REGEX_KEY = '<([a-zA-Z0-9_]++)>';

    /**
     * @const  <segment>的匹配正则
     */
    const REGEX_SEGMENT = '[^/.,;?\n]++';

    /**
     * @const  转义正则
     */
    const REGEX_ESCAPE = '[.\\+*?[^\\]${}=!|]';

    /**
     * @var  string  路由中使用的默认协议
     */
    public static $defaultProtocol = 'http://';

    /**
     * @var  array  本地主机表
     */
    public static $localHosts = [
        false,
        '',
        'local',
        'localhost'
    ];

    /**
     * @var string  默认命名空间
     */
    public static $defaultNamespace = '\\controller\\';

    /**
     * @var  string  路由中的默认动作
     */
    public static $defaultAction = 'index';

    /**
     * @var  Entry[]  记录所有路由信息的表
     */
    protected static $routeInstances = [];

    /**
     * 设置和保存指定的路由信息
     *
     *     Route::set('default', '(<controller>(/<action>(/<id>)))')
     *         ->defaults([
     *             'controller' => 'welcome',
     *         ]);
     *
     * @param string $name  路由名称
     * @param string $uri   URI规则
     * @param array  $regex 匹配规则
     * @param bool   $force
     * @return Entry
     */
    public static function set($name, $uri = null, $regex = null, $force = false)
    {
        Base::getLog()->debug(__METHOD__ . ' call static get method', [
            'name'  => $name,
            'uri'   => $uri,
            'regex' => $regex,
            'force' => $force,
        ]);
        if (isset(self::$routeInstances[$name]) && ! $force)
        {
            return self::$routeInstances[$name];
        }
        return self::$routeInstances[$name] = new Entry([
            'uri'      => $uri,
            'regex'    => $regex,
            'identify' => $name,
        ]);
    }

    /**
     * 强制替换指定路由，如果不存在的话，那就新增
     *
     * @param string $name
     * @param string $uri
     * @param array  $regex
     * @return Entry
     */
    public static function replace($name, $uri = null, $regex = null)
    {
        Base::getLog()->debug(__METHOD__ . ' call static replace method', [
            'name'  => $name,
            'uri'   => $uri,
            'regex' => $regex,
        ]);
        return self::set($name, $uri, $regex, true);
    }

    /**
     * 获取指定的路由信息
     *
     *     $route = Route::get('default');
     *
     * @param  string $name 路由名称
     * @return Entry
     * @throws BaseException
     */
    public static function get($name)
    {
        if ( ! isset(Route::$routeInstances[$name]))
        {
            Base::getLog()->error(__METHOD__ . ' getting unknown route', [
                'name'   => $name,
                'exists' => array_keys(self::$routeInstances),
            ]);
            throw new RouteNotFoundException('The requested route does not exist: :route', [
                ':route' => $name
            ]);
        }

        return Route::$routeInstances[$name];
    }

    /**
     * 检测指定路由是否存在
     *
     * @param string $name
     * @return bool
     */
    public static function exists($name)
    {
        return isset(self::$routeInstances[$name]);
    }

    /**
     * 获取所有已经定义的路由信息
     *
     *     $routes = Route::all();
     *
     * @return  array
     */
    public static function all()
    {
        return Route::$routeInstances;
    }

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
    public static function url($name, array $params = null, $protocol = null)
    {
        $route = Route::get($name);
        return $route->isExternal()
            ? $route->uri($params)
            : Url::site($route->uri($params), $protocol);
    }

    /**
     * 解析和返回路由规则的参数
     *
     *     $compiled = Route::compile('<controller>(/<action>(/<id>))', [
     *         'controller' => '[a-z]+',
     *         'id' => '\d+',
     *     ]);
     *
     * @param  string $uri
     * @param  array  $regex
     * @return string
     */
    public static function compile($uri, array $regex = null)
    {
        // The URI should be considered literal except for keys and optional parts
        // Escape everything preg_quote would escape except for : ( ) < >
        $expression = preg_replace('#' . Route::REGEX_ESCAPE . '#', '\\\\$0', $uri);

        if (false !== strpos($expression, '('))
        {
            // Make optional parts of the URI non-capturing and optional
            $expression = str_replace(['(', ')'], ['(?:', ')?'], $expression);
        }

        // 插入默认规则
        $expression = str_replace(['<', '>'], ['(?P<', '>' . Route::REGEX_SEGMENT . ')'], $expression);

        if ($regex)
        {
            $search = $replace = [];
            foreach ($regex as $key => $value)
            {
                $search[] = "<$key>" . Route::REGEX_SEGMENT;
                $replace[] = "<$key>$value";
            }

            // Replace the default regex with the user-specified regex
            $expression = str_replace($search, $replace, $expression);
        }

        return '#^' . $expression . '$#uD';
    }
}
