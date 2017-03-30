<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Interfaces\Parser;
use Sojf\Config\Exceptions\ConfigParseException;
use Symfony\Component\Yaml\Yaml as YamlParser;

/**
 * yaml解析器
 */
class Yml implements Parser
{
    const EXT = 'yml';

    /**
     * 解析yaml格式文件
     * @param $path
     * @return mixed
     * @throws ConfigParseException
     */
    public function parse($path)
    {
        try {
            $data = $this->toArray(file_get_contents($path, LOCK_SH));

        } catch (\Exception $exception) {
            throw new ConfigParseException(
                array(
                    'message'   => 'Error parsing YAML file',
                    'exception' => $exception,
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
     * 返回数组转yaml格式字符串
     * @param array $data
     * @return mixed
     */
    public function toString(array $data)
    {
        return YamlParser::dump($data);
    }

    /**
     * 解析yaml字符串返回数组
     * @param $str
     * @return mixed
     * @throws ConfigParseException
     */
    public function toArray($str)
    {
        try {
            $data = YamlParser::parse($str);

        } catch (\Exception $exception) {
            throw new ConfigParseException(
                array(
                    'message'   => 'Error parsing YAML file',
                    'exception' => $exception,
                )
            );
        }
        return $data;
    }
}
