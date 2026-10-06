//This is an example of a simple getter and setter class in PHP

<?php
class GettySetty {
    private $fullName;
    private $age;

    public function setFullName($localFullName) {
        $this->fullName = $localFullName;
    }

    public function setAge($localAge) {
        $this->age = $localAge;
    }

    public function getFullName() {
        return $this->fullName;
    }

    public function getAge() {
        return $this->age;
    }

}
/*
// DONT DO THIS SHIT DUMBASS
$testOBJ = new GettySetty();
$testOBJ -> setFullName("John Wick");
$testOBJ -> setAge(45);

echo $testOBJ -> getFullName() . "<br>";
echo $testOBJ -> getAge();
End of example usage
*/
?>