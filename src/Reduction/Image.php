<?php
/**
 * Image - класс-шаблон для сущности Image, общей для классов Jpeg, Png, Gif
 * Этот файл часть reduction 
 *
 * @copyright Copyright (c) 2022, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

abstract class Image {

    /**
     * тип изображения: jpeg | png | gif
     * @var string
     */
    public string $type;

    /**
     * относительный путь к изображению
     * @var string
     */
    public string $path;

    /**
     * размер изображения в байтах
     * @var int
     */
    public int $size;

    /**
     * ширина в рх
     * @var int
     */
    public int $width;

    /**
     * высота в рх
     * @var int
     */
    public int $height;

    /**
     * ориентация  для jpeg: 1 | 3 | 6 | 8
     * @var int
     */
    public int $orientation;

    /**
     * качество для изображения нового размера
     * @var int
     */
    public int $quality;     

    abstract public function getAngle(): int; 

    abstract public function getRealWidth(): int; 

    abstract public function getRealHeight(): int;

    abstract public function getRealAspectRatio(): float;

    abstract public function buildNewImage(int $width, int $height): bool;

}