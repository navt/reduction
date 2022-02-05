<?php
/**
 * Этот файл часть reduction 
 *
 * @copyright Copyright (c) 2022, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

abstract class Image {

    public $type;        // jpeg | png | gif
    public $path;        // относительный путь к изображению
    public $size;        // размер изображения в байтах
    public $width;       // ширина в рх
    public $height;      // высота в рх
    public $orientation; // для jpeg
    public $quality;     // качество для изображения нового размера

    abstract public function getAngle(); 

    abstract public function getRealWidth(); 

    abstract public function getRealHeight();

    abstract public function getRealAspectRatio();

    abstract public function buildNewImage($width, $height);

}