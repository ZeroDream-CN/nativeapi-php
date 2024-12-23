<?php

#[AllowDynamicProperties]
class exports
{
    private $name;
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function __call($name, $arguments)
    {
        $args = $this->processArgs($arguments);
        $code = "return exports.{$this->name}.{$name}({$args});";
        return EvalCode($code);
    }

    public function processArgs($arguments)
    {
        $args = [];
        foreach ($arguments as $arg) {
            if (is_string($arg)) {
                $arg    = str_replace("'", "\\'", $arg);
                $arg    = str_replace('\\', '\\\\', $arg);
                $args[] = "'$arg'";
            } else if (is_array($arg)) {
                $arr = '[';
                foreach ($arg as $key => $value) {
                    $arr .= $this->processArgs([$key, $value]) . ',';
                }
                $arr    = rtrim($arr, ',') . ']';
                $args[] = $arr;
            } else {
                $args[] = $arg;
            }
        }
        return implode(',', $args);
    }
}

#[AllowDynamicProperties]
class dynClass
{
    public function __call($method, $args)
    {
        if (isset($this->$method)) {
            $func = $this->$method;
            return call_user_func_array($func, $args);
        }
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }

    public function __get($name)
    {
        return $this->$name;
    }

    public function __isset($name)
    {
        return isset($this->$name);
    }

    public function __unset($name)
    {
        unset($this->$name);
    }

    public function __toString()
    {
        return json_encode($this);
    }

    public function __debugInfo()
    {
        return (array)$this;
    }

    public function __invoke()
    {
        return $this;
    }

    public function __construct($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    public function __destruct()
    {
        foreach ($this as $key => $value) {
            unset($this->$key);
        }
    }
}
