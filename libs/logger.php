<?php
class Logger
{
    private $level;

    public function __construct($level)
    {
        $this->level = $level;
    }

    public function debug()
    {
        $message = func_get_args();
        $this->log(0, $message);
    }

    public function info()
    {
        $message = func_get_args();
        $this->log(1, $message);
    }

    public function warning()
    {
        $message = func_get_args();
        $this->log(2, $message);
    }

    public function error()
    {
        $message = func_get_args();
        $this->log(3, $message);
    }

    public function print()
    {
        $message = func_get_args();
        $this->log(false, $message);
    }

    private function log($level, $message)
    {
        if ($level === false) {
            $level = 9;
        }
        if ($level < $this->level) {
            return;
        }
        if (is_array($message)) {
            $message = implode(' ', $message);
        }
        $color = $this->getColor($level);
        $label = $this->getLabel($level);
        $cid   = $this->getThreadName(Co::getCid());
        if (strpos($message, "\n") !== false) {
            $message = explode("\n", $message);
            foreach ($message as $line) {
                echo color(sprintf("^7%s ^2[%s]%s%s^0 %s^0\n", date('H:i:s'), $cid, $color, $label, $line));
            }
            return;
        }
        echo color(sprintf("^7%s ^2[%s]%s%s^0 %s^0\n", date('H:i:s'), $cid, $color, $label, $message));
    }

    private function getThreadName($cid)
    {
        global $client;
        if ($cid == -1) {
            return 'MAIN';
        }
        if (property_exists($client, 'eventThreadId') && $cid == $client->eventThreadId) {
            return 'EVENT';
        }
        if (property_exists($client, 'httpThreadId') && $cid == $client->httpThreadId) {
            return 'HTTP';
        }
        $threadNames = [
            1 => 'LOADER',
        ];
        return $threadNames[$cid] ?? sprintf('CO-%d', $cid);
    }

    private function getLabel($level)
    {
        switch ($level) {
            case 0:
                return ' DEBUG';
            case 1:
                return ' INFO';
            case 2:
                return ' WARNING';
            case 3:
                return ' ERROR';
            default:
                return '';
        }
    }

    private function getColor($level)
    {
        switch ($level) {
            case 0:
                return "^9";
            case 1:
                return "^5";
            case 2:
                return "^3";
            case 3:
                return "^1";
            default:
                return "^0";
        }
    }
}
