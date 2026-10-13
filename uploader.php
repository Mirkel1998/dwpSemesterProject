<?php
// Standalone image-upload demonstration: inspect an uploaded image, then use
// Resizer to save a resized copy under img/. The form currently processes the
// height option; the other choices are displayed but are not handled here.
spl_autoload_register(function($class) {
    // Load the demo's Resizer class from the adjacent source file.
    include __DIR__ . '/reziser.php';
});
define("MAX_SIZE", 30000000000);
$upmsg = array();

if(isset($_POST['submit']))
    if ($_FILES['image']['name']) {
        // Inspect the temporary upload and continue only for recognized
        // JPEG, GIF, or PNG images smaller than the configured maximum.
        $imageName = $_FILES['image']['name'];
        $file = $_FILES['image']['tmp_name'];
        $imageType = getimagesize($file);
        if(($imageType[2] == 2)||($imageType[2] == 1)||($imageType[2]  == 3)) {
            $size = filesize($file);
            if($size < MAX_SIZE) {
               $prefix = uniqid();
               $newName = $prefix . "_" . $imageName;
               $storeImage = "img/" . $newName;
               $resOBJ = new Resizer();
               $resOBJ->load($file);
               if($_POST['resizetype'] == 'height') {
                // Preserve the original aspect ratio while setting the height.
                $height = $_POST['size'];
                $resOBJ->resizeToHeight($height);
                $resOBJ->save($storeImage);
            }
        }
    }
        // Handle the uploaded image here
    }
?>

<!-- Multipart form lets the browser send an image file alongside resize options. -->
<form name="imgup" method ="post" enctype="multipart/form-data" action="">
    Image <input type="file" name="image">
    Resize to: <select name="resizetype">
        <option value="width">Width</option>
        <option value="height">Height</option>
        <option value="scale">Both</option>
    </select>
    size: <input type="text" name="size">
    <input name="submit" type="submit" value="Upload"/>
    </form> 