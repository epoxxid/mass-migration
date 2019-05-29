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
        $typeStr = "[$type]";
        switch ($type) {
            case 'ERR':
                $typeStr = "\e[0;31m{$typeStr}\e[0m";
                break;
            case 'INF':
                $typeStr = "\e[0;35m{$typeStr}\e[0m";
                break;
            case 'DBG':
                $typeStr = "\e[0;33m{$typeStr}\e[0m";
                break;
        }

        echo "$typeStr \e[1;34m$module\e[0m $msg\n";
        return true;
    }
}