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
            $line = new \stdClass();  // элемент списка, будет содержать: path, type, size 
            
            if ($file == "." || $file == "..") {
                continue;
            }

            $e = $file->getExtension();

            foreach ($this->patterns as $type => $pattern) {
                if (preg_match($pattern, $e)) {
                    if (in_array($type, $this->ableTypes)) {
                        $line->type = $type;
                        $line->path = $file->getPathname();
                    }
                }
            }
            // в зависимости от $mode решить включать ли изображение в список
            $target = false;

            if (isset($line->type)) {
                $target = $this->filter($file);
            }

            if ($target === true) {
                $line->size = $file->getSize();
                $this->list[] = $line;  // формируем массив из объектов
            }

            unset($line);
        }
        return $this;
    }

    private function filter($file) {
        
        if (in_array($this->mode, ["FileSize", "ImageSide"]) === false) {
            throw new AppException(__METHOD__." Не выбран режим фильтрации.");
        }

        if ($this->mode === "FileSize") {
            return ($file->getSize() > $this->maxFileSize) ?  true : false;
        }
        
        if ($this->mode === "ImageSide") {
            return $this->testingOnSide($file->getPathname());
        }
        
    }

    private function testingOnSide(string $path) {
        try {
            $is = getimagesize($path); // image size
            if ($is === false) {
                throw new AppException(__METHOD__." Невозможно получить сведения из {$path}");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            return false;
        }

        $aspectRatio = $is[0] / $is[1]; // ширинa / высоту

        switch ($aspectRatio) {
            case $aspectRatio > 1:
                return ($is[0] > $this->maxImageSide) ? true : false;
                break;
            case $aspectRatio < 1:
                return ($is[1] > $this->maxImageSide) ? true : false;
                break;
            case $aspectRatio = 1:
                return ($is[0] > $this->maxImageSide) ? true : false;
                break;
        }

    }
    
    private function reduct(string $file="", string $type="") {
 
        try {
            $is = getimagesize($file); // image size
            if ($is === false) {
                throw new AppException(__METHOD__." Невозможно получить сведения из {$file}");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            return false;
        }
        // по какой стороне уменьшать изображение?
        $aspectRatio = $is[0] / $is[1];

        switch ($aspectRatio) {
            case $aspectRatio > 1:
                $width = $this->maxWidth;
                $height = (int)($this->maxWidth/$aspectRatio);
                break;
            case $aspectRatio < 1:
                $height = $this->maxHeight;
                $width = (int)($this->maxHeight * $aspectRatio);
                break;
            case $aspectRatio = 1:
                $width = $this->maxHeight;
                $height = $this->maxHeight;
                break;
        }
        $funcs = [
            "jpeg" => ["imagecreatefromjpeg", "imagejpeg"],
            "png" => ["imagecreatefrompng", "imagepng"],
            "gif" => ["imagecreatefromgif", "imagegif"]
        ];
        // уменьшение исходного изображения, перезапись файла
        $func = $funcs[$type][0];
        try {
            $src = $func($file);
            if ($src === false) {
                throw new AppException(__METHOD__." Невозможно создать ресурс из {$file}");
            }
        } catch (AppException $e) {
            $this->log->error($e->getMessage());
            return false;
        }

        $new = imagecreatetruecolor($width, $height);
        imagecopyresampled ($new, $src, 0, 0, 0, 0, $width, $height, $is[0], $is[1]);
        $func = $funcs[$type][1];
        $effect = $func($new, $file);
        imagedestroy($new);
        return $effect;
    }
    
    public function reductAll() {
        $i = 0;

        foreach ($this->list as $line) {
            $effect = $this->reduct($line->path, $line->type);
            if ($effect === true) {
                $fs = filesize($line->path);
                $m = sprintf("%7d %s перезаписан, новый размер: %d", $i, $line->path, $fs);
                $this->log->info($m);
                $i++;
            } else {
                $m = sprintf("Файл %s не был перезаписан.", $line->path);
                $this->log->warning($m);
            }
        }

    }

    public function printList() {
        $ts = 0; // total size
        $this->log->info("Список выбранных изображений");

        foreach ($this->list as $key => $line) {
            $m = sprintf("%7d  %s  %s %d", $key, $line->path, $line->type, $line->size);
            $this->log->info($m);
            $ts = $ts + $line->size;
        }

        $c = count($this->list);
        $m = sprintf("Выбрано изображений: %d, общий объём выбранных файлов: %d байт.\n",
                $c, $ts);
        $this->log->info($m);        
        return $this;
    }
}