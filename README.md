# tourze框架-路由模块

## 安装

首先需要下载和安装[composer](https://getcomposer.org/)，具体请查看官网的[Download页面](https://getcomposer.org/download/)

在你的`composer.json`中增加：

    "require": {
        "tourze/route": "^1.0"
    },

或直接执行

    composer require tourze/route:"^1.0"

## 使用

代码示例：

    use tourze\Route\Route;
    
    Route::set('default', '<controller>(/<action>)')
        ->defaults([
            'controller' => 'Site',
            'action'     => 'index',
        ]);

或者使用组件来调用：

    use tourze\Base\Base;
    Base::get('route')->set(...);
