<?php
namespace Sojf\Config\Interfaces;


/**
 * 配置格式解析器接口
 */
interface Parser
{
    // 解析文件
    public function parse($path);

    // 保存文件
    public function save($path, array $data);

    // 转换成字符串
    public function toString(array $data);

    // 转换成数组
    public function toArray($str);
}