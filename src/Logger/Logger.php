<?php
/**
 * Этот файл часть reduction 
 * Logger - простейший логгер 
 *
 * @copyright Copyright (c) 2020, Борис Федоров <w-navt@yandex.ru>
 * @license   MIT
 */
namespace Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\InvalidArgumentException;
use DateTime;

class Logger implements LoggerInterface
{
    private $logPath;
    
    public function __construct(string $logPath="data/app.log") {
        
        $path = getcwd().DIRECTORY_SEPARATOR.$logPath;
        $this->logPath = $path;

        if (file_exists($path) && is_writable($path)) return;

        $dir = dirname($path);

        if (is_dir($dir)) return;

        if (mkdir($dir, 0777, true) === false) {
            throw new InvalidArgumentException("Создайте вручную директорию для логов: {$dir}");
        }
    }
    
    public function emergency($message, array $context = []) {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = []) {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical($message, array $context = []) {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error($message, array $context = []) {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning($message, array $context = []) {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice($message, array $context = []) {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info($message, array $context = []) {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug($message, array $context = []) {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = []) {
        
        if (is_string($message) === false) {
            throw new InvalidArgumentException("Аргумент message должен быть строкой");
        }

        $date = new DateTime();
        $out = sprintf("[%s] [%s] %s %s\n",
            $date->format('Y-m-d H:i:s.v'), 
            ucfirst($level),
            $message,
            $this->displayContext($context));
        file_put_contents($this->logPath, $out, FILE_APPEND);
    }

    private function displayContext(array $context=[]) {
        return ($context == []) ? "" : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

}
