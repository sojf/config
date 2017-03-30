<?php
namespace Sojf\Config\Interfaces;


/**
 * Config接口
 */
interface Config
{
    // 加载配置文件
    public function load($path);

    // 获取数据
    public function get($key);

    // 设置数据
    public function set($key, $value);
}