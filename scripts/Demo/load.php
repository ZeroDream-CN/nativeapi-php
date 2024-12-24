<?php
class Demo {
    private $ZeroDream;
    private $logger;
    private $database;

    public function __construct()
    {
        global $logger, $database;
        $this->logger = $logger;
        $this->database = $database;
    }

    public function onLoad()
    {
        $this->ZeroDream = (new exports('zerodream_core'))->GetSharedObject();

        RegisterCommand('testdb', function($source, $args) {
            $data = $this->ZeroDream->Player->GetData($source);
            $conn = $this->database->getConnection();
            $stmt = $conn->prepare('SELECT * FROM users WHERE identifier = ?');
            $stmt->execute([$data['identifier']]);
            $result = $stmt->fetch();
            $this->ZeroDream->Notify->Send($source, "Hello, your name is {$result['name']}");
            $this->logger->info("Testing\nmultiple lines\n{$result['name']}");
        });

        RegisterCommand('perf', function($source, $args) {
            $begin = microtime(true);
            for ($i = 0; $i < 10000; $i++) {
                GetHashKey('test' . $i);
            }
            $end = microtime(true);
            $this->logger->info(sprintf('Performance test: %fs', $end - $begin));
        });

        RegisterCommand('getmymoney', function($source, $args) {
            $money = $this->ZeroDream->Player->GetMoney($source);
            $this->ZeroDream->Notify->Send($source, "Hello, you have $money");
            TriggerClientEvent('chat:addMessage', $source, [
                'args' => [
                    sprintf('You have ^2$%d^0.', $money)
                ]
            ]);
        });

        RegisterServerEvent('zerodream_chats:sendChat', function($source, $message) {
            $chatLog = sprintf('%s: %s', GetPlayerName($source), $message);
            $this->logger->info($chatLog);

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
        /* CreateThread(function() {
            $this->logger->info('Thread started');
            while (true) {
                Co::sleep(2);
                TriggerClientEvent('chat:addMessage', -1, [
                    'args' => [
                        sprintf('Time: %s', date('Y-m-d H:i:s'))
                    ]
                ]);
            }
        }); */
    }
}
