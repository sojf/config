<?php
namespace Sojf\Config;


use Sojf\Config\Exceptions\ConfigException;
use Sojf\Config\Interfaces\Parser;

class Config extends AbstractConfig implements \IteratorAggregate
{
    protected $loadFile = array();

    protected $dataHash = array();

    protected $noSync = array();

    protected $parser = array(

        \Sojf\Config\Parser\Ini::EXT => \Sojf\Config\Parser\Ini::class,
        \Sojf\Config\Parser\Json::EXT => \Sojf\Config\Parser\Json::class,
        \Sojf\Config\Parser\Php::EXT => \Sojf\Config\Parser\Php::class,
        \Sojf\Config\Parser\Xml::EXT => \Sojf\Config\Parser\Xml::class,
        \Sojf\Config\Parser\Yml::EXT => \Sojf\Config\Parser\Yml::class
    );
    
    public function __construct($path = '')
    {
        if ($path) {
            
            $this->load($path);
        }
    }
    
    public function sync()
    {
        foreach ($this->loadFile as $path => $key) {

            if (in_array($key, $this->noSync)) {
                
                continue;
            }
            
            if (key_exists($key, $this->data)) {

                if ($this->dataHash[$key] !== $this->hash($this->data[$key])) {

                    $this->save($path, $this->data[$key]);
                }
            }
        }
    }

    protected function hash($data)
    {
        return md5(json_encode($data));
    }

    public function noSync($key)
    {
        $this->noSync[] = $key;
        return $this;
    }


    public function load($path)
    {
        $paths = $this->paths($path);

        foreach ($paths as $path) {

            $info = $this->pathInfo($path);

            /** @var Parser $parser */
            $parser = $this->parser($info['extension']);

            $data = $parser->parse($path);

            $key = $info['filename'];
            
            $this->loadFile[$info['path']] = $key;

            $this->dataHash[$key] = $this->hash($data);

            $this->data = array_replace($this->data, array(
                $key => $data
            ));
        }

        return $this->data;
    }

    public function save($path, $data)
    {
        $info = $this->pathInfo($path);

        /** @var Parser $parser */
        $parser = $this->parser($info['extension']);

        $parser->save($path, $data);
    }

    protected function pathInfo($path)
    {
        $info = pathinfo($path);

        if (!isset($info['extension'])) {

            throw new ConfigException("path not found extension: $path");
        }

        $add = array(
            'path' => $info['dirname'] . DIRECTORY_SEPARATOR . $info['basename'],
        );

        return array_merge($info, $add);
    }

    protected function parser($extension)
    {
        if (isset($this->parser[$extension])) {
            
            return new $this->parser[$extension];
        }             
        
        throw new ConfigException("Config not support extension : $extension ");
    }

    protected function paths($path)
    {
        $paths = array();
        if (is_file($path)) {

            $paths[] = $path;
        } elseif (is_dir($path)) {

            $paths = glob($path . '/*.*');
        } else {

            throw new ConfigException("Config error path.");
        }

        return $paths;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
