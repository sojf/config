<?php
namespace Sojf\Config;


use Sojf\Config\Exceptions\ConfigException;
use Sojf\Config\Interfaces\Parser;

class Config extends AbstractConfig implements \IteratorAggregate
{
    protected $cache; // 缓存目录

    protected $cacheData; // 缓存数据
    protected $_cacheData; // 文件加载的缓存数据

    protected $loadFile = array(); // 加载的文件路径

    protected $dataHash = array(); // 数据hash

    protected $noSync = array(); // 不同步的key

    protected $parser = array( // 配置文件加载器
        \Sojf\Config\Parser\Ini::EXT => \Sojf\Config\Parser\Ini::class,
        \Sojf\Config\Parser\Json::EXT => \Sojf\Config\Parser\Json::class,
        \Sojf\Config\Parser\Php::EXT => \Sojf\Config\Parser\Php::class,
        \Sojf\Config\Parser\Xml::EXT => \Sojf\Config\Parser\Xml::class,
        \Sojf\Config\Parser\Yml::EXT => \Sojf\Config\Parser\Yml::class
    );

    /**
     * 构造函数
     * Config constructor.
     * @param string $path 加载路径
     * @param string $cache 缓存目录
     */
    public function __construct($path = '', $cache = '')
    {
        // 设置缓存
        if ($cache) {
            $this->cache = $cache . '/' . 'conf.cache';
        }

        if ($path) {
            $this->load($path);
        }
    }

    /**
     * 同步数据到文件
     */
    public function sync()
    {
        foreach ($this->loadFile as $path => $key) {

            if (in_array($key, $this->noSync)) {
                
                continue;
            }
            
            if (key_exists($key, $this->data)) {

                if ($this->dataHash[$key] !== $this->hash($this->data[$key])) {

                    $this->save($path, $this->data[$key]);
                }
            }
        }
    }

    /**
     * 计算数据hash
     * @param $data
     * @return string
     */
    protected function hash($data)
    {
        return md5(json_encode($data));
    }

    /**
     * 设置不同步的数据
     * @param $key
     * @return $this
     */
    public function noSync($key)
    {
        $this->noSync[] = $key;
        return $this;
    }


    /**
     * 加载文件
     * @param $path
     * @return array
     */
    public function load($path)
    {
        $paths = $this->paths($path); // 获取文件路径数组

        foreach ($paths as $path) {

            $info = $this->pathInfo($path); // 获取文件信息

            // 缓存检查
            if ($this->has_cache($info['path'])) {
                continue; // 有缓存
            }

            // 解析配置文件
            /** @var Parser $parser */
            $parser = $this->parser($info['extension']); // 通过文件后缀获取加载器
            $data = $parser->parse($path); // 解析文件

            $key = $info['filename']; // 文件名为key

            $this->loadFile[$info['path']] = $key; // 保存加载的文件
            $this->dataHash[$key] = $this->hash($data); // 计算数据hash值
            $this->data = array_replace($this->data, array( // 添加数据到对象data数组
                $key => $data
            ));

            // 生成缓存数据
            $this->cache($key, $path, $data);
        }

        $this->saveCache(); // 保存缓存数据
        return $this->data;
    }

    /**
     * 是否使用缓存
     * @param $path
     * @return bool
     */
    protected function has_cache($path)
    {
        if (!$this->cache) { // 没有开启缓存
            return false;
        }

        $cacheFile = $this->cache; // 缓存文件路径
        if (is_file($cacheFile)) {

            if ($this->_cacheData) {
                $cacheData = $this->_cacheData;
            } else {
                // 读取缓存文件
                $cacheData = unserialize(file_get_contents($cacheFile));
                $this->_cacheData = $cacheData;
            }

            $path = realpath($path); // 矫正路径格式

            // 生成比较文件hash
            $curr = md5(file_get_contents($path));

            // 缓存文件的hash
            $cache = isset($cacheData[$path]) ? $cacheData[$path]['hash'] : '';

            // 已缓存
            if ($curr == $cache) {

                $key = $cacheData[$path]['key']; // 旧key
                $data = $cacheData[$path]['data']; // 旧数据

                // 保存缓存数据到config对象使用
                $this->loadFile[$path] = $key;
                $this->dataHash[$key] = $this->hash($data);
                $this->data = array_replace($this->data, array(
                    $key => $data
                ));

                return true;

            } else { // 文件改变了
                return false;
            }
        } else { // 没有缓存
            return false;
        }
    }

    /**
     * 添加缓存数据
     * @param $key
     * @param $path
     * @param $data
     * @return bool
     */
    protected function cache($key, $path, $data)
    {
        if (!$this->cache) {
            return false;
        }

        $path = realpath($path); // 矫正路径格式

        $this->cacheData[$path]['key'] = $key;
        $this->cacheData[$path]['data'] = $data;
        $this->cacheData[$path]['hash'] = md5(file_get_contents($path));
    }

    /**
     * 保存缓存数据
     * @return bool|int
     */
    protected function saveCache()
    {
        if (!$this->cache || !$this->cacheData) {
            return false;
        }

        $cacheFile = $this->cache; // 缓存文件
        $cacheFileData = is_file($cacheFile) ? file_get_contents($cacheFile) : ''; // 读取旧缓存数据
        if (!$this->serialized($cacheFileData)) {
            $oldCacheData = array(); // 没有旧缓存数据
        } else {
            $oldCacheData = unserialize($cacheFileData); // 反序列化旧缓存数据
        }

        // 替换掉旧缓存数据
        foreach ($this->cacheData as $file => $change) {
            $oldCacheData[$file] = $change;
        }

        // 保存新缓存数据
        $newCacheDate = $oldCacheData;
        $cacheJson = serialize($newCacheDate);
        return file_put_contents($cacheFile, $cacheJson);
    }

    /**
     * 写入数据到指定文件
     * @param $path
     * @param $data
     */
    public function save($path, $data)
    {
        $info = $this->pathInfo($path);

        /** @var Parser $parser */
        $parser = $this->parser($info['extension']);

        $parser->save($path, $data);
    }

    /**
     * 获取文件信息
     * @param $path
     * @return array
     */
    protected function pathInfo($path)
    {
        $info = pathinfo($path);

        if (!isset($info['extension'])) {
            throw new ConfigException("path not found extension: $path");
        }

        $add = array(
            'path' => $info['dirname'] . DIRECTORY_SEPARATOR . $info['basename'], // 文件全路径
        );

        return array_merge($info, $add);
    }

    /**
     * 通过后缀获取加载器
     * @param $extension
     * @return mixed
     */
    protected function parser($extension)
    {
        if (isset($this->parser[$extension])) {
            
            return new $this->parser[$extension];
        }             
        
        throw new ConfigException("Config not support extension : $extension ");
    }

    /**
     * 返回文件路径数组
     * @param $path
     * @return array
     */
    protected function paths($path)
    {
        $paths = array();
        if (is_file($path)) {

            $paths[] = $path;
        } elseif (is_dir($path)) {

            $paths = glob($path . '/*.*');
        } else {

            throw new ConfigException("Config error path.");
        }

        return $paths;
    }

    /**
     * 检查字符串是否序列化
     * @param $data
     * @return bool
     */
    protected function serialized($data)
    {
        // if it isn't a string, it isn't serialized
        if (!is_string($data))
            return false;
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }

    /**
     * 迭代器
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
