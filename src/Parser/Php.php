<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

/**
 * php数组解析器
 */
class Php implements Parser
{
    const EXT = 'php';

    /**
     * 解析php文件
     * @param $path
     * @return mixed
     * @throws ConfigParseException
     */
    public function parse($path)
    {
        try {
            $data = require $path;

        } catch (\Exception $exception) {
            throw new ConfigParseException(
                array(
                    'message'   => 'PHP file threw an exception',
                    'exception' => $exception,
                )
            );
        }

        if (!is_array($data)) {
            throw new ConfigParseException(
                array(
                    'message' => 'PHP file does not return an array'
                )
            );
        }

        return $data;
    }

    /**
     * 保存数据
     * @param $path
     * @param array $data
     * @return bool
     * @throws ConfigParseException
     */
    public function save($path, array $data)
    {
        $content = $this->toString($data);
        if (file_put_contents($path, $content, LOCK_EX)) {
            return true;

        } else {
            throw new ConfigParseException(array(
                'message' => "failed to write file $path for writing."
            ));
        }
    }

    /**
     * 返回php数组转php文件字符串
     * @param array $data
     * @return string
     */
    public function toString(array $data)
    {
        $php = <<<php
<?php

return %s;
php;
        return sprintf($php, var_export($data, true));
    }

    public function toArray($str)
    {
    }
}
