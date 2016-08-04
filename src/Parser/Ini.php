<?php
namespace Sojf\Config\Parser;


use Sojf\Config\Exceptions\ConfigParseException;
use Sojf\Config\Interfaces\Parser;

class Ini implements Parser
{
    const EXT = 'ini';
        
    public function save($path, array $data)
    {
        $content = $this->buildOutputString($data);
        
        if (false === file_put_contents($path, $content)) {
            
            throw new ConfigParseException(array(
                
                'message' => "failed to write file $path for writing."
            ));
        }
        return true;
    }

    protected function buildOutputString(array $sectionsarray)
    {
        $content = '';
        $sections = '';
        $globals  = '';
        
        if (!empty($sectionsarray)) {
            // 2 loops to write `globals' on top, alternative: buffer
            foreach ($sectionsarray as $section => $item) {
                if (!is_array($item)) {
                    $value    = $this->normalizeValue($item);
                    $globals .= $section . ' = ' . $value . PHP_EOL;
                }
            }
            $content .= $globals;
            foreach ($sectionsarray as $section => $item) {
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

    protected function normalizeValue($value)
    {
        if (is_bool($value)) {
            
            $value = $value === true ? 1 : 0;
            return $value;
        } elseif (is_numeric($value)) {
            
            return $value;
        } else {
            
            $value = '"' . $value . '"';
        }
        return $value;
    }
    
    public function parse($path)
    {
        if (!is_file($path)) {

            throw new ConfigParseException(array(
                'message' => 'file not found: ' . $path
            ));
        }

        if (!is_readable($path)) {

            throw new ConfigParseException(array(
                'message' => 'file not readable: ' . $path
            ));
        }

        return parse_ini_file($path, true, INI_SCANNER_NORMAL);
    }
}
