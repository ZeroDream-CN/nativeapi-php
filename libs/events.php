<?php
$registeredEvent = [];
$registeredServerEvent = [];
$registeredCommand = [];
$registeredThread = [];
$registeredFunction = [];

function RegisterServerEvent($eventName, $callback)
{
    global $registeredServerEvent, $logger;
    $result = CallBridge('registerServerEvent', [
        'name' => $eventName
    ]);
    if ($result == 'ok') {
        $logger->debug('Registered server event: ' . $eventName);
        $registeredServerEvent[$eventName]   = $registeredServerEvent[$eventName] ?? [];
        $registeredServerEvent[$eventName][] = $callback;
    } else {
        $logger->error('Failed to register server event:', $eventName);
    }
    return $result == 'ok';
}

function RegisterEvent($eventName, $callback)
{
    global $registeredEvent, $logger;
    $result = CallBridge('registerEvent', [
        'name' => $eventName
    ]);
    if ($result == 'ok') {
        $logger->debug('Registered event: ' . $eventName);
        $registeredEvent[$eventName]   = $registeredEvent[$eventName] ?? [];
        $registeredEvent[$eventName][] = $callback;
    } else {
        $logger->error('Failed to register event:', $eventName);
    }
    return $result == 'ok';
}

function TriggerEvent()
{
    $args = func_get_args();
    if (count($args) < 1) {
        return false;
    }
    $eventName = array_shift($args);
    $result    = CallBridge('triggerEvent', [
        'name' => $eventName,
        'args' => $args
    ]);
    return $result == 'ok';
}

function TriggerClientEvent()
{
    global $client, $logger;
    $args = func_get_args();
    if (count($args) < 2) {
        return false;
    }
    $eventName = array_shift($args);
    $result    = CallBridge('triggerClientEvent', [
        'name' => $eventName,
        'args' => $args
    ]);
    return $result == 'ok';
}

function RegisterCommand($command, $callback, $restricted = false)
{
    global $registeredCommand, $logger;
    $result = CallBridge('registerCommand', [
        'name'       => $command,
        'restricted' => $restricted
    ]);
    if ($result == 'ok') {
        $logger->debug('Registered command: ' . $command);
        $registeredCommand[$command] = $callback;
    } else {
        $logger->error('Failed to register command:', $command);
    }
    return $result == 'ok';
}

function EvalCode($code)
{
    $result = CallBridge('eval', [
        'code' => sprintf("(function() { %s }());", $code)
    ]);
    return ProcessResult($result);
}

function ProcessResult($result)
{
    $object = new dynClass();
    if (!is_array($result)) {
        return $result;
    }
    foreach ($result as $key => $value) {
        if (is_array($value)) {
            $object->{$key} = ProcessResult($value);
        } else {
            if (is_string($value) && substr($value, 0, 13) === '__FUNCTION__:') {
                $function     = substr($value, 13);
                $object->$key = function () use ($function) {
                    $args   = func_get_args();
                    $args   = ProcessArgs($args);
                    $result = CallBridge('callFunction', [
                        'id'   => $function,
                        'args' => $args
                    ]);
                    if ($result['type'] == 'vector3') {
                        return new Vector3($result['result'][0], $result['result'][1], $result['result'][2]);
                    } else {
                        return isset($result['result']) ? $result['result'] : $result;
                    }
                };
            } else {
                $object->{$key} = $value;
            }
        }
    }
    return $object;
}

function ProcessRemoteFunction($object)
{
    $result = [];
    foreach ($object as $key => $value) {
        if (is_string($value) && substr($value, 0, 13) === '__FUNCTION__:') {
            $function = substr($value, 13);
            $result[$key] = function () use ($function) {
                $args   = func_get_args();
                $args   = ProcessArgs($args);
                $result = CallBridge('callFunction', [
                    'id'   => $function,
                    'args' => $args
                ]);
                if ($result['type'] == 'vector3') {
                    return new Vector3($result['result'][0], $result['result'][1], $result['result'][2]);
                } else {
                    return isset($result['result']) ? $result['result'] : $result;
                }
            };
        } elseif (is_array($value)) {
            $result[$key] = ProcessRemoteFunction($value);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

function ProcessArgs($args)
{
    $result = [];
    foreach ($args as $arg) {
        if (is_callable($arg)) {
            $id = CreateFunction($arg);
            $result[] = '__FUNCTION__:' . $id;
        } else {
            $result[] = $arg;
        }
    }
    return $result;
}

function ProcessVector3($vector3)
{
    $result = [];
    foreach ($vector3 as $key => $value) {
        if ($value instanceof Vector3) {
            $arr = $value->toArray();
            $result[$key] = "__VECTOR3__:" . json_encode($arr);
        } elseif (is_array($value)) {
            $result[$key] = ProcessVector3($value);
        } else {
            $result[$key] = $value;
        }
    }
    return $result;
}

function CreateFunction($callback)
{
    global $registeredFunction, $logger;
    $registeredFunction[] = $callback;
    return count($registeredFunction) - 1;
}

function CreateThread($callback)
{
    global $registeredThread, $logger;
    $registeredThread[] = $callback;
    return count($registeredThread) - 1;
}

function HandleThreads()
{
    global $registeredThread, $client, $logger;
    foreach ($registeredThread as $thread) {
        go(function() use ($thread) {
            \Swoole\Runtime::enableCoroutine();
            $thread();
        });
    }
}

function HandleEvents()
{
    global $registeredEvent, $registeredServerEvent, $registeredCommand, $registeredFunction, $client, $logger, $channel, $channelResp;
    $aes = new ZeroAES(AES_KEY, AES_IV);
    $client->eventThreadId = Co::getCid();
    $logger->info('Event handler started');
    $cachedData = false;
    while (true) {
        try {
            $raw = $client->recv(2);
            $raw = $raw ? $raw->data : null;
            if ($cachedData) {
                $raw = $cachedData . $raw;
                $cachedData = false;
            }
            if ($raw) {
                $raw       = preg_replace('/[^a-zA-Z0-9\;\!]/', '', $raw);
                // 每个数据块以 \n 分割，判断各数据块是否以 ! 开头，; 结尾
                // 如果是则认为是加密数据，推入队列
                // 否则存入 cachedData 中，下次循环时处理
                $dataList  = [];
                $dataExp   = explode("\n", $raw);
                $dataCount = count($dataExp);
                for ($i = 0; $i < $dataCount; $i++) {
                    $data = $dataExp[$i];
                    if (substr($data, 0, 1) == '!' && substr($data, -1) == ';') {
                        $dataList[] = substr($data, 1, -1);
                    } else {
                        $cachedData = $data;
                    }
                }
                // 解密数据并处理
                for ($i = 0; $i < count($dataList); $i++) {
                    $data      = $dataList[$i];
                    $logger->debug('Received data:', $data);
                    $decrypted = $aes->decrypt($data);
                    $json      = json_decode($decrypted, true, 512, JSON_PRESERVE_ZERO_FRACTION);
                    if ($json) {
                        $action = $json['action'];
                        $data   = $json['data'];
                        switch ($action) {
                            case 'serverEvent':
                                $eventName = $data['name'];
                                $args      = $data['args'];
                                if (isset($registeredServerEvent[$eventName])) {
                                    foreach ($registeredServerEvent[$eventName] as $callback) {
                                        call_user_func_array($callback, $args);
                                    }
                                }
                                break;
                            case 'event':
                                $eventName = $data['name'];
                                $args      = $data['args'];
                                if (isset($registeredEvent[$eventName])) {
                                    foreach ($registeredEvent[$eventName] as $callback) {
                                        call_user_func_array($callback, $args);
                                    }
                                }
                                break;
                            case 'command':
                                $command = $data['name'];
                                $source  = $data['source'];
                                $args    = $data['args'] ?? [];
                                $raw     = $data['raw'];
                                if (isset($registeredCommand[$command])) {
                                    $registeredCommand[$command]($source, $args, $raw);
                                }
                                break;
                            case 'function':
                                $id   = Intval($data['id']);
                                $args = $data['args'];
                                if (isset($registeredFunction[$id])) {
                                    $args = ProcessRemoteFunction($args);
                                    // $logger->info('Calling function:', $id, json_encode($args));
                                    call_user_func_array($registeredFunction[$id], $args);
                                }
                                break;
                            case 'stop':
                                $logger->info('Server stopped');
                                exit;
                            default:
                                $logger->warning('Unknown action:', $action);
                                break;
                        }
                    }
                }
            }
        } catch (\WebSocket\ConnectionException $e) {
            // TODO
        }

        // Handle calls
        $call = $channel->pop(0.01);
        if ($call) {
            $action = $call[0];
            $data   = $call[1];
            $result = CallBridge($action, $data);
            $channelResp->push($result);
        }
        Co::sleep(0.01);
    }
    $client->close();
}
