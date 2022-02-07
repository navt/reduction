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

class Png extends Image {

    public function getAngle(): int {
        return 0;
    }

    public function getRealWidth(): int {
        return $this->width;
    }

    public function getRealHeight(): int {
        return $this->height;
    }

    public function getRealAspectRatio(): float {
        return $this->getRealWidth()/$this->getRealHeight();
    }

    public function buildNewImage(int $width, int $height): bool {
        // уменьшение исходного изображения, перезапись файла
        $src = imagecreatefrompng($this->path);
        
        if ($src === false) {
            throw new AppException(__METHOD__." Невозможно создать ресурс из {$this->path}");
        }

        $new = imagecreatetruecolor($width, $height);
        
        // png изображения имеют прозрачность, поэтому
        imagealphablending($new, false); // накладываемый пиксель заменяет исходный
        imagesavealpha($new, true);      // сохранять информацию о прозрачности

        imagecopyresampled ($new, $src, 0, 0, 0, 0, $width, $height, $this->getRealWidth(), $this->getRealHeight());

        $effect = imagepng($new, $this->path, $this->quality);
        imagedestroy($new);
        imagedestroy($src);
        return $effect;
    }

}