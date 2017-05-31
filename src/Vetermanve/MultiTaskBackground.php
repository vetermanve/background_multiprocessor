<?php


namespace Vetermanve;


class MultiTaskBackground
{
    private $providerCallback;
    
    private $getCallback;
    
    private $resultCallback;
    
    private $canStopCallback;
    
    private $activeLimit;
    
    private $tasks = [];
    
    private $pids = [];
    
    public function run () 
    {
        $this->watch();
    }
    
    private function subloadTasks($taskToActivate)
    {
        $newTasks = call_user_func($this->providerCallback, $taskToActivate);
        if (is_array($newTasks)) {
            foreach ($newTasks as $id => $command) {
                call_user_func($this->getCallback, $id);
                $this->start($id, $command);
            }
        }
    }
    
    public function watch () 
    {
        $this->initSubload();
        
        do {
            $activePids = [];
            foreach ($this->pids as $pid => $feed) {
                $r = (int)shell_exec('ps -p '.$pid.'| grep '.$pid.' | wc -l' );
                if ($r) {
                    $activePids[$pid] = $r;
                }
            }
            
            $closedPids = array_diff_key($this->pids, $activePids);
            if ($closedPids) {
                $this->finish($closedPids); 
            }
            
        } while ($activePids || $this->continueProcessing());
    }
    
    public function continueProcessing () 
    {
        if (!$this->canStopCallback) {
            return false;
        }
        
        $waitSeconds = (int)call_user_func($this->canStopCallback);
        if (!$waitSeconds) {
             return false;
        }
        
        sleep($waitSeconds);
        $this->initSubload();
        return true;
    }
    
    public function start ($id, $command) 
    {
        $this->tasks[$id] = $command;
        
        $pid = trim(shell_exec('(echo "MultiTaskBackground" && '.$command. ') > /dev/null 2>&1 & echo $!'));
        $this->pids[$pid] = $id;
    }
    
    private function finish($closedPids)
    {
        foreach ($closedPids as $pid => $_) {
            $taskId = $this->pids[$pid];
            unset($this->tasks[$taskId]);
            unset($this->pids[$pid]);
            call_user_func($this->resultCallback, $taskId);
        }
    
        $this->initSubload();
    }
    
    public function initSubload () 
    {
        $taskToActivate = $this->activeLimit - count($this->tasks);
        if ($taskToActivate > 0) {
            $this->subloadTasks($taskToActivate);
        }
    }
    
    /**
     * @return mixed
     */
    public function getProviderCallback()
    {
        return $this->providerCallback;
    }
    
    /**
     * @param mixed $providerCallback
     */
    public function setProviderCallback($providerCallback)
    {
        $this->providerCallback = $providerCallback;
    }
    
    /**
     * @return mixed
     */
    public function getGetCallback()
    {
        return $this->getCallback;
    }
    
    /**
     * @param mixed $getCallback
     */
    public function setGetCallback($getCallback)
    {
        $this->getCallback = $getCallback;
    }
    
    /**
     * @return mixed
     */
    public function getResultCallback()
    {
        return $this->resultCallback;
    }
    
    /**
     * @param mixed $resultCallback
     */
    public function setResultCallback($resultCallback)
    {
        $this->resultCallback = $resultCallback;
    }
    
    /**
     * @return mixed
     */
    public function getActiveLimit()
    {
        return $this->activeLimit;
    }
    
    /**
     * @param mixed $activeLimit
     */
    public function setActiveLimit($activeLimit)
    {
        $this->activeLimit = $activeLimit;
    }
    
    /**
     * @return mixed
     */
    public function getCanStopCallback()
    {
        return $this->canStopCallback;
    }
    
    /**
     * @param mixed $canStopCallback
     */
    public function setCanStopCallback($canStopCallback)
    {
        $this->canStopCallback = $canStopCallback;
    }
}