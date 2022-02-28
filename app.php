<?php
error_reporting(E_ALL);
ini_set('display_errors', "1");

chdir(__DIR__);
require_once __DIR__.'/vendor/autoload.php';

use navt\Reduction\Logger\Logger;
use navt\Reduction\Marker;
use navt\Reduction\Reduction;

$log = new Logger("data/app.log");
$marker = new Marker($log);

// создаём экземпляр Reduction, получаем список файлов, печатаем список
$reduct = new Reduction($log, "data/config.json");
$reduct->getList();
$reduct->printList();
$marker->sumUp();