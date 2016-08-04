<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

class Php implements Parser
{

    const EXT = 'php';

    public function save($path, array $data)
    {
        $php = <<<php
<?php

return %s;
php;
        $content = sprintf($php, var_export($data, true));

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

    public function supportedExtension()
    {
        return 'php';
    }
}
