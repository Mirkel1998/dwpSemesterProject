//This is an example of a simple getter and setter class in PHP

<?php
// Teaching example: store a name and age privately, with public methods to
// assign and retrieve those values. The usage example below is commented out.
class GettySetty {
    private $fullName;
    private $age;

    // Set the object's stored full name.
    public function setFullName($localFullName) {
        $this->fullName = $localFullName;
    }

    // Set the object's stored age.
    public function setAge($localAge) {
        $this->age = $localAge;
    }

    // Return the currently stored full name.
    public function getFullName() {
        return $this->fullName;
    }

    // Return the currently stored age.
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