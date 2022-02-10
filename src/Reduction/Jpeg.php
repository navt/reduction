<?php
/**
 * Этот файл часть reduction 
 *
 * @copyright Copyright (c) 2022, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

use Reduction\AppException;
use Reduction\Image;

class Jpeg extends Image {

    public function getAngle(): int {

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

    public function getRealWidth(): int {

        switch ($this->orientation) {
            case 1:
                return $this->width;
            case 6:
                return $this->height;
            case 8:
                return $this->height;
            case 3:
                return $this->width;
            default:
                throw new AppException(
                    __METHOD__." Не определён вариант поворота для Orientation {$this->orientation} {$this->path}"
                );
        }
    }

    public function getRealHeight(): int {

        switch ($this->orientation) {
            case 1:
                return $this->height;
            case 6:
                return $this->width;
            case 8:
                return $this->width;
            case 3:
                return $this->height;
            default:
                throw new AppException(
                    __METHOD__." Не определён вариант поворота для Orientation {$this->orientation} {$this->path}"
                );
        }
    }

    public function getRealAspectRatio(): float {
        return $this->getRealWidth()/$this->getRealHeight();
    }

    public function buildNewImage(int $width, int $height): bool {
        // уменьшение исходного изображения, перезапись файла
        $src = imagecreatefromjpeg($this->path);
        
        if ($src === false) {
            throw new AppException(__METHOD__." Невозможно создать ресурс из {$this->path}");
        }

        if ($this->orientation !== 1) {
            // имеется ли вариант обработки изображения с такой ориентацией
            // если угол не найдется getAngle() выбросит исключение
            $angle = $this->getAngle();
            $src = imagerotate($src, $angle, 0);
        }

        $new = imagecreatetruecolor($width, $height);

        imagecopyresampled (
            $new, $src, 0, 0, 0, 0, 
            $width, $height, $this->getRealWidth(), $this->getRealHeight()
        );

        $effect = imagejpeg($new, $this->path, $this->quality);
        imagedestroy($new);
        imagedestroy($src);
        return $effect;
    }

}