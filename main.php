<?php

use function Swoole\Coroutine\run;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine;
use Swoole\Coroutine\Channel;

// Define ROOT path
define('ROOT', __DIR__);

// Load libraries
require_once('libs/aes.php');
require_once('libs/logger.php');
require_once('libs/vector3.php');
require_once('libs/exports.php');
require_once('libs/mysql.php');
require_once('libs/http.php');
require_once('libs/utils.php');
require_once('libs/bridge.php');
require_once('libs/natives.php');
require_once('libs/events.php');
require_once('libs/scripts.php');
require_once('config.php');

// Initialize Logger
$logger = new Logger(LOG_LEVEL);
$logger->print('^1  _   _       _   _                ^0_    ____ ___ ');
$logger->print('^1 | \ | | __ _| |_(_)_   _____     ^0/ \  |  _ \_ _|');
$logger->print('^1 |  \| |/ _` | __| \ \ / / _ \   ^0/ _ \ | |_) | | ');
$logger->print('^1 | |\  | (_| | |_| |\ V /  __/  ^0/ ___ \|  __/| | ');
$logger->print('^1 |_| \_|\__,_|\__|_| \_/ \___/ ^0/_/   \_\_|  |___|');
$logger->print('                                              ');

// Initialize Database and WebSocket Client
$database    = new ZeroDB(DATABASE_HOST, DATABASE_PORT, DATABASE_NAME, DATABASE_USER, DATABASE_PASS);
$client      = new Client(NATIVE_API_HOST, NATIVE_API_PORT);
$channel     = new Channel(128);
$channelResp = new Channel(128);
$httpServer  = null;

// Connect to MySQL
$database->connect();

// Swoole Hook Flags
Co::set(['hook_flags' => SWOOLE_HOOK_ALL]);

register_shutdown_function('shutdown');

// Main function
run(function () use ($client, $logger, $database, $channel, $channelResp, $httpServer) {
    
    if (!$database->getConnection()) {
        $logger->error('Failed to connect to MySQL, please check your configuration');
        return;
    }
    
    $logger->info('Connected to ^2MySQL');

    // Connect to Native Server WebSocket
    $ret = $client->upgrade('/');
    if (!$ret) {
        $logger->error('Failed to connect to Native Server, please check your configuration');
        return;
    }

    // Authenticate with Native Server
    $result = CallBridge('auth', ['auth' => true]);
    if (strlen($result) !== 36) {
        $logger->error('Failed to authenticate with Native Server');
        return;
    }

    $logger->info('Connected to ^3Native Server');
    
    // Load scripts
    ScanScripts(sprintf('%s/scripts', ROOT));

    // Load threads
    HandleThreads();

    // Main loop
    go(function () use ($client, $logger, $database, $channel, $channelResp) {
        HandleEvents();
    });

    // Initialize HTTP Server
    InitHttpServer();
});