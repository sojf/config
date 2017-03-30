<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

/**
 * xml解析器
 */
class Xml implements Parser
{
    const EXT = 'xml';

    /**
     * 解析xml数据
     * @param $path
     * @return mixed
     * @throws ConfigParseException
     */
    public function parse($path)
    {
        libxml_use_internal_errors(true);
        $data = simplexml_load_file($path, null, LIBXML_NOERROR);

        if ($data === false) {
            $errors      = libxml_get_errors();
            $latestError = array_pop($errors);
            $error       = array(
                'message' => $latestError->message,
                'type'    => $latestError->level,
                'code'    => $latestError->code,
                'file'    => $latestError->file,
                'line'    => $latestError->line,
            );
            throw new ConfigParseException($error);
        }

        return json_decode(json_encode($data), true);
    }

    /**
     * 保存数据
     * @param $path
     * @param array $data
     * @param string $rootNode
     * @param string $version
     * @param string $encoding
     * @return bool
     * @throws ConfigParseException
     */
    public function save($path, array $data, $rootNode = 'root', $version = '1.0', $encoding = 'gbk')
    {
        $content = $this->asXml($data, $rootNode, $version, $encoding);
        if (file_put_contents($path, $content, LOCK_EX)) {
            return true;

        } else {
            throw new ConfigParseException(array(
                'message' => "failed to write file $path for writing."
            ));
        }
    }

    /**
     * 返回数组转xml格式字符串
     * @param array $data
     * @param string $rootNode
     * @param string $version
     * @param string $encoding
     * @return string
     */
    public function toString(array $data, $rootNode = 'root', $version = '1.0', $encoding = 'UTF-8')
    {
        return $this->asXml($data, $rootNode, $version, $encoding);
    }

    /**
     * 解析xml字符串返回数组
     * @param $str
     * @return mixed
     * @throws ConfigParseException
     */
    public function toArray($str)
    {
        libxml_use_internal_errors(true);
        $data = simplexml_load_string($str, null, LIBXML_NOERROR);

        if ($data === false) {
            $errors      = libxml_get_errors();
            $latestError = array_pop($errors);
            $error       = array(
                'message' => $latestError->message,
                'type'    => $latestError->level,
                'code'    => $latestError->code,
                'file'    => $latestError->file,
                'line'    => $latestError->line,
            );
            throw new ConfigParseException($error);
        }

        return json_decode(json_encode($data), true);
    }

    /**
     * 数组转字符串
     * @param array $data
     * @param string $rootNode
     * @param string $version
     * @param string $encoding
     * @return string
     */
    protected function asXml(array $data, $rootNode = 'root', $version = '1.0', $encoding = 'gbk')
    {
        $node = new \SimpleXMLElement("<?xml version='$version' encoding='$encoding' ?><$rootNode></$rootNode>");
        $this->array_to_xml($data, $node);

        /** @var \DOMDocument $dom */
        $dom = dom_import_simplexml($node)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
    
    /**
     * 数组转xml
     * @param $array
     * @param $node \SimpleXMLElement
     */
    protected function array_to_xml($array, &$node) {
                
        foreach($array as $key => $value) {
            // 判断key是否数字
            if (is_numeric($key)) {
                $key = "item$key";
            }

            // 判断value是否数组
            if(is_array($value)) {
                $subNode = $node->addChild("$key");
                $this->array_to_xml($value, $subNode);

            } else {
                $node->addChild("$key", htmlspecialchars("$value"));
            }
        }
    }
}
