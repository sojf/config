<?php
namespace Sojf\Config;


use Sojf\Config\Exceptions\ConfigException;

/**
 * '.'方式访问数组实现类
 */
class DotAccess
{
    /**
     * @var array 数据
     */
    public $data = array();

    /**
     * 使用`.`方式获取数据
     * @param $key
     * @param null $default
     * @return bool|mixed|null
     */
    public function get($key, $default = null)
    {
        $this->isString($key); // 判断是否字符串

        if (isset($this->data[$key])) { // 存在直接返回
            return $this->data[$key];
        }

        $first = mb_strpos($key, '.');  // 查找 . 第一次出现的位置
        $last = mb_strrpos($key, '.');  // 查找 . 最后一次出现的位置
        $end = mb_strlen($key) - 1;     // 算出总index数量

        // . 存在并且 . 不是第一个，也是不最后一个
        if ($first !== false && $first !== 0 && $last !== $end) {

            $key = explode('.', $key); // 按. 拆分字符串成数组

            // 获取value
            if ($ret = $this->dotGet($this->data, $key)) {
                return $ret;
            }
        }

        /*
         * key不存在
         * default是闭包，调用
         * 不是闭包返回default
         * */
        if (is_callable($default)) {
            return call_user_func($default);
        } else {
            return $default;
        }
    }

    /**
     * 使用`.`方式获取数据
     * @param $player array  数据数组
     * @param $key  array   .格式字符串拆分后的数组
     * @param int $curr      当前索引
     * @return bool
     */
    protected function dotGet(&$player, $key, $curr = 0)
    {
        $next = $curr + 1; // 下一个元素

        if (isset($player[$key[$curr]])) { // 当前元素存在

            if (isset($key[$next])) { // 下个元素存在，继续递归

                return $this->dotGet($player[$key[$curr]], $key, $next);

            } else { // 下个元素不存在，说明到底了，直接返回value
                return $player[$key[$curr]];
            }
        } else { // 当前元素不存在，直接返回false
            return false;
        }
    }

    /**
     * 使用`.`方式设置数据
     * @param $key
     * @param $value
     */
    public function set($key, $value)
    {
        $this->isString($key); // 判断是否字符串

        $first = mb_strpos($key, '.');  // 查找 . 第一次出现的位置
        $last = mb_strrpos($key, '.');  // 查找 . 最后一次出现的位置
        $end = mb_strlen($key) - 1;     // 算出总index数量

        // . 存在并且 . 不是第一个，也是不最后一个
        if ($first !== false && $first !== 0 && $last !== $end) {

            $key = explode('.', $key); // 按. 拆分字符串成数组

            if (!isset($this->data[$key[0]])) {
                /*
                 * key还没有设置，
                 * 但是因为有 . 说明是一个数组，
                 * 需要先赋值一个空数据
                 * */
                $this->data[$key[0]] = array();
            }

            // 开始设置
            $this->dotSet($this->data[$key[0]], $key, $value);

        } else {

            // 没有 . 直接设置
            $this->data[$key] = $value;
        }
    }

    /**
     * 使用`.`方式设置数据
     * @param $player array 要设置的数组
     * @param $key array    .格式字符串拆分后的数组
     * @param $value mixed  用户设置的value
     * @param int $prev     当前索引
     * @return bool
     */
    protected function dotSet(&$player, $key, $value, $prev = 0)
    {
        $curr = $prev + 1; // 当前元素
        $next = $curr + 1; // 下个元素

        if (isset($key[$next])) { // 还有下一个元素

            if (!isset($player[$key[$curr]])) { // 当前元素还没设置，需要先赋值空数组
                $player[$key[$curr]] = array();
            }

            // 递归设置下一个元素
            return $this->dotSet($player[$key[$curr]], $key, $value, $curr);

        } else { // 没有下一个元素了，说明已经到底，可以设置value了

            if (is_array($player)) { // 上一个元素是个数组

                $player[$key[$curr]] = $value; // 设置value

            } else { // 上一个元素不是数组
                $player = array(
                    $key[$curr] => $value
                );
            }
            return true;
        }
    }

    /**
     * 使用`.`方式删除数组某个key
     * @param $key
     */
    public function del($key)
    {
        $this->isString($key); // 判断是否字符串

        if (isset($this->data[$key])) { // key存在直接删除
            unset($this->data[$key]);
        } else {

            $first = mb_strpos($key, '.');  // 查找 . 第一次出现的位置
            $last = mb_strrpos($key, '.');  // 查找 . 最后一次出现的位置
            $end = mb_strlen($key) - 1;     // 算出总index数量

            // . 存在并且 . 不是第一个，也是不最后一个
            if ($first !== false && $first !== 0 && $last !== $end) {
                $key = explode('.', $key);
                $this->dotDel($this->data, $key);
            }
        }
    }

    /**
     * 使用`.`方式删除数组某个key
     * @param $player array  要删除的数组
     * @param $key array    .格式字符串拆分后的数组
     * @param int $curr     当前索引
     * @return string
     */
    protected function dotDel(&$player, $key, $curr = 0)
    {
        $next = $curr + 1; // 下一个元素

        if (isset($player[$key[$curr]])) { // 当前元素存在

            if (isset($key[$next])) { // 下一个元素存在，继续递归

                return $this->dotDel($player[$key[$curr]], $key, $next);

            } else { // 到底了，没有下一个元素，可以直接删除了
                unset($player[$key[$curr]]);
                return '';
            }
        } else { // 当前元素不存在，直接返回
            return '';
        }
    }

    /**
     * 判断是否字符串
     * @param $key
     * @return $this
     */
    protected function isString($key)
    {
        if (!is_string($key)) {
            $type = gettype($key);
            throw new ConfigException("Invalid key type: $type");
        }
        return $this;
    }
}
