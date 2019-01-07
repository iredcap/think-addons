<?php
/**
 *  +----------------------------------------------------------------------
 *  | 草帽支付系统 [ WE CAN DO IT JUST THINK ]
 *  +----------------------------------------------------------------------
 *  | Copyright (c) 2018 http://www.iredcap.cn All rights reserved.
 *  +----------------------------------------------------------------------
 *  | Licensed ( https://www.apache.org/licenses/LICENSE-2.0 )
 *  +----------------------------------------------------------------------
 *  | Author: Brian Waring <BrianWaring98@gmail.com>
 *  +----------------------------------------------------------------------
 */

use think\Loader;
use think\facade\Env;
use think\facade\Hook;
use think\facade\Route;

// 定义插件目录
define('ADDON_PATH', Env::get('root_path') . DIRECTORY_SEPARATOR . 'addons' .DIRECTORY_SEPARATOR);

// 定义插件路由
Route::any('addons/:route', "\\think\\addons\\Route@execute");

// 如果插件目录不存在则创建
if (! is_dir(ADDON_PATH)) {
    @mkdir(ADDON_PATH, 0777, true);
}

// 注册插件类的根命名空间
Loader::addNamespace('addons', ADDON_PATH);

/**
 * 处理插件钩子
 *
 * @param string $hook 钩子名称
 * @param mixed $params 传入参数
 * @return mixed
 */
function hook($hook, $params = [])
{
    $result = Hook::listen($hook, $params);
    if (count($result) > 0) {
        return $result;
    }
}

/**
 * 获取插件类的类名
 *
 * @param string $name 插件名
 * @param string $type 返回命名空间类型
 * @param string $class 当前类名
 * @return string
 */
function get_addon_class($name, $type = 'hook', $class = null)
{
    $name = Loader::parseName($name);
    // 处理多级控制器情况
    if (! is_null($class) && strpos($class, '.')) {
        $class = explode('.', $class);
        foreach ($class as $key => $cls) {
            $class[$key] = Loader::parseName($cls, 1);
        }
        $class = implode('\\', $class);
    } else {
        $class = Loader::parseName(is_null($class) ? $name : $class, 1);
    }
    switch ($type) {
        case 'controller':
            $namespace = "\\addons\\" . $name . "\\controller\\" . $class;
            break;
        default:
            $namespace = "\\addons\\" . $name . "\\" . $class;
    }

    return class_exists($namespace) ? $namespace : '';
}

/**
 * 获取插件类的配置文件数组
 *
 * @param string $name 插件名
 * @return array
 */
function get_addon_config($name)
{
    $class = get_addon_class($name);
    if (class_exists($class)) {
        $addon = new $class();

        return $addon->getConfig();
    } else {
        return [];
    }
}

/**
 * 插件显示内容里生成访问插件的url
 *
 * @param $url
 * @param array $param
 * @return bool|string
 * @param bool|string $suffix 生成的URL后缀
 * @param bool|string $domain 域名
 */
function addon_url($url, $param = [], $suffix = true, $domain = false)
{
    $url        = parse_url($url);
    $case       = config('url_convert');
    $addons     = $case ? Loader::parseName($url['scheme']) : $url['scheme'];
    $controller = $case ? Loader::parseName($url['host']) : $url['host'];
    $action     = trim($case ? strtolower($url['path']) : $url['path'], '/');

    /* 解析URL带的参数 */
    if (isset($url['query'])) {
        parse_str($url['query'], $query);
        $param = array_merge($query, $param);
    }

    // 生成插件链接新规则
    $actions = "{$addons}-{$controller}-{$action}";

    return url("/addons/{$actions}", $param, $suffix, $domain);
}