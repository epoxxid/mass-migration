<?php

require_once 'MMLogger.php';

class MMStdoutLogger extends MMLogger
{
    /**
     * Add actual log record
     *
     * @param string $type
     * @param string $msg
     * @param string $module
     * @return bool
     */
    protected function addLogRecord($type, $msg, $module)
    {
        echo "[$type] $module | $msg\n";
        return true;
    }
}