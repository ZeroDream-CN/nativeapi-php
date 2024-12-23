<?php
function ScanScripts($path) {
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
}

function LoadScript($path) {
    global $logger;
    if (file_exists(sprintf('%s/load.php', $path))) {
        $logger->info(sprintf('Loading script %s', basename($path)));
        include(sprintf('%s/load.php', $path));
    } else {
        $logger->warning(sprintf('Script %s does not have a load.php', $path));
    }
}
