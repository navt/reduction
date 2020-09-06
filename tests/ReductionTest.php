<?php
chdir(__DIR__);
require_once dirname(__DIR__).'/src/Loader/Loader.php';
\Loader\Loader::autoload(true, dirname(__DIR__)."/src");

use PHPUnit\Framework\TestCase;
use Logger\Logger;
use Reduction\Marker;
use Reduction\Reduction;
use Reduction\Image;

class ReductionTest extends TestCase {
    
    public function testImage() {
        $image = new Image();
        
        $image->width = 50;
        $image->height = 25;
        $image->orientation = 8;

        $image->type = "jpeg";
        $this->assertEquals($image->getRealWidth(), 25);
        $this->assertEquals($image->getRealHeight(), 50);
        $this->assertEquals($image->getRealAspectRatio(), 0.5);
        $this->assertEquals($image->getAngle(), 90);

        $image->type = "png";
        $this->assertEquals($image->getRealAspectRatio(), 2);
        $this->assertEquals($image->getAngle(), 0);
        
    }

    public function testConstruct() {
        $log = new Logger("../data/app.log", false);
        $reduct = new Reduction($log, "test-data/conf-c.json");

        $this->assertEquals($reduct->getVar("cpath"), "test-data/conf-c.json");
        $this->assertEquals($reduct->getVar("mode"), "ImageSide");
        $this->assertEquals($reduct->getVar("maxImageSide"), 96);
        $this->assertEquals($reduct->getVar("ableTypes"), 
            ["jpeg",
            "png",
            "gif"]);
        $this->assertEquals($reduct->getVar("quality"), 
            ["jpeg" => 75,
            "png" => 6]);
    }

    public function testGetList() {
        $log = new Logger("../data/app.log", false);
        $reduct = new Reduction($log, "test-data/conf-c.json");
        $reduct->getList();
        $list = $reduct->getVar("list");

        $this->assertEquals(count($list), 1);
        
        $image = $list[0];

        $this->assertEquals($image->type, "jpeg");
        $this->assertEquals($image->width, 150);
        $this->assertEquals($image->height, 200);
        $this->assertEquals($image->size, 25939);
        $this->assertEquals($image->orientation, 1);
        $this->assertEquals($image->path, "test-images/pic123.JPG");
    }
}