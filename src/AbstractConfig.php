<?php
namespace Sojf\Config;


use Sojf\Config\Exceptions\ConfigException;

abstract class AbstractConfig
{
    public $data = array();
    public $prepend = array();

    abstract public function load($config);

    abstract public function save($path, $data);

    protected function dotGet(&$player, $key, $curr = 0)
    {
        $next = $curr + 1;
        if (isset($player[$key[$curr]])) {

            if (isset($key[$next])) {

                return $this->dotGet($player[$key[$curr]], $key, $next);
            } else {

                return $player[$key[$curr]];
            }
        } else {

            return false;
        }
    }

    public function get($key, $default = null)
    {
        if (isset($this->prepend[$key])) {
            return $this->prepend[$key];
        }

        $this->keyType($key);

        if (isset($this->data[$key])) {

            return $this->data[$key];
        }

        $pre = mb_strpos($key, '.');
        $post = mb_strrpos($key, '.');
        $end = mb_strlen($key) - 1;

        if ($pre !== 0 && $post !== $end && $pre !== false) {

            $key = explode('.', $key);

            if ($ret = $this->dotGet($this->data, $key)) {
                return $ret;
            }
        }

        if (is_callable($default)) {

            return call_user_func($default);
        } else {

            return $default;
        }
    }

    protected function dotSet(&$player, $key, $value, $prev = 0)
    {
        $curr = $prev + 1;
        $next = $curr + 1;
        if (isset($key[$next])) {

            if (!isset($player[$key[$curr]])) {

                $player[$key[$curr]] = array();
            }
            return $this->dotSet($player[$key[$curr]], $key, $value, $curr);
        } else {

            if (is_array($player)) {

                $player[$key[$curr]] = $value;
            } else {

                $player = array(
                    $key[$curr] => $value
                );
            }
            return true;
        }
    }

    public function set($key, $value, $prepend = false)
    {
        $k = $key;
        $this->keyType($key);

        $pre = mb_strpos($key, '.');
        $post = mb_strrpos($key, '.');
        $end = mb_strlen($key) - 1;

        if ($pre !== 0 && $post !== $end && $pre !== false) {

            $key = explode('.', $key);

            if (!isset($this->data[$key[0]])) {

                $this->data[$key[0]] = array();
            }

            $this->dotSet($this->data[$key[0]], $key, $value);
        } else {

            $this->data[$key] = $value;
        }

        if ($prepend) {
            $this->prepend[$k] = $value . $prepend;
        }

        return $this;
    }

    protected function dotDel(&$player, $key, $curr = 0)
    {
        $next = $curr + 1;
        if (isset($player[$key[$curr]])) {

            if (isset($key[$next])) {

                return $this->dotDel($player[$key[$curr]], $key, $next);
            } else {

                unset($player[$key[$curr]]);
                return '';
            }
        } else {

            return '';
        }
    }

    public function del($key)
    {
        $this->keyType($key);

        if (isset($this->data[$key])) {

            unset($this->data[$key]);
        } else {

            $pre = mb_strpos($key, '.');
            $post = mb_strrpos($key, '.');
            $end = mb_strlen($key) - 1;

            if ($pre !== 0 && $post !== $end && $pre !== false) {

                $key = explode('.', $key);

                $this->dotDel($this->data, $key);
            }
        }
    }

    protected function keyType($key)
    {
        if (!is_string($key)) {

            $type = gettype($key);
            throw new ConfigException("error key type: $type");
        }

        return $this;
    }

    public function all()
    {
        return $this->data;
    }
}
