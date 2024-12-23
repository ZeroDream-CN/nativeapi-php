# NativeAPI PHP
PHP Interface for ZeroDream Native API

## Requirements
- PHP 7.4 or higher
- Swoole 4.5.0 or higher
- PDO Extension
- MySQL 5.7 or higher

## Installation
```bash
git clone https://github.com/ZeroDream-CN/nativeapi-php.git
```

## Usage
Edit the `config.php` file in the project root directory. Make sure the AES key and IV are the same as the server configuration file.

The key must be 32 characters long and the IV must be 16 characters long.

```php
<?php
// Native Server
define('NATIVE_API_HOST', '127.0.0.1');
define('NATIVE_API_PORT', 38080);
define('AES_KEY', '0123456789abcdef0123456789abcdef'); // <- Change this
define('AES_IV', 'abcdef9876543210');                  // <- Change this
define('LOG_LEVEL', 1);

// MySQL
define('DATABASE_HOST', '127.0.0.1');
define('DATABASE_PORT', 3306);
define('DATABASE_USER', 'root');
define('DATABASE_PASS', '123456789');
define('DATABASE_NAME', 'fivem');
```

Create a folder in your project `scripts/` folder. The first letter of the folder name must be uppercase.

Then create a file named `load.php` in the folder.

```text
project/
├── scripts/
│   ├── MyScript/
│   │   ├── load.php
```

Edit the `load.php` file in the `MyScript/` folder and implement your scripts.

```php
<?php
class MyScript
{
    private $logger;

    public function __construct()
    {
        // implement your constructor here
        global $logger;
        $this->logger = $logger;
    }

    public function onLoad()
    {
        // implement your scripts here
        RegisterCommand('hello', function($source, $args) {
            $this->logger->info(sprintf('Player %s says hello', GetPlayerName($source)));
            TriggerClientEvent('chat:addMessage', $source, [
                'args' => [
                    sprintf('Hello, %s', GetPlayerName($source))
                ]
            ]);
        });
    }
}
```

The `onLoad` method will be called when the script is loaded.

### Import other scripts
You can import other scripts instance by adding `// Import:` tag in the `load.php` file.

```php
<?php
// Import: MyAnotherScript
// Import: MyAnotherScript2
class MyResource {
    public function onLoad($myScript, $myScript2) {
        // $myScript is an instance of MyAnotherScript
        // $myScript2 is an instance of MyAnotherScript2
    }
}
```

Then, run the `main.php` file in the project root directory.

```bash
php main.php
```

## API

### Natives

The FiveM server side natives are registered as functions in the global namespace. You can call these functions directly in your scripts.

```php
$player = GetPlayerPed($source);
$pos    = GetEntityCoords($player);
$name   = GetPlayerName($source);
```

### Scripts and Events

<details>
<summary>Click to expand</summary>

### RegisterServerEvent
```php
RegisterServerEvent ( string $eventName, callable $callback )
```
Registers a server event with the specified name and callback function.

**Parameters:**
- `string $eventName`: The name of the server event to register.
- `callable $callback`: The callback function to execute when the event is triggered.

**Returns:**
- `bool`: `true` if the event was registered successfully, `false` otherwise.

### RegisterEvent
```php
RegisterEvent ( string $eventName, callable $callback )
```
Registers an event with the specified name and callback function.

**Parameters:**
- `string $eventName`: The name of the event to register.
- `callable $callback`: The callback function to execute when the event is triggered.

**Returns:**
- `bool`: `true` if the event was registered successfully, `false` otherwise.

### TriggerEvent
```php
TriggerEvent ( string $eventName, mixed ...$args )
```
Triggers an event with the specified name and arguments.

**Parameters:**
- `string $eventName`: The name of the event to trigger.
- `mixed ...$args`: The arguments to pass to the event callback.

**Returns:**
- `bool`: `true` if the event was triggered successfully, `false` otherwise.

### TriggerClientEvent
```php
TriggerClientEvent ( string $eventName, mixed ...$args )
```
Triggers a client event with the specified name and arguments.

**Parameters:**
- `string $eventName`: The name of the client event to trigger.
- `mixed ...$args`: The arguments to pass to the event callback.

**Returns:**
- `bool`: `true` if the event was triggered successfully, `false` otherwise.

### RegisterCommand
```php
RegisterCommand ( string $command, callable $callback, bool $restricted = false )
```
Registers a command with the specified name, callback function, and restriction status.

**Parameters:**
- `string $command`: The name of the command to register.
- `callable $callback`: The callback function to execute when the command is triggered.
- `bool $restricted`: Whether the command is restricted (default is `false`).

**Returns:**
- `bool`: `true` if the command was registered successfully, `false` otherwise.

### EvalCode
```php
EvalCode ( string $code )
```
Evaluates the specified code and processes the result.

**Parameters:**
- `string $code`: The code to evaluate.

**Returns:**
- `mixed`: The processed result of the evaluated code.

### CreateThread
```php
CreateThread ( callable $callback )
```
Creates a new thread with the specified callback function.

Do not nest threads! This can lead to unpredictable errors.
```php
CreateThread(function() {
    // ... some code
    CreateThread(function() {
        // The nested part
    });
});
```

**Parameters:**
- `callable $callback`: The callback function to execute in the new thread.

**Returns:**
- `int`: The ID of the created thread.

</details>

## Exports
You can use exports to share data between LUA/JS scripts.

```php
$module = (new export('resource-name'));
$result = $module->methodName($arg1, $arg2, ...);
```

## Performance
10000 times loop test, the result is about 1.6s.

OS specs: Fedora 40 Linux, E5-2690 v4 CPU, 256GB RAM, 1Gbps LAN.
```php
RegisterCommand('perf', function($source, $args) use ($logger) {
    global $logger;
    $begin = microtime(true);
    for ($i = 0; $i < 10000; $i++) {
        GetHashKey('test' . $i);
    }
    $end = microtime(true);
    $logger->info(sprintf('Performance test: %fs', $end - $begin));
});
```

## Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
