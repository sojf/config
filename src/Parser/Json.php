<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

class Json implements Parser
{
    
    const EXT = 'json';

    public function save($path, array $data)
    {
        $content = json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
        if (false === file_put_contents($path, $content)) {
            
            throw new ConfigParseException(array(
                
                'message' => "failed to write file $path for writing."
            ));
        }
        return true;
    }

    public function parse($path)
    {
        $data = json_decode(file_get_contents($path), true);

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

    public function supportedExtension()
    {
        return 'json';
    }
}
