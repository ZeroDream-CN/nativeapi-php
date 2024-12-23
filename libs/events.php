<?php
$registeredEvent = [];
$registeredServerEvent = [];
$registeredCommand = [];
$registeredThread = [];

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
    foreach ($result as $key => $value) {
        if (is_array($value)) {
            $object->{$key} = ProcessResult($value);
        } else {
            if (is_string($value) && substr($value, 0, 13) === '__FUNCTION__:') {
                $function     = substr($value, 13);
                $object->$key = function () use ($function) {
                    $args   = func_get_args();
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
        go($thread);
    }
}

function HandleEvents()
{
    global $registeredEvent, $registeredServerEvent, $registeredCommand, $client, $logger, $channel, $channelResp;
    $aes = new ZeroAES(AES_KEY, AES_IV);
    $logger->info('Event handler started');
    $client->eventThreadId = Co::getCid();
    while (true) {
        try {
            $data = $client->recv(1);
            $data = $data ? $data->data : null;
            if ($data) {
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
                        case 'stop':
                            $logger->info('Server stopped');
                            exit;
                        default:
                            $logger->warning('Unknown action:', $action);
                            break;
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
