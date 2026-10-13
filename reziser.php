<?php
// Image-resizing helper used by uploader.php. It loads JPEG/GIF/PNG files,
// creates resized image data with GD, and writes the result in a chosen format.
class Resizer{
    protected $image;
    protected $image_type;

    public function load($filename){
        // Detect the source format and use its matching GD image loader.
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];
        if($this->image_type== IMAGETYPE_JPEG){
            $this->image = imagecreatefromjpeg($filename);
        }elseif ($this->image_type == IMAGETYPE_GIF){
            $this->image = imagecreatefromgif($filename);
        }elseif ($this->image_type== IMAGETYPE_PNG){
            // Some PNG files contain an invalid iCCP profile that libpng reports while loading.
            $this->image = @imagecreatefrompng($filename);
        }
    }
    public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 100)
    {
    // Write the current in-memory image using the requested output format.
    if($image_type==IMAGETYPE_JPEG){
        imagejpeg($this->image, $filename, $compression);
    }elseif ($image_type==IMAGETYPE_GIF){
        imagegif($this->image, $filename);
    }elseif ($image_type==IMAGETYPE_PNG){
        imagepng($this->image, $filename);
    }
    }
    protected function getWidth(){
        // Read dimensions from the current in-memory image resource.
        return imagesx($this->image);
    }
    protected function getHeight(){
        return imagesy($this->image);
    }
    public function resizeToHeight($height){
        // Calculate the matching width so the source aspect ratio is preserved.
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth()*$ratio;
        $this->resize($width, $height);
    }
    public function resizeToWidth($width){
        // Calculate the matching height so the source aspect ratio is preserved.
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight()*$ratio;
        $this->resize($width, $height);
    }

    public function scale($scale){
        // Resize both dimensions by the supplied percentage.
        $width = $this->getWidth()*$scale/100;
        $height = $this->getHeight()*$scale/100;
        $this->resize($width, $height);
    }

    public function resize($width, $height){
        // Resample the original into a new true-color image of the target size.
        $new_image = imagecreatetruecolor($width,$height);
        imagecopyresampled($new_image, $this->image, 0,0,0,0,
            $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }
}
/*function doStuff($value){
    $size = new Resizer();
    $size->load("img.jpg");
    $size->resizeToWidth($value);
    $size->save("img/img.jpg");
}
doStuff(100);*/