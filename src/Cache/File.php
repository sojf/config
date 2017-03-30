<?php
namespace Sojf\Config\Cache;


use Sojf\Config\Exceptions\CacheException;
use Sojf\Config\Interfaces\Cache;

/**
 * 文件缓存器
 */
class File implements Cache, \ArrayAccess
{
    protected $file; // 缓存文件
    protected $data = array(); // 缓存数据

    /**
     * 文件缓存器 constructor.
     * @param $file
     */
    public function __construct($file)
    {
        $this->file = $file;
        $this->open();
    }

    /**
     * 文件缓存器 destruct
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 打开缓存文件
     */
    public function open()
    {
        $cacheFile = $this->file;

        if (is_file($cacheFile)) {
            if (is_readable($cacheFile)) {
                $data = file_get_contents($cacheFile);

                if ($data) {
                    if ($this->serialized($data)) {
                        $this->data = unserialize($data);

                    } else {
                        throw new CacheException(array(
                            'message' => 'Error cache file: ' . $cacheFile
                        ));
                    }
                }
            } else {
                throw new CacheException(array(
                    'message' => 'File not readable: ' . $cacheFile
                ));
            }
        }
    }

    /**
     * 关闭缓存文件
     * @return int
     */
    public function close()
    {
        // 缓存文件路径
        $cacheFile = $this->file;

        // 读取旧缓存数据
        $cacheData = is_file($cacheFile) && is_readable($cacheFile) ? file_get_contents($cacheFile) : '';

        // 检查数据格式是否正确
        if ($this->serialized($cacheData)) {

            // 反序列化旧缓存数据
            $data = unserialize($cacheData);
        } else {
            // 没有旧缓存数据
            $data = array();
        }

        // 求旧数据和新数据交集
        $intersect = @array_intersect_assoc($data, $this->data);

        if ($intersect) {
            // 在交集的基础上替换或者添加新数据
            $data = array_replace($intersect, $this->data);

        } else {
            // 没有交集说明完全变了，直接覆盖
            $data = $this->data;
        }

        // 缓存新数据
        $cache = serialize($data);
        return file_put_contents($cacheFile, $cache, LOCK_EX);
    }

    /**
     * 通过key获取缓存数据
     * @param $key
     * @return null|mixed
     */
    public function get($key)
    {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    /**
     * 设置缓存数据
     * @param $key
     * @param $value
     * @return mixed
     */
    public function set($key, $value)
    {
        return $this->data[$key] = $value;
    }

    /**
     * 追加缓存数据
     * @param $data
     * @return array
     */
    public function add($data)
    {
        return $this->data = array_replace($this->data, $data);
    }

    /**
     * 通过key删除缓存数据
     * @param $key
     */
    public function del($key)
    {
        if (isset($this->data[$key])) {
            unset($this->data[$key]);
        }
    }

    /**
     * 返回所有缓存数据
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * 检查字符串是否序列化
     * @param $data
     * @return bool
     */
    protected function serialized($data)
    {
        if (!is_string($data)) {
            return false;
        }

        $data = trim($data);
        if ('N;' == $data) {
            return true;
        }

        if (!preg_match('/^([adObis]):/', $data, $badions)) {
            return false;
        }

        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data)) {
                    return true;
                }
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data)) {
                    return true;
                }
                break;
        }

        return false;
    }

    /**
     * get魔术方法
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return $this->get($key);
    }

    /**
     * set魔术方法
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * unset魔术方法
     * @param $key
     */
    public function __unset($key)
    {
        $this->del($key);
    }

    /**
     * 获取缓存文件内容
     * @return string
     */
    public function __toString()
    {
        return print_r($this->data, true);
    }

    /**
     * 数组方式访判断数据是否存在
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return key_exists($key, $this->data) ? true : false;
    }

    /**
     * 数组方式访问缓存数据
     * @param mixed $key
     * @return mixed|null
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * 数组方式设置缓存数据
     * @param mixed $key
     * @param mixed $value
     * @return $this|void
     */
    public function offsetSet($key, $value)
    {
        return $this->set($key, $value);
    }

    /**
     * 数组方式删除缓存数据
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        $this->del($key);
    }
}