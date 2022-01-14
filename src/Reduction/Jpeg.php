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

class Jpeg implements Image {

    public function __construct(Logger $log) {
        $this->log = $log;
    }

    public function getAngle() {

        // соответствие orientation углу поворота изображения
        $mapping = [
            1 => 0,
            3 => 180,
            6 => -90,
            8 => 90
        ];

        if (array_key_exists($this->orientation, $mapping)) {
            return $mapping[$this->orientation];
        } else {
            throw new AppException(
                __METHOD__." Не определён угол поворота для Orientation {$this->orientation} {$this->path}"
            );
        }
    
    }

    public function getRealWidth() {

        switch ($this->orientation) {
            case 1:
                return $this->width;
                break;
            case 6:
                return $this->height;
                break;
            case 8:
                return $this->height;
                break;
            case 3:
                return $this->width;
                break;
            default:
                throw new AppException(
                    __METHOD__." Не определён угол поворота для Orientation {$this->orientation} {$this->path}"
                );
                break;
        }
    }

    public function getRealHeight() {

        switch ($this->orientation) {
            case 1:
                return $this->height;
                break;
            case 6:
                return $this->width;
                break;
            case 8:
                return $this->width;
                break;
            case 3:
                return $this->height;
                break;
            default:
                throw new AppException(
                    __METHOD__." Не определён угол поворота для Orientation {$this->orientation} {$this->path}"
                );
                break;
        }
    }

    public function getRealAspectRatio() {
        return $this->getRealWidth()/$this->getRealHeight();
    }

    public function buildNewImage($width, $height) {
        // уменьшение исходного изображения, перезапись файла

        try {
            $src = imagecreatefromjpeg($this->path);
            if ($src === false) {
                throw new AppException(__METHOD__." Невозможно создать ресурс из {$this->path}");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            return false;
        }

        if ($this->orientation !== 1) {
            // имеется ли вариант обработки изображения с такой ориентацией
            try {
                $angle = $this->getAngle();
            } catch (AppException $e) {
                $this->log->warning($e->getMessage());
                return false;
            }
            
            $src = imagerotate($src, $angle, 0);
        }

        $new = imagecreatetruecolor($width, $height);
        imagecopyresampled ($new, $src, 0, 0, 0, 0, $width, $height, $this->getRealWidth(), $this->getRealHeight());

        $effect = imagejpeg($new, $this->path, $this->quality);
        imagedestroy($new);
        imagedestroy($src);
        return $effect;
    }

}