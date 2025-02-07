<?php
$registeredHttpHandler = [];

function SetHttpHandler($path, $callback)
{
    global $logger, $registeredHttpHandler, $httpServer;
    if (!is_string($path) || empty($path)) {
        $logger->error("Path for http handler is not valid");
        return;
    }
    if (!is_callable($callback)) {
        $logger->error("Callback for $path is not callable");
        return;
    }
    if (isset($registeredHttpHandler[$path])) {
        $logger->error("Http handler $path is already registered");
        return;
    }
    CreateThread(function() use ($path, $callback) {
        global $logger, $httpServer, $registeredHttpHandler;
        while (!isset($httpServer)) {
            $logger->debug('Waiting for http server to be initialized');
            Co::sleep(1);
        }
        $logger->debug("Registered http handler $path");
        $registeredHttpHandler[$path] = $callback;
        $httpServer->handle($path, function ($request, $response) use ($path) {
            global $registeredHttpHandler;
            $callback = $registeredHttpHandler[$path];
            $callback($request, $response);
        });
    });
}

function InitHttpServer()
{
    global $httpServer, $logger, $client;
    $client->httpThreadId = Co::getCid();
    $httpServer = new Swoole\Coroutine\Http\Server(HTTP_SERVER_HOST, HTTP_SERVER_PORT, false);
    $httpServer->handle('/', function ($request, $response) {
        $response->end('ZERODREAM API/1.0');
    });
    $logger->info(sprintf('Http server listening on %s:%d', HTTP_SERVER_HOST, HTTP_SERVER_PORT));
    $httpServer->start();
}
