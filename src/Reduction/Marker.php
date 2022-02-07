<?php
/**
 * Этот файл часть reduction
 * Marker - класс для расстановки "маркеров" по ходу выполнения скрипта,
 * вывода интервалов времени, используемой памяти.
 *
 * @copyright Copyright (c) 2020, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Reduction;

use Logger\Logger;

class Marker {
    /**
     * @var Logger
     */
    private Logger $log;

    /**
     * @var array<float>
     */
    private array $markers = [];

    /**
     * @var array<int>
     */
    private array $volumes = [1, 1024, 1048576, 1073741824, 1099511627776];

    /**
     * @var array<string>
     */
    private array $units = ["B", "KB", "MB", "GB", "TB"];

    public function __construct(Logger $log) {
        $this->log = $log;
        
        // первая метка устанавливается по началу запроса к скрипту или
        // по факту создании экземпляра класса Marker
        if (isset($_SERVER["REQUEST_TIME_FLOAT"]) && is_float($_SERVER["REQUEST_TIME_FLOAT"])) {
            $this->markers[] = $_SERVER["REQUEST_TIME_FLOAT"];
        } else {
            $this->log->warning("0-й маркер установлен при создании экземпляра Marker.");
            $this->addMark();
        }
    }

    public function addMark(): void {
        $this->markers[] = microtime(true);
    }

    public function display(): void {
        
        if (count($this->markers) < 2) {
            $m = __METHOD__." Нет данных для отображения меток.";
            $this->log->warning($m);
        } else {
            $cEnd = count($this->markers)-1;

            for ($i = 0; $i < $cEnd; $i++) {
                $delta = $this->markers[$i+1] - $this->markers[$i];
                $m = sprintf("метка # %d -> метка # %d  %f сек", $i, $i+1, $delta);
                $this->log->info($m);
            }
        }

        $this->sumUp();
    }

    public function sumUp(): void {
        // это крайняя метка, таким образом при создании экземпляра Marker, а
        // впоследствии вызове Marker::sumUp должны быть созданы минимум 2 метки
        $this->addMark();
        $delta = $this->markers[count($this->markers)-1] - $this->markers[0];
        
        $bytes = memory_get_peak_usage(true);
        $memory = "не определен";

        for ($i=0; $i < count($this->volumes); $i++) { 
            if ($bytes > $this->volumes[$i] && $bytes <= $this->volumes[$i+1]) {
                $memory = sprintf("%4.2f %s", $bytes/$this->volumes[$i], $this->units[$i]);
                break;
            }
        }

        $m = sprintf("Скрипт выполнялся: %f сек. Объем памяти: %s.\n", $delta, $memory);
        $this->log->info($m);
    }
}