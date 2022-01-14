<?php
/**
 * Этот файл часть reduction 
 *
 * @copyright Copyright (c) 2022, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

use Logger\Logger;
use Reduction\AppException;
use Reduction\Image;
use \Imagick;

class Gif implements Image {

    public function __construct(Logger $log) {
        $this->log = $log;
    }

    public function getAngle() {
        return 0;
    }

    public function getRealWidth() {
        return $this->width;
    }

    public function getRealHeight() {
        return $this->height;
    }

    public function getRealAspectRatio() {
        return $this->getRealWidth()/$this->getRealHeight();
    }

    public function buildNewImage($width, $height) {
        
        if (extension_loaded("imagick") === false) {
            $this->log->error(__METHOD__." Модуль PHP imagick не загружен.");
            return false;
        }

        $src = new Imagick($this->path);
        $src->setFirstIterator();
        $src = $src->coalesceImages();
        
        do {
            $src->scaleImage($width, $height);
        } while ($src->nextImage());
        
        $src->setFirstIterator();
        $effect = $src->writeImages($this->path, true); 
        $src = $src->deconstructImages();
        
        return ($effect === true) ?  true : false;
    }
}