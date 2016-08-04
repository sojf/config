<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Interfaces\Parser;
use Sojf\Config\Exceptions\ConfigParseException;
use Symfony\Component\Yaml\Yaml as YamlParser;

class Yml implements Parser
{

    const EXT = 'yml';

    public function save($path, array $data)
    {
        $content = YamlParser::dump($data);

        if (false === file_put_contents($path, $content)) {

            throw new ConfigParseException(array(

                'message' => "failed to write file $path for writing."
            ));
        }
        return true;
    }

    public function parse($path)
    {
        try {
            
            $data = YamlParser::parse(file_get_contents($path));
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

    public function supportedExtension()
    {
        return 'yml';
    }
}
