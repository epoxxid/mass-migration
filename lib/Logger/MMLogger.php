<?php

/**
 * Common interface for any kind of Mass Migration logger
 */
abstract class MMLogger
{
    const LEVEL_OFF = 0;
    const LEVEL_ERRORS = 100;
    const LEVEL_INF0 = 200;
    const LEVEL_DEBUG = 300;

    const TYPE_ERROR = 'ERR';
    const TYPE_INFO = 'INF';
    const TYPE_DEBUG = 'DBG';

    /** @var int */
    private $level = self::LEVEL_ERRORS;

    public function setLevel($level)
    {
        switch ($level) {
            case 'off':
                return $this->level = self::LEVEL_OFF;
            case 'errors':
                return $this->level = self::LEVEL_ERRORS;
            case 'info':
                return $this->level = self::LEVEL_INF0;
            case 'debug':
                return $this->level = self::LEVEL_DEBUG;
        }
        return $this->level;
    }

    /**
     * Add error message to the logger
     *
     * @param $msg
     * @param string $module
     * @return bool
     */
    public function error($msg, $module = '')
    {
        if ($this->level >= self::LEVEL_ERRORS) {
            return $this->addLogRecord(self::TYPE_ERROR, $msg, $module);
        }
        return false;
    }

    /**
     * Add info message to the logger
     *
     * @param $msg
     * @param string $module
     * @return bool
     */
    public function info($msg, $module = '')
    {
        if ($this->level >= self::LEVEL_INF0) {
            return $this->addLogRecord(self::TYPE_INFO, $msg, $module);
        }
        return false;
    }

    /**
     * Add debug message to the logger
     *
     * @param $msg
     * @param string $module
     * @return bool
     */
    public function dbg($msg, $module = '')
    {
        if ($this->level >= self::LEVEL_DEBUG) {
            return $this->addLogRecord(self::TYPE_DEBUG, $msg, $module);
        }
        return false;
    }

    /**
     * Dump XML string to debug output
     *
     * @param string $title
     * @param string $xml
     * @param string $module
     * @return bool
     */
    public function dbgXml($title, $xml, $module = '')
    {
        $str = sprintf("\n========== %s =========\n%s\n", $title, $xml);
        return $this->dbg($str, $module);
    }

    /**
     * Add actual log record
     *
     * @param string $type
     * @param string $msg
     * @param string $module
     * @return bool
     */
    abstract protected function addLogRecord($type, $msg, $module);
}