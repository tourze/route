<?php

namespace tourze\Route;

use tourze\Base\Base;
use tourze\Base\Helper\Arr;
use tourze\Base\Object;
use tourze\Route\Exception\FilterCallbackInvalidException;
use tourze\Route\Exception\RouteException;

/**
 * 路由实例
 *
 * @property string routeRegex
 * @property string uri
 * @property string regex
 * @property string identify
 * @package tourze\Route
 */
class Entry extends Object
{

    /**
     * @var string  当前路由对象的标示符
     */
    protected $_identify = '';

    /**
     * @return string
     */
    public function getIdentify()
    {
        return $this->_identify;
    }

    /**
     * @param string $identify
     */
    public function setIdentify($identify)
    {
        $this->_identify = $identify;
    }

    /**
     * @var array 额外执行的filter
     */
    protected $_filters = [];

    /**
     * @var string 当前URI
     */
    protected $_uri = '';

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->_uri;
    }

    /**
     * @param string $uri
     */
    public function setUri($uri)
    {
        $this->_uri = $uri;
    }

    /**
     * @var array 匹配到的规则
     */
    protected $_regex = [];

    /**
     * @return array
     */
    public function getRegex()
    {
        return $this->_regex;
    }

    /**
     * @param array $regex
     */
    public function setRegex($regex)
    {
        $this->_regex = $regex;
    }

    /**
     * @var array 默认参数
     */
    protected $_defaults = [
        'method' => '',
        'action' => 'index',
        'host'   => '',
    ];

    /**
     * @var string
     */
    protected $_routeRegex;

    /**
     * @return string
     */
    public function getRouteRegex()
    {
        return $this->_routeRegex;
    }

    /**
     * @param string $routeRegex
     */
    public function setRouteRegex($routeRegex)
    {
        $this->_routeRegex = $routeRegex;
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->routeRegex = Route::compile($this->uri, $this->regex);
    }

    /**
     * 设置或读取路由规则的默认参数
     *
     *     $route->defaults([
     *         'controller' => 'welcome',
     *         'action'     => 'index'
     *     ]);
     *
     * @param   array $defaults 键值数据
     * @return  $this|array
     */
    public function defaults(array $defaults = null)
    {
        if (null === $defaults)
        {
            return $this->_defaults;
        }
        $this->_defaults = $defaults;

        return $this;
    }

    /**
     * filter会在路由参数被返回前执行：
     *
     *     $route->filter(
     *         function(Route $route, $params, Request $request)
     *         {
     *             if (Request::POST !== $request->method())
     *             {
     *                 return false;
     *             }
     *             if ($params and 'welcome' === $params['controller'])
     *             {
     *                 $params['controller'] = 'home';
     *             }
     *             return $params;
     *         }
     *     );
     *
     * 如果要跳过当前匹配规则，可以返回false。
     * 如果要更改当前路由参数，返回修改后的参数数组即可。
     *
     * [!!] 在filter被调用前，默认数据就已经被合并到路由参数中的了
     *
     * @throws  FilterCallbackInvalidException
     * @param   callable $callback 回调函数，可以为字符串、数组或closure
     * @return  $this
     */
    public function filter($callback)
    {
        if ( ! is_callable($callback))
        {
            throw new FilterCallbackInvalidException('Invalid Route::callback specified');
        }

        $this->_filters[] = $callback;

        return $this;
    }

    /**
     * 检测路由是否与路由表中的记录有匹配
     *
     * @param string $uri    URI
     * @param string $method URI的请求方法
     * @return array
     */
    public function matches($uri, $method = null)
    {
        $uri = trim($uri, '/');
        Base::getLog()->debug(__METHOD__ . ' begin to match uri', [
            'identify' => $this->identify,
            'uri'      => $uri,
        ]);

        // 先校验URI是否正确
        if ( ! preg_match($this->routeRegex, $uri, $matches))
        {
            Base::getLog()->debug(__METHOD__ . ' route do not matched', [
                'identify' => $this->identify,
                'regex'    => $this->routeRegex,
                'uri'      => $uri,
                'method'   => $method,
            ]);
            return false;
        }

        // 解析参数
        $params = [];
        foreach ($matches as $key => $value)
        {
            if (is_int($key))
            {
                // 如果键值不是关联的话，那么就跳过
                continue;
            }
            $params[$key] = $value;
        }

        // 设置默认参数
        foreach ($this->_defaults as $key => $value)
        {
            if ( ! isset($params[$key]) || '' === $params[$key])
            {
                // 如果没匹配到，那么就设置默认值
                $params[$key] = $value;
            }
        }

        Base::getLog()->debug(__METHOD__ . ' set route params', [
            'identify' => $this->identify,
            'name'     => $uri,
            'params'   => $params,
        ]);

        // 处理method
        if (isset($params['method']) && $params['method'])
        {
            $pass = true;
            if (is_array($params['method']))
            {
                if ( ! in_array($method, $params['method']))
                {
                    $pass = false;
                }
            }
            else
            {
                if ($params['method'] != $method)
                {
                    $pass = false;
                }
            }

            if ( ! $pass)
            {
                Base::getLog()->warning(__METHOD__ . ' requested http method do not matched', [
                    'identify'       => $this->identify,
                    'uri'            => $uri,
                    'param_method'   => $params['method'],
                    'request_method' => $method,
                ]);
                return false;
            }
        }

        if ( ! empty($params['controller']))
        {
            $params['controller'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', $params['controller'])));
        }

        if ( ! empty($params['directory']))
        {
            $params['directory'] = str_replace(' ', '_', ucwords(str_replace('_', ' ', $params['directory'])));
        }

        if ($this->_filters)
        {
            foreach ($this->_filters as $callback)
            {
                // 执行过滤器
                $return = call_user_func($callback, $this, $params, $uri);

                if (false === $return)
                {
                    // 停止继续匹配
                    return false;
                }
                elseif (is_array($return))
                {
                    // 修改参数值
                    $params = $return;
                }
            }
        }

        Base::getLog()->debug(__METHOD__ . ' final matched route params', [
            'identify' => $this->identify,
            'uri'      => $uri,
            'method'   => $method,
            'params'   => $params,
        ]);

        return $params;
    }

    /**
     * 是否是外部链接
     *
     * @return  boolean
     */
    public function isExternal()
    {
        return ! in_array(Arr::get($this->_defaults, 'host', false), Route::$localHosts);
    }

    /**
     * 传入参数，生成当前路由的uri
     *
     * @param  array $params URI参数
     * @return string
     * @throws RouteException
     */
    public function uri(array $params = null)
    {
        $defaults = $this->_defaults;

        if (isset($params['controller']))
        {
            $params['controller'] = strtolower($params['controller']);
        }
        if (isset($params['directory']))
        {
            $params['directory'] = strtolower($params['directory']);
        }

        /**
         * 匿名函数，用于循环替换路由参数
         *
         * @param  string  $portion  URI定义部分
         * @param  boolean $required 参数是否必须的
         * @return array 返回保存参数的数组
         * @throws RouteException
         */
        $compile = function ($portion, $required) use (&$compile, $defaults, $params)
        {
            $missing = [];

            $pattern = '#(?:' . Route::REGEX_KEY . '|' . Route::REGEX_GROUP . ')#';
            $result = preg_replace_callback($pattern, function ($matches) use (&$compile, $defaults, &$missing, $params, &$required)
            {
                if ('<' === $matches[0][0])
                {
                    $param = $matches[1];

                    if (isset($params[$param]))
                    {
                        $required = ($required || ! isset($defaults[$param]) || $params[$param] !== $defaults[$param]);
                        return $params[$param];
                    }

                    // 直接返回参数默认值
                    if (isset($defaults[$param]))
                    {
                        return $defaults[$param];
                    }

                    $missing[] = $param;
                }
                else
                {
                    $result = $compile($matches[2], false);

                    if ($result[1])
                    {
                        $required = true;
                        return $result[0];
                    }
                }

                return null;
            }, $portion);

            if ($required && $missing)
            {
                throw new RouteException('Required route parameter not passed: :param', [
                    ':param' => reset($missing),
                ]);
            }

            return [
                $result,
                $required,
            ];
        };

        $result = $compile($this->_uri, true);
        Base::getLog()->debug(__METHOD__ . ' get route compile result', [
            'identify' => $this->identify,
            'result'   => $result,
        ]);
        $uri = $result ? array_shift($result) : $result;

        // 过滤URI中的重复斜杆
        $uri = preg_replace('#//+#', '/', rtrim($uri, '/'));

        // 如果是外部链接
        if ($this->isExternal())
        {
            $host = $this->_defaults['host'];
            // 使用默认协议
            if (false === strpos($host, '://'))
            {
                $host = Route::$defaultProtocol . $host;
            }
            $uri = rtrim($host, '/') . '/' . $uri;
        }

        return $uri;
    }
}
