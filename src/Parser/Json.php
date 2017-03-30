<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

/**
 * json解析器
 */
class Json implements Parser
{
    const EXT = 'json';

    /**
     * 解析json文件
     * @param $path
     * @return mixed
     * @throws ConfigParseException
     */
    public function parse($path)
    {
        $data = $this->toArray(file_get_contents($path, LOCK_SH));

        if (json_last_error() !== JSON_ERROR_NONE) {
            $error_message  = 'Syntax error';
            if (function_exists('json_last_error_msg')) {
                $error_message = json_last_error_msg();
            }

            $error = array(
                'message' => $error_message,
                'type'    => json_last_error(),
                'file'    => $path,
            );

            throw new ConfigParseException($error);
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
     * 返回数组转json格式字符串
     * @param array $data
     * @return string
     */
    public function toString(array $data)
    {
        return json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析json字符串返回数组
     * @param $str
     * @return mixed
     */
    public function toArray($str)
    {
        return json_decode($str, true);
    }
}
