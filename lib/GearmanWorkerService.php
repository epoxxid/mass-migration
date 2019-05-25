<?php


class GearmanWorkerService
{
    /** @var GearmanWorker */
    private $worker;

    public function __construct(array $functionsMap = array())
    {
        $this->worker = new GearmanWorker();
        $this->worker->addServer();

        foreach ($functionsMap as $event => $callback) {
            $this->worker->addFunction($event, $callback);
        }
    }

    public function start()
    {
        print "Gearman started! Waiting for a job...\n";

        while($this->worker->work())
        {
            if ($this->worker->returnCode() != GEARMAN_SUCCESS)
            {
                echo 'return_code: ' . $this->worker->returnCode() . "\n";
                break;
            }
        }
    }


}