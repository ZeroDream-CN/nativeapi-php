<?php
$ZeroDream = (new exports('zerodream_core'))->GetSharedObject();

RegisterCommand('testdb', function($source, $args) use ($logger, $ZeroDream) {
    global $logger, $database;
    $data = $ZeroDream->Player->GetData($source);
    $conn = $database->getConnection();
    $stmt = $conn->prepare('SELECT * FROM users WHERE identifier = ?');
    $stmt->execute([$data['identifier']]);
    $result = $stmt->fetch();
    $ZeroDream->Notify->Send($source, "Hello, your name is {$result['name']}");
    $logger->info("Testing\nmultiple lines\n{$result['name']}");
});

RegisterCommand('getmymoney', function($source, $args) use ($logger, $ZeroDream) {
    global $logger;
    $money = $ZeroDream->Player->GetMoney($source);
    $ZeroDream->Notify->Send($source, "Hello, you have $money");
    TriggerClientEvent('chat:addMessage', $source, [
        'args' => [
            sprintf('You have ^2$%d^0.', $money)
        ]
    ]);
});

RegisterServerEvent('zerodream_chats:sendChat', function($source, $message) {
    global $logger;
    $chatLog = sprintf('%s: %s', GetPlayerName($source), $message);
    $logger->info($chatLog);

    if ($message == 'am i driving') {
        $isDriving = GetVehiclePedIsIn(GetPlayerPed($source), false);
        if ($isDriving) {
            TriggerClientEvent('chat:addMessage', $source, [
                'args' => [
                    'Yes, you are driving car'
                ]
            ]);
        } else {
            TriggerClientEvent('chat:addMessage', $source, [
                'args' => [
                    'No, you are not driving car'
                ]
            ]);
        }
    }
});

// Uncomment this to test thread
/* CreateThread(function() use ($logger) {
    $logger->info('Loaded script test.php from thread');
    while (true) {
        Co::sleep(2);
        TriggerClientEvent('chat:addMessage', -1, [
            'args' => [
                sprintf('Time: %s', date('Y-m-d H:i:s'))
            ]
        ]);
    }
}); */