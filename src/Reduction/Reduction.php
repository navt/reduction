<?php
/**
 * Этот файл часть reduction 
 * Reduction - класс для уменьшения размера изображений. Ввиду различного "устройства"
 * изображений разных типов, максимально корректная работа класса достигается выбором
 * одного типа изображения и фильтрацией по максимальному размеру файла или максимальному
 * размеру длинной стороны изображения. 
 *
 * @copyright Copyright (c) 2020, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

use Logger\Logger;
use Reduction\AppException;
use Reduction\Image;


class Reduction {
    
    private $log;        // логгер

    private $cpaph;      // путь к файлу конфигурации
    private $folderPath; // путь к директории изображений
                         // 2 варианта фильтрации изображений:
    private $mode;       // "FileSize" / "ImageSide"
    private $maxFileSize;  // по размеру файла, с которого начнется фильтрация или
    private $maxImageSide; // по размеру длинной стороны изображения, с которого начнется фильтрация

    private $maxWidth;   // ширина новых изображений, если изображения горизонтальные
    private $maxHeight;  // высота новых изображений, если изображения вертикальные
    private $ableTypes;  // типы принимаемых в работу файлов
    private $quality;    // quality для функкций типа imagejpeg, imagepng

    private $list = [];  // список обнаруженных файлов
  
    private $patterns = [
        "jpeg" => "~^(jpg|jpeg)$~i",
        "png" => "~^png$~i",
        "gif" => "~^gif$~i"
    ];

    private $classes = [
        IMAGETYPE_GIF => "Gif",
        IMAGETYPE_JPEG => "Jpeg",
        IMAGETYPE_PNG => "Png"
    ];

    public function __construct(Logger $log, string $cpath="data/config.json") {
        $this->cpath = $cpath;
        $this->log = $log;
        $s = $this->readConfig();
        try {
            $a = json_decode($s, true);
            if ($a === NULL) {
                throw new AppException(__METHOD__." json не может быть преобразован.");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            exit();
        }

        foreach ($a as $name => $value) {
            $this->$name = $value;
        }

        $this->log->info("Заданы значения:", $a);
    }

    private function readConfig() {
        try {
            if (is_file($this->cpath) === false) {
                $m = sprintf("%s Неверно задан путь к файлу конфигурации; %s", __METHOD__, $this->cpath);
                throw new AppException($m);
            }
            $s = file_get_contents($this->cpath);
            if ($s === false) {
                $m = sprintf("%s Файл конфигурации %s не читается.", __METHOD__, $this->cpath);
                throw new AppException($m);
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            exit();
        }
        return $s;
    }
    
    public function getList() {

        try {
            if (is_dir($this->folderPath) !== true) {
                throw new AppException(__METHOD__." По указанному пути {$this->folderPath} нет директории.");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            exit();
        }
        
        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->folderPath), true);
        $bad = []; // пути файлов, не являющихся корректными изображениями
        
        foreach ($ri as $file) {
            // не рассматриваем, если это не файл
            if ($file->isFile() === false) continue;

            $e = $file->getExtension();
            $isImageExtension = false;

            foreach ($this->patterns as $ext => $pattern) {
                if (preg_match($pattern, $e)) {
                    $isImageExtension = true;
                    $type = $ext;
                }
            }
            // не рассматриваем, если расширение не как у изображения
            if ($isImageExtension === false) continue;

            $exiftype = @exif_imagetype($file->getPathname());
            // не рассматриваем, если корректная сигнатура не обнаружена,
            // пишем в массив "побитых" изображений
            if ($exiftype === false) {
                $bad[] = $file->getPathname();
                continue;
            }
            // не рассматриваем, если нет в массиве ableTypes 
            if (in_array($type, $this->ableTypes) === false) {
                continue;
            }
            
            // элемент списка - объект, реализующий интерфейс Image, будет 
            // содержать свойства: type, path, size, width, height, orientation, quality
            if (key_exists($exiftype, $this->classes)) {
                $class = sprintf("Reduction\%s", $this->classes[$exiftype]);
                $image = new $class($this->log);
            } else {
                continue;
            }

            $image->type = $type;               // type
            $image->path = $file->getPathname();// path
            $image->size = $file->getSize();    // size

            switch ($exiftype) {                // orientation  
                case IMAGETYPE_JPEG:
                    $exif = exif_read_data($image->path);
                    
                    if (!empty($exif["Orientation"])) {
                        $image->orientation = $exif["Orientation"];
                    } else {
                        $image->orientation = 1;
                    }

                    $image->quality = $this->quality["jpeg"];
                    break;
                case IMAGETYPE_PNG:
                    $image->orientation = 1;
                    $image->quality = $this->quality["png"];
                    break;
                case IMAGETYPE_GIF:
                    $image->orientation = 1;
                    break;
                default:
                    break;
            }

            $is = getimagesize($image->path);
            $image->width = $is[0];           // width
            $image->height = $is[1];          // height

            // включать ли изображение в список?
            $target = $this->filter($image);

            if ($target === true) {
                $this->list[] = $image;  // пишем объект в массив
            }

            unset($image);
        }

        if (count($bad) > 0) {
            $this->log->notice("Файл не является корректным изображением:", $bad);
        }

        return $this;
    }

    private function filter(Image $image) {
        
        if (in_array($this->mode, ["FileSize", "ImageSide"]) === false) {
            throw new AppException(__METHOD__." Не выбран режим фильтрации.");
        }

        if ($this->mode === "FileSize") {
            return ($image->size > $this->maxFileSize) ?  true : false;
        }
        
        if ($this->mode === "ImageSide") {
            return ($image->width > $this->maxImageSide || $image->height > $this->maxImageSide)
                ? true : false;
        }
        
    }

    private function reduct(Image $image) {
        try {
            $rar = $image->getRealAspectRatio();

            switch ($rar) {
                case $rar > 1:
                    $width = $this->maxWidth;
                    $height = (int)($this->maxWidth/$rar);
                    break;
                case $rar < 1:
                    $height = $this->maxHeight;
                    $width = (int)($this->maxHeight * $rar);
                    break;
                case $rar == 1:
                    $width = $this->maxHeight;
                    $height = $this->maxHeight;
                    break;
            }
            // уменьшение исходного изображения, перезапись файла
            $effect = $image->buildNewImage($width, $height);
            
        // все исключения из классов Jpeg, Png, Gif перехватываются здесь:
        } catch (AppException $e) {
            $this->log->warning($e->getMessage());
            return false;
        }
        
        return $effect;
    }
    
    public function reductAll() {
        $i = 0;
        $ts = 0; // total size

        try {
            if (!($this->quality["jpeg"] >= -1 && $this->quality["jpeg"] <= 100)) {
                throw new AppException(__METHOD__." Неверно задано quality для jpeg: {$this->quality["jpeg"]}");
            }
            if (!($this->quality["png"] >= -1 && $this->quality["png"] <= 9)) {
                throw new AppException(__METHOD__." Неверно задано quality для png: {$this->quality["png"]}");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            exit();
        }

        foreach ($this->list as $image) {
            $effect = $this->reduct($image);
            if ($effect === true) {
                $fs = filesize($image->path);
                $m = sprintf("%7d %s перезаписан, новый размер: %d", $i, $image->path, $fs);
                $this->log->info($m);
                $i++;
                $ts = $ts + $fs;
            } else {
                $m = sprintf("Файл %s не был перезаписан.", $image->path);
                $this->log->warning($m);
            }
        }

        $m = sprintf("Перезаписано изображений: %d, общий объём новых файлов: %d B.",
                $i, $ts);
        $this->log->info($m);

    }

    public function printList() {
        $ts = 0; // total size
        $this->log->info("Список выбранных изображений");

        foreach ($this->list as $key => $image) {
            $m = sprintf("%7d  %s  %s %d B  %dx%d px (%d)", 
                $key, 
                $image->type, 
                $image->path, 
                $image->size, 
                $image->getRealWidth(), 
                $image->getRealHeight(),
                $image->orientation);
            $this->log->info($m);
            $ts = $ts + $image->size;
        }

        $c = count($this->list);
        $m = sprintf("Выбрано изображений: %d, общий объём выбранных файлов: %d B.",
                $c, $ts);
        $this->log->info($m);        
        return $this;
    }

    public function getVar(string $name="") {
        return isset($this->$name) ? $this->$name : null;
    }
}