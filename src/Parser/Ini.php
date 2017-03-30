<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

/**
 * ini解析器
 */
class Ini implements Parser
{
    const EXT = 'ini';

    /**
     * 解析ini文件
     * @param $path
     * @return array
     * @throws ConfigParseException
     */
    public function parse($path)
    {
        return parse_ini_file($path, true, INI_SCANNER_NORMAL);
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
        // 数组转成ini格式字符串
        $content = $this->buildOutputString($data);

        // 保存文件
        if (file_put_contents($path, $content, LOCK_EX)) {
            return true;

        } else {
            throw new ConfigParseException(array(
                'message' => "failed to write file $path for writing."
            ));
        }
    }

    /**
     * 返回数组转ini格式字符串
     * @param array $data
     * @return string
     */
    public function toString(array $data)
    {
        return $this->buildOutputString($data);
    }

    /**
     * 解析ini字符串返回数组
     * @param $str
     * @return array
     */
    public function toArray($str)
    {
        return parse_ini_string($str, true, INI_SCANNER_NORMAL);
    }

    /**
     * 数组转成ini格式字符串
     * @param array $sectionsArray
     * @return string
     */
    protected function buildOutputString(array $sectionsArray)
    {
        $content = '';
        $sections = '';
        $globals  = '';
        
        if (!empty($sectionsArray)) {
            // 2 loops to write `globals' on top, alternative: buffer
            foreach ($sectionsArray as $section => $item) {
                if (!is_array($item)) {
                    $value    = $this->normalizeValue($item);
                    $globals .= $section . ' = ' . $value . PHP_EOL;
                }
            }
            $content .= $globals;
            foreach ($sectionsArray as $section => $item) {
                if (is_array($item)) {
                    $sections .= PHP_EOL
                        . "[" . $section . "]" . PHP_EOL;
                    foreach ($item as $key => $value) {
                        if (is_array($value)) {
                            foreach ($value as $arrkey => $arrvalue) {
                                $arrvalue  = $this->normalizeValue($arrvalue);
                                $arrkey    = $key . '[' . $arrkey . ']';
                                $sections .= $arrkey . ' = ' . $arrvalue
                                    . PHP_EOL;
                            }
                        } else {
                            $value     = $this->normalizeValue($value);
                            $sections .= $key . ' = ' . $value . PHP_EOL;
                        }
                    }
                }
            }
            $content .= $sections;
        }
        return $content;
    }

    /**
     * 格式化value
     * @param $value
     * @return int|string
     * @throws ConfigParseException
     */
    protected function normalizeValue($value)
    {
        if (is_array($value) || is_object($value) || is_callable($value) || is_resource($value)) {
            // 非法value
            $type = gettype($value);
            throw new ConfigParseException(array(
                'message' => "failed value type : " . $type
            ));

        } elseif (is_bool($value)) {
            $value = $value === true ? 1 : 0;
            return $value;

        } elseif (is_numeric($value)) {
            return $value;

        } else {
            $value = '"' . $value . '"';
        }
        return $value;
    }
}
