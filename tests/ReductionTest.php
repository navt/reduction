<?php
chdir(__DIR__);
require_once dirname(__DIR__).'/vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use navt\Reduction\Logger\Logger;
use navt\Reduction\Reduction;

use navt\Reduction\Image;
use navt\Reduction\Jpeg;
use navt\Reduction\Png;

class ReductionTest extends TestCase {
    
    public $paths = [];
    public $reduct;
    public $log;

    protected function setUp():void {
        $this->log = new Logger("../data/app.log", false);
        $this->reduct = new Reduction($this->log, "test-data/conf-c.json");
    }

    public function testConstruct() {

        $this->assertEquals($this->getSomeProperty($this->reduct, "cpath"), "test-data/conf-c.json");
        $this->assertEquals($this->getSomeProperty($this->reduct, "mode"), "ImageSide");
        $this->assertEquals($this->getSomeProperty($this->reduct, "maxImageSide"), 480);
        $this->assertEquals($this->getSomeProperty($this->reduct, "ableTypes"), 
            ["jpeg",
            "png",
            "gif"]);
        $this->assertEquals($this->getSomeProperty($this->reduct, "quality"), 
            ["jpeg" => 75,
            "png" => 6]);

    }

    public function testJpeg() {
        
        $jpeg = new Jpeg();
        $jpeg->width = 250;
        $jpeg->height = 500;
        $jpeg->orientation = 8;

        $this->assertEquals($jpeg->getAngle(), 90);
        $this->assertEquals($jpeg->getRealWidth(), 500);
        $this->assertEquals($jpeg->getRealHeight(), 250);
        $this->assertEquals($jpeg->getRealAspectRatio(), 2.0);

    }

    public function testFullCicle() {
        $q = 5;
        // создаем $q тестовых изображений размером 500х250
        $this->generate($q, 500, "test-images");        
        // получаем список в соответствии с conf-c.json
        $this->reduct->getList();
        
        // частично смотрим, правильно ли выбралось
        $list = $this->getSomeProperty($this->reduct, "list");
        $this->assertEquals(count($list), $q);
        
        $image = $list[rand(0, $q-1)];
        $this->assertEquals($image->type, "jpeg");
        $this->assertEquals($image->width, 500);
        $this->assertEquals($image->height, 250);
        $this->assertEquals($image->size, 2738);
        $this->assertEquals($image->orientation, 1);
        $list = null;

        // уменьшаем по ширине до 220 px 
        $this->reduct->reductAll();
        // смотрим на новые размеры изображений
        foreach ($this->paths as $path) {
            $size = getimagesize($path);
            $this->assertEquals($size[0], 220);
            $this->assertEquals($size[1], 110);
        }
                
        // удаляем тестовые изображения
        $this->delete();
    
    }

    public function getSomeProperty($object, $property) {
        $refl = new ReflectionClass($object);
        $prop = $refl->getProperty($property);
        $prop->setAccessible(true);
        return $prop->getValue($object);
     }

    public function generate($qPic = 5, $width = 500, $dir = "test-images") {
        
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $height = (int)$width/2;

        for ($i = 0; $i < $qPic; $i++) { 
            $im = imagecreatetruecolor($width, $height);
            $color = imagecolorallocate($im, 5, 0, 254);
            imagefill($im, 0, 0, $color);
            $path = sprintf("%s/%s.jpeg", $dir, $i);

            if (imagejpeg($im, $path, 75) === true) {
                $this->paths[] = $path;
            }

        }

    }

    public function delete() {
        foreach ($this->paths as $path) {
            unlink($path);
        }
    }

}