<?php

namespace TidioLiveChat\Logs;

if (!defined('WPINC')) {
    die('File loaded directly. Exiting.');
}


use DateTime;
use TidioLiveChat\Clock\Clock;

use function array_reverse;
use function file_exists;
use function is_dir;
use function is_file;
use function touch;
use function sprintf;
use function error_log;
use function file_get_contents;
use function filesize;

final class Logger
{
    /** @var string */
    private $path;
    /** @var Clock */
    private $clock;
    /** @var int */
    private $maxFileSize;

    /**
     * @param string $path Debug logs file name
     * @param Clock $clock
     * @param int $maxFileSize Maximum log file size in bytes, default 2MB
     */
    public function __construct($path, $clock, $maxFileSize = 2097152)
    {
        $this->path = $path;
        $this->clock = $clock;
        $this->maxFileSize = $maxFileSize;

        // The directory can be read-only, then logging is skipped instead of erroring out.
        if (!file_exists($this->path) && is_writable(dirname($this->path))) {
            touch($this->path);
        }
    }

    /**
     * @param string $message
     * @return void
     */
    public function info($message)
    {
        $this->storeLogLine('info', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function debug($message)
    {
        $this->storeLogLine('debug', $message);
    }

    /**
     * @param string $message
     * @return void
     */
    public function error($message)
    {
        $this->storeLogLine('error', $message);
    }

    /**
     * @return string
     */
    public function readLog()
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            return 'Cannot read log file';
        }

        $logContent = file_get_contents($this->path);
        $lines = explode(PHP_EOL, $logContent ?: '');
        $reversedLines = array_reverse($lines);
        $reversedContent = trim(implode(PHP_EOL, $reversedLines));

        return $reversedContent ?: 'Log file is empty';
    }

    /**
     * @return void
     */
    public function clearLog()
    {
        if (!is_file($this->path) || !is_writable($this->path)) {
            return;
        }

        file_put_contents($this->path, '');
    }

    /**
     * @return string
     */
    public function logFileStatus()
    {
        if (!file_exists($this->path)) {
            return is_writable(dirname($this->path))
                ? 'Log file does not exist'
                : 'Log directory is not writable';
        }

        // is_writable() reports true for a directory, so the path type has to be checked separately.
        if (is_dir($this->path)) {
            return 'Log path is a directory, not a file';
        }

        if (!is_writable($this->path)) {
            return 'Log file is not writable';
        }

        if (!is_readable($this->path)) {
            return 'Log file is not readable';
        }

        return 'Log file exists and is readable and writable';
    }

    /**
     * @param string $severity
     * @param string $message
     * @return void
     */
    private function storeLogLine($severity, $message)
    {
        // is_file() guards against a directory sitting under the log path, which is_writable() accepts.
        if (!is_file($this->path) || !is_writable($this->path)) {
            return;
        }

        $this->truncateLogFileIfTooBig();

        $date = $this->clock->getCurrentTimestamp()->format(DateTime::ATOM);
        $formattedError = sprintf('[%s][%s] %s' . PHP_EOL, $date, $severity, $message);
        error_log($formattedError, 3, $this->path);
    }

    /**
     * @return void
     */
    private function truncateLogFileIfTooBig()
    {
        // error_log() appends without invalidating the stat cache, so filesize() would go stale.
        clearstatcache(true, $this->path);

        if (filesize($this->path) < $this->maxFileSize) {
            return;
        }

        $tail = file_get_contents($this->path, false, null, -intval($this->maxFileSize / 2));
        if ($tail === false) {
            return;
        }

        // Drop the leading line, it was cut in the middle by the offset read.
        $firstEol = strpos($tail, PHP_EOL);
        file_put_contents($this->path, $firstEol === false ? '' : substr($tail, $firstEol + strlen(PHP_EOL)));
    }
}
