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
use \Imagick;

class Gif extends Image {

    private string $src = "";
    // взято отсюда: 
    // https://www.php.net/manual/ru/function.imagecreatefromgif.php#104473
    private string $pattern = '~\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)~s';

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
        
        $s = file_get_contents($this->path);

        if ($s === false) return false;

        $this->src = $s;
        // определяем имеется ли анимация в данном изображении
        $offset = 0;
        $count = 0;

        for ($i=0; $i < 2; $i++) { 
            $flag = preg_match($this->pattern, $this->src, $out, PREG_OFFSET_CAPTURE, $offset);
            $count+= $flag;
            if ($out != []) {
                $offset = $out[1][1] + 10;
            }
        }

        $isAni = ($count === 2) ? true : false;
        // используем gd или imagick в зависимости от ситуации
        if ($isAni === true) {
            return $this->useImagick($width, $height);
        } else {
            return $this->useGD($width, $height);
        }
    
    }

    private function useGD(int $width, int $height): bool {


        $image = imagecreatefromstring($this->src);
        if ($image === false) {
            throw new AppException(__METHOD__." Невозможно создать ресурс из данных файла {$this->path}");
        }

        $new = imagecreatetruecolor($width, $height);
        
        // получаем прозрачный цвет
        $transparent = imagecolortransparent($image);

        // проверяем наличие прозрачности
        if($transparent !== -1){
            $transparentColor = imagecolorsforindex($image, $transparent);
        
            if (is_array($transparentColor)) {
                // добавляем цвет в палитру нового изображения
                $destIndex=imagecolorallocate($new, $transparentColor['red'], $transparentColor['green'], $transparentColor['blue']);
                // и устанавливаем его как прозрачный
                imagecolortransparent($new, $destIndex);
                // заливаем фон этим цветом
                imagefill($new, 0, 0, $destIndex);
            }
            
        }

        imagecopyresampled ($new, $image, 0, 0, 0, 0, $width, $height, $this->getRealWidth(), $this->getRealHeight());

        $effect = imagegif($new, $this->path);
        imagedestroy($new);
        imagedestroy($image);
        $this->src = "";

        return $effect;
    }

    private function useImagick(int $width, int $height): bool {
        
        if (extension_loaded("imagick") === false) {
            throw new AppException(__METHOD__." Модуль PHP imagick не загружен.");
        }

        $image = new Imagick();
        $out = $image->readImageBlob($this->src);

        if ($out !== true) {
            throw new AppException(__METHOD__." Невозможно создать ресурс из данных файла {$this->path}");
        }

        $image->setFirstIterator();
        $image = $image->coalesceImages();
        
        do {
            $image->scaleImage($width, $height);
        } while ($image->nextImage());
        
        $image->setFirstIterator();
        $effect = $image->writeImages($this->path, true); 
        $image = $image->deconstructImages();
        $this->src = "";
        
        return ($effect === true) ?  true : false;
    }
}