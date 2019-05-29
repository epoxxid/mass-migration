<?php

require_once 'MMLogger.php';

class MMWriteToFileLogger extends MMLogger
{
    const DEFAULT_FILE_NAME = 'mass-migration.log';

    /** @var resource */
    private $fileHandle;

    /**
     * @param string $filePath
     */
    public function __construct($filePath)
    {
        $this->fileHandle = fopen($filePath, 'a') or die("Unable to init log file: path `$filePath` is not writable");
    }

    protected function addLogRecord($type, $msg, $module)
    {
        $logLine = sprintf("%s | %s | %s | %s\n", date('Y-m-d H:i:s'), $type, $module, $msg);
        fwrite($this->fileHandle, $logLine);
    }
}
