<?php

declare(strict_types=1);

class Test
{
    protected $name;

    public function __construct(\DateTime $name)
    {
        $this->name = $name;
    }

    public function format()
    {
        echo $this->name->format('c');
    }
}

