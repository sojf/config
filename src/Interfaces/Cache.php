<?php
namespace Sojf\Config\Interfaces;


/**
 * 缓存器接口
 */
interface Cache
{
    // 打开缓存数据
    public function open();

    // 关闭缓存数据
    public function close();

    // 获取缓存数据
    public function get($key);

    // 设置缓存数据
    public function set($key, $value);

    // 追加缓存数据
    public function add($data);

    // 删除缓存数据
    public function del($key);

    // 返回所有缓存数据
    public function all();
}