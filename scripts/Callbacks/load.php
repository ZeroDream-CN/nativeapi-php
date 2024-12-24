<?php
class Callbacks {
    private $registeredCallbacks = [];

    public function Register($name, $cb) {
        global $logger;
        if (!is_string($name) || empty($name)) {
            $logger->error("Name for callback is not valid");
            return;
        }
        if (!is_callable($cb)) {
            $logger->error("Callback for $name is not callable");
            return;
        }
        if (isset($this->registeredCallbacks[$name])) {
            $logger->error("Callback $name is already registered");
            return;
        }
        $logger->debug("Registered callback $name");
        $this->registeredCallbacks[$name] = $cb;
    }

    public function IsRegister($name) {
        return isset($this->registeredCallbacks[$name]);
    }

    public function Unregister($name) {
        global $logger;
        if (!isset($this->registeredCallbacks[$name])) {
            $logger->debug("Callback $name is not registered");
            return;
        }
        $logger->debug("Unregistered callback $name");
        unset($this->registeredCallbacks[$name]);
    }

    public function onLoad()
    {
        RegisterServerEvent("zerodream_core:triggerServerCallback", function($source, $name, $requestId, ...$args) {
            global $logger;
            if (!isset($this->registeredCallbacks[$name])) {
                $logger->debug("Callback $name is not registered");
                return;
            }
            $logger->debug("Triggered callback $name");
            $cb = $this->registeredCallbacks[$name];
            call_user_func_array($cb, array_merge([$source, function(...$data) use ($source, $requestId, $name, $logger) {
                TriggerClientEvent("zerodream_core:triggerServerCallback", $source, $requestId, ...$data);
            }], $args));
        });
    }
}
