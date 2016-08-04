<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

class Xml implements Parser
{

    const EXT = 'xml';

    public function toString(array $data, $rootNode = 'root', $version = '1.0', $encoding = 'UTF-8')
    {
        $content = $this->asXml($data, $rootNode, $version, $encoding);

        return $content;
    }
    
    public function save($path, array $data)
    {
        $content = $this->asXml($data);
        
        if (false === file_put_contents($path, $content)) {

            throw new ConfigParseException(array(

                'message' => "failed to write file $path for writing."
            ));
        }
        return true;
    }

    protected function asXml(array $data, $rootNode = 'config', $version = '1.0', $encoding = 'gbk')
    {
        $node = new \SimpleXMLElement("<?xml version='$version' encoding='$encoding' ?><$rootNode></$rootNode>");

        $this->array_to_xml($data, $node);

        /** @var \DOMDocument $dom */
        $dom = dom_import_simplexml($node)->ownerDocument;
        $dom->formatOutput = true;

        return $dom->saveXML();
    }
    
    /**
     * @param $array
     * @param $node \SimpleXMLElement
     */
    protected function array_to_xml($array, &$node) {
                
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)){
                    
                    $subNode = $node->addChild("$key");
                    $this->array_to_xml($value, $subNode);
                }else{

                    $subNode = $node->addChild("item$key");
                    $this->array_to_xml($value, $subNode);
                }
            }else {

                $node->addChild("$key",htmlspecialchars("$value"));
            }
        }
    }

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

    public function supportedExtension()
    {
        return 'xml';
    }
}
