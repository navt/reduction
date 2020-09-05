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
        $image->type = "jpeg";
        $image->width = 50;
        $image->height = 25;
        $image->orientation = 8;

        $this->assertEquals($image->getRealWidth(), 25);
        $this->assertEquals($image->getRealHeight(), 50);
        $this->assertEquals($image->getRealAspectRatio(), 0.5);
        $this->assertEquals($image->getAngle(), 90);

        $image->type = "png";

        $this->assertEquals($image->getAngle(), 0);

    }
}