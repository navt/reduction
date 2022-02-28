<?php
/**
 * Этот файл часть reduction 
 * Logger - простейший логгер 
 *
 * @copyright Copyright (c) 2020, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace navt\Reduction\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use DateTime;

class Logger extends AbstractLogger
{
    private bool $active;
    private string $logPath;
    
    public function __construct(string $logPath="data/app.log", bool $active=true) {
        
        $this->active = $active;
        $path = getcwd().DIRECTORY_SEPARATOR.$logPath;
        $this->logPath = $path;

        if (file_exists($path)) return;

        $dir = dirname($path);

        if (is_dir($dir)) return;

        if (mkdir($dir, 0777, true) === false) {
            throw new InvalidArgumentException("Создайте вручную директорию для логов: {$dir}");
        }
    }
    
    public function log($level, $message, array $context = []) {
        
        if ($this->active === false) return;

        if (!is_string($message)) {
            $s = var_export($message, true);
            throw new InvalidArgumentException("Аргумент message {$s} должен быть строкой");
        }

        $date = new DateTime();
        $outFormat = "[%s] [%s] %s".PHP_EOL."%s";
        $out = sprintf($outFormat,
            $date->format('Y-m-d H:i:s.v'), 
            ucfirst($level),
            $message,
            $this->displayContext($context));
        file_put_contents($this->logPath, $out, FILE_APPEND);
    }
    
    /**
     * @param mixed[] $context
     * @return string
     */
    private function displayContext(array $context=[]): string {
        return ($context == []) ? "" : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
    }

}
