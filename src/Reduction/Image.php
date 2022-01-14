<?php
/**
 * Этот файл часть reduction 
 *
 * @copyright Copyright (c) 2022, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

interface Image {
    /*
     *  объект, реализующий Image, будет содержать свойства: 
     *  type, path, size, width, height, orientation, quality
     */
    public function getAngle(); 

    public function getRealWidth(); 

    public function getRealHeight();

    public function getRealAspectRatio();

    public function buildNewImage($width, $height);

}