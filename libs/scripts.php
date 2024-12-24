<?php
$loadedScripts = [];
$importMaps = [];

function ScanScripts($path) {
    global $loadedScripts, $importMaps;

    $files = scandir($path);
    foreach ($files as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $fullpath = sprintf('%s/%s', $path, $file);
        if (is_dir($fullpath)) {
            LoadScript($fullpath);
        }
    }

    // Initialize the scripts
    foreach ($loadedScripts as $script) {
        $className = ucfirst($script);
        if (class_exists($className) && !isset($$className)) {
            $$className = new $className();
        }
    }

    // Call onLoad for each script
    foreach ($loadedScripts as $script) {
        $className = ucfirst($script);
        if (isset($$className)) {
            $importMap = $importMaps[$script] ?? [];
            $importObj = [];
            foreach ($importMap as $import) {
                $importObj[] = $$import;
            }
            $$className->onLoad(...$importObj);
        }
    }
}

function LoadScript($path) {
    global $logger, $loadedScripts, $importMaps;
    if (file_exists(sprintf('%s/load.php', $path))) {
        $logger->info(sprintf('Loading script %s', basename($path)));
        include(sprintf('%s/load.php', $path));
        $loadedScripts[] = basename($path);
        $importMaps[basename($path)] = GetImportMapFromFile(sprintf('%s/load.php', $path));
    } else {
        $logger->warning(sprintf('Script %s does not have a load.php', $path));
    }
}

function GetImportMapFromFile($path) {
    $importMap = [];
    $file      = file_get_contents($path);
    $lines     = explode("\n", $file);
    foreach ($lines as $line) {
        if (preg_match('/^\/\/ Import\: (.*)$/', $line, $matches)) {
            $importMap[] = trim($matches[1]);
        }
    }
    return $importMap;
}
