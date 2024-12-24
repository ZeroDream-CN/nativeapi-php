<?php
function CallBridge($action, $data)
{
    global $client, $logger, $channel, $channelResp;
    $cid = Co::getCid();
    if ($cid !== 1 && $cid !== $client->eventThreadId) {
        $channel->push([$action, $data]);
        $data = $channelResp->pop(3.0);
        return $data;
    }
    $aes       = new ZeroAES(AES_KEY, AES_IV);
    $uuid      = uuid();
    $encrypted = $aes->encrypt(json_encode([
        'action' => $action,
        'eid'    => $uuid,
        'data'   => $data
    ], JSON_PRESERVE_ZERO_FRACTION));
    $client->push($encrypted);
    $begin = time();
    while (time() - $begin < 5) {
        $data = $client->recv(1);
        $data = $data ? $data->data : null;
        if ($data) {
            $decrypted = $aes->decrypt($data);
            $json      = json_decode($decrypted, true, 512, JSON_PRESERVE_ZERO_FRACTION);
            if ($json && isset($json['eid']) && $json['eid'] == $uuid) {
                $data = $json['data'];
                return $data;
            }
        }
    }
    $logger->error('Failed to call bridge due to timeout:', $action);
    return null;
}
