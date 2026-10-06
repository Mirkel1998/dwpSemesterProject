<?php

//this is a simple class demonstrating public, private, and protected properties in PHP

class Vissy {
    public $one = "public";
    private $two = "private";
    protected $three = "protected";

    function __construct() 
    {
        echo $this->one . "<br>";
        echo $this->two . "<br>";
        echo $this->three . "<br>";
    }

    public function change($two2) {
        $this->two = $two2;
        echo $this->two . "<br>";
    }
}
$vissy = new Vissy();
$vissy->change("Kim2 cause 2 kim is not 2 good");
//echo $vissy->one;
?>