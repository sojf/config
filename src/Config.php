<?php
namespace Sojf\Config;


use Sojf\Config\Exceptions\ConfigException;
use Sojf\Config\Interfaces\Cache;
use Sojf\Config\Interfaces\Parser;
use Sojf\Config\Interfaces\Config as ConfigInterface;

/**
 * config 配置类，用于加载配置文件
 * 实现了IteratorAggregate，ArrayAccess接口
 * 同时继承了AbstractConfig抽象类，此类主要提供`.`式访问
 */
class Config extends DotAccess implements \IteratorAggregate, \ArrayAccess, ConfigInterface
{
    /**
     * @var Cache 缓存器
     */
    protected $cache;

    /**
     * @var array 配置数组
     */
    public $data = array();

    /**
     * @var array 记录加载的文件信息
     */
    protected $loadFile = array();

    /**
     * 缓存数据key，用来记录文件hash值
     */
    const FILE_HASH_KEY = '__FILE__MD5__HASH__';

    // 配置文件加载器
    protected $parser = array(
        \Sojf\Config\Parser\Ini::EXT  => \Sojf\Config\Parser\Ini::class,
        \Sojf\Config\Parser\Json::EXT => \Sojf\Config\Parser\Json::class,
        \Sojf\Config\Parser\Php::EXT  => \Sojf\Config\Parser\Php::class,
        \Sojf\Config\Parser\Xml::EXT  => \Sojf\Config\Parser\Xml::class,
        \Sojf\Config\Parser\Yml::EXT  => \Sojf\Config\Parser\Yml::class
    );

    /**
     * Config 构造函数
     * @param string $path
     * @param Cache $cache
     */
    public function __construct($path = '', Cache $cache = null)
    {
        // 设置缓存器
        if ($cache) {
            $this->cache = $cache;
        }

        // 加载配置文件
        if ($path) {
            $this->load($path);
        }
    }

    /**
     * 加载配置文件
     * @param $path
     * @return array
     */
    public function load($path)
    {
        // 获取文件路径数组
        $paths = $this->paths($path);

        // 遍历文件
        foreach ($paths as $path) {

            // 获取文件/目录信息
            $fileInfo = $this->pathInfo($path);

            // 存在缓存，或者不符合后缀格式，不加载配置文件
            if ($this->cached($fileInfo) || !isset($fileInfo['extension'])) {
                continue;
            }

            // 通过后缀获取解析器
            /** @var Parser $parser */
            $parser = $this->parser($fileInfo['extension']);

            if (!$parser) { // 解析器不存在
                continue;
            }

            // 解析配置文件
            $data = $parser->parse($path);

            if ($data) {
                $this->add($fileInfo, $data);
            }

            // 缓存数据
            $this->cache($fileInfo['path'], $data);
        }

        return $this->data;
    }

    /**
     * 缓存数据检测
     * @param $fileInfo
     * @return bool
     */
    protected function cached($fileInfo)
    {
        if (!$this->cache) { // 没有缓存驱动器
            return false;
        }

        // 配置文件路径
        $path = $fileInfo['path'];

        // 获取缓存数据
        $data = $this->cache->get($this->cachedKey($path));

        /*
         * 缓存数据存在，判断配置文件是否改动过，
         * 没有改动过使用缓存数据，
         * 改动过返回false
         * */
        if ($data && isset($data[self::FILE_HASH_KEY])) {

            // 获取配置文件上一版本hash
            $hash = $data[self::FILE_HASH_KEY];

            // 获取配置文件当前hash
            $curr = $this->hash($path);

            if ($hash === $curr) { // 没有修改过数据

                // 去掉FILE_HASH_KEY
                unset($data[self::FILE_HASH_KEY]);

                // 使用缓存数据
                $this->add($fileInfo, $data);
                return true;

            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 缓存数据
     * @param $path
     * @param array $data
     * @return bool
     */
    protected function cache($path, array $data)
    {
        if (!$this->cache) { // 没有缓存驱动器
            return false;
        }

        // 记录当前文件hash
        $data[self::FILE_HASH_KEY] = $this->hash($path);

        // 添加缓存数据
        return $this->cache->set($this->cachedKey($path), $data);
    }

    /**
     * 缓存数据key为文件路径，需要过滤下特殊字符
     * @param $path
     * @return mixed
     */
    protected function cachedKey($path)
    {
        // 过滤特殊字符
        return str_replace(array('\\', '/'), '_', $path);
    }

    /**
     * 设置缓存器
     * @param Cache $cache
     */
    public function setCache(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * 添加数据
     * @param $fileInfo
     * @param $data
     */
    protected function add($fileInfo, $data)
    {
        // 添加数据到对象data数组
        $this->data = array_replace($this->data, $data);

        // 配置数组hash
        $hash = $this->hash($data);

        // 配置文件中所有key
        $keys = array_flip(array_keys($data));

        // 记录加载文件数据，同步数据时要用到
        $this->loadFile[$fileInfo['path']] = array($hash, $keys);
    }

    /**
     * 写入数据到指定文件
     * @param $data
     * @param $path
     * @return mixed
     */
    public function save($data, $path)
    {
        $info = $this->pathInfo($path);
        $extension = isset($info['extension']) ? $info['extension'] : '';

        /** @var Parser $parser */
        $parser = $this->parser($extension);
        if (!$parser) {
            throw new ConfigException("Not supported type : " . $extension);
        }

        return $parser->save($path, $data);
    }

    /**
     * 同步数据到文件
     */
    public function sync()
    {
        foreach ($this->loadFile as $path => $date) {

            $hash = $date[0]; // 配置文件数组hash
            $keys = $date[1]; // 配置文件中所有key

            // 计算所有数据和这个文件中key的交集
            $intersect = array_intersect_key($this->data, $keys);

            // 判断是否修改过
            if ($hash !== $this->hash($intersect)) {
                $this->save($intersect, $path);
            }
        }
    }

    /**
     * 返回整个data数组
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * 获取有效文件信息
     * @param $path
     * @return array|bool
     */
    protected function pathInfo($path)
    {
        $add = array();
        $info = pathinfo($path);

        if (isset($info['extension'])) {
            $add = array(
                // 文件全路径
                'path' => realpath($info['dirname'] . DIRECTORY_SEPARATOR . $info['basename']),
            );
        }

        return array_merge($info, $add);
    }

    /**
     * 通过后缀获取加载器
     * @param $extension
     * @return bool
     */
    protected function parser($extension)
    {
        if (isset($this->parser[$extension])) {
            return new $this->parser[$extension];
        } else {
            return false;
        }
    }

    /**
     * 返回文件路径数组
     * @param $path
     * @return array
     */
    protected function paths($path)
    {
        if (is_file($path)) {
            $paths[] = $path;

        } elseif (is_dir($path)) {
            $paths = glob($path . '/*.*');

        } else {
            throw new ConfigException("Invalid path: " . $path);
        }

        return $paths;
    }

    /**
     * 返回hash
     * @param $data
     * @return string
     */
    protected function hash($data)
    {
        if (is_string($data) && is_file($data)) {
            return md5_file($data);
        } else {
            return md5(serialize($data));
        }
    }

    /**
     * 获取文件内容
     * @param $path
     * @return string
     */
    protected function file($path)
    {
        if (!is_file($path)) {
            throw new ConfigException('Invalid file: ' . $path);
        }

        if (!is_readable($path)) {
            throw new ConfigException('File not readable: ' . $path);
        }

        return file_get_contents($path, LOCK_SH);
    }

    /**
     * 迭代器
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * 输出data字符串
     * @return mixed
     */
    public function __toString()
    {
        return print_r($this->data, true);
    }

    /**
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->get($key) ? true : false;
    }

    /**
     * @param mixed $key
     * @return null
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        if ($this->get($key)) {
            $this->del($key);
        }
    }
}
