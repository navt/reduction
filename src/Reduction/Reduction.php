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
    private $maxFileSize;  // по размеру файла, с которого начнется фильтрация или
    private $maxImageSide; // по размеру длинной стороны изображения, с которого начнется фильтрация
    private $mode;       // "FileSize" / "ImageSide"

    private $maxWidth;   // ширина новых изображений, если изображения горизонтальные
    private $maxHeight;  // высота новых изображений, если изображения вертикальные
    private $ableTypes;  // типы принимаемых в работу файлов
    private $quality;    // quality для функкций типа imagejpeg, imagepng

    private $list = [];  // список обнаруженных файлов
    private $cpath;      // путь к конфигурационному файлу  
    private $patterns = [
        "jpeg" => "~^(jpg|jpeg)$~i",
        "png" => "~^png$~i",
        "gif" => "~^gif$~i"
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
            exit(2);
        }

        foreach ($a as $name => $value) {
            $this->$name = $value;
        }

        $this->log->info("Заданы значения:\n", $a);
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
            exit(1);
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
            exit(2);
        }
        
        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->folderPath), true);
        
        foreach ($ri as $file) {
            // элемент списка, будет содержать: type, path, size, width, height
            $image = new Image();      
            
            if ($file->isDot()) continue;

            $e = $file->getExtension();

            foreach ($this->patterns as $type => $pattern) {
                if (preg_match($pattern, $e)) {
                    if (in_array($type, $this->ableTypes)) {
                        $image->type = $type;
                        $image->path = $file->getPathname();
                        $image->size = $file->getSize();
                        try {
                            $output = [];
                            $command = sprintf("./bin/imagesizes -path %s", $image->path);
                            exec($command, $output);
                            $sizes = json_decode($output[0], true);

                            if ($sizes === null) {
                                throw new AppException(__METHOD__." imagesizes: {$output[0]}");
                            }

                        } catch (AppException $e) {
                            $this->log->error($e->getMessage());
                            $image->type = null; // считаем, что это не изображение
                            continue;
                        }
                        $image->width = $sizes["width"];
                        $image->height = $sizes["height"];
                        $image->aspectRatio = $image->width/$image->height;
                    }
                }
            }
            // в зависимости от $mode решить включать ли изображение в список
            $target = false;

            if (isset($image->type)) {
                $target = $this->filter($image);
            }

            if ($target === true) {
                $this->list[] = $image;  // формируем массив из объектов
            }

            unset($image);
        }
        return $this;
    }

    private function filter(stdClass $image) {
        
        if (in_array($this->mode, ["FileSize", "ImageSide"]) === false) {
            throw new AppException(__METHOD__." Не выбран режим фильтрации.");
        }

        if ($this->mode === "FileSize") {
            return ($image->size > $this->maxFileSize) ?  true : false;
        }
        
        if ($this->mode === "ImageSide") {
            return $this->testingOnSide($image);
        }
        
    }

    private function testingOnSide(stdClass $image) {

        switch ($image->aspectRatio) {
            case $image->aspectRatio > 1:
                return ($image->width > $this->maxImageSide) ? true : false;
                break;
            case $image->aspectRatio < 1:
                return ($image->height > $this->maxImageSide) ? true : false;
                break;
            case $image->aspectRatio = 1:
                return ($image->width > $this->maxImageSide) ? true : false;
                break;
        }

    }
    
    private function reduct(stdClass $image) {

        switch ($image->aspectRatio) {
            case $image->aspectRatio > 1:
                $width = $this->maxWidth;
                $height = (int)($this->maxWidth/$image->aspectRatio);
                break;
            case $image->aspectRatio < 1:
                $height = $this->maxHeight;
                $width = (int)($this->maxHeight * $image->aspectRatio);
                break;
            case $aspectRatio = 1:
                $width = $this->maxHeight;
                $height = $this->maxHeight;
                break;
        }
        $funcs = [
            "jpeg" => "imagecreatefromjpeg",
            "png" => "imagecreatefrompng",
            "gif" => "imagecreatefromgif"
        ];
        // уменьшение исходного изображения, перезапись файла
        $func = $funcs[$image->type];
        try {
            $src = $func($image->path);
            if ($src === false) {
                throw new AppException(__METHOD__." Невозможно создать ресурс из {$image->path}");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            return false;
        }

        if ($image->aspectRatio < 1) {
            $new = imagecreatetruecolor( $height, $width);
            imagecopyresampled ($new, $src, 0, 0, 0, 0, $height, $width, $image->height, $image->width);
            $new = imagerotate($new, 90);
        } else {
            $new = imagecreatetruecolor($width, $height);
            imagecopyresampled ($new, $src, 0, 0, 0, 0, $width, $height, $image->width, $image->height);
        }

        $effect = $this->rewrite($new, $image);
        imagedestroy($new);
        return $effect;
    }

    private function rewrite($new, stdClass $image) {
        
        if ($image->type == "gif") {
            $effect = imagegif($new, $image->path);
        }
        if ($image->type == "jpeg") {
            $effect = imagejpeg($new, $image->path, $this->quality["jpeg"]);
        }
        if ($image->type == "png") {
            $effect = imagepng($new, $image->path, $this->quality["png"]);
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

        $m = sprintf("Перезаписано изображений: %d, общий объём новых файлов: %d B.\n",
                $i, $ts);
        $this->log->info($m);

    }

    public function printList() {
        $ts = 0; // total size
        $this->log->info("Список выбранных изображений");

        foreach ($this->list as $key => $image) {
            $m = sprintf("%7d  %s  %s %d B  %dx%d px", 
                $key, 
                $image->type, 
                $image->path, 
                $image->size, 
                $image->width, 
                $image->height);
            $this->log->info($m);
            $ts = $ts + $image->size;
        }

        $c = count($this->list);
        $m = sprintf("Выбрано изображений: %d, общий объём выбранных файлов: %d B.\n",
                $c, $ts);
        $this->log->info($m);        
        return $this;
    }
}