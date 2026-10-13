<?php

// Teaching example for PHP visibility: construct an object with public,
// private, and protected properties, print them internally, then modify the
// private property through a public method.

class Vissy {
    public $one = "public";
    private $two = "private";
    protected $three = "protected";

    function __construct() 
    {
        // Class methods can access all three visibility levels on this object.
        echo $this->one . "<br>";
        echo $this->two . "<br>";
        echo $this->three . "<br>";
    }

    public function change($two2) {
        // Callers can change the private property only through this method.
        $this->two = $two2;
        echo $this->two . "<br>";
    }
}
$vissy = new Vissy();
$vissy->change("Kim2 cause 2 kim is not 2 good");
//echo $vissy->one;
?>