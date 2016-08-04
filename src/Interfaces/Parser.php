<?php
namespace Sojf\Config\Interfaces;


interface Parser
{
    public function parse($path);

    public function save($path, array $data);
}