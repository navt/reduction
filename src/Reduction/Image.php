<?php
/**
 * Этот файл часть reduction 
 *
 * @copyright Copyright (c) 2020, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

use Reduction\AppException;
use \stdClass;

class Image extends stdClass {
    /*
     *  объект Image, будет содержать свойства: 
     *  type, path, size, width, height, orientation
     */
    public function getAngle() {
        
        if ($this->type !== "jpeg") {
            return 0;
        }
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
        
        if ($this->type !== "jpeg") {
            return $this->width;
        }

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

        if ($this->type !== "jpeg") {
            return $this->height;
        }

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

}