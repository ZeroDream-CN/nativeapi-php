<?php
function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function color($text) {
    $colors = [
        '^1' => "\033[31;1m",
        '^2' => "\033[32m",
        '^3' => "\033[33m",
        '^4' => "\033[34m",
        '^5' => "\033[36m",
        '^6' => "\033[35m",
        '^7' => "\033[37m",
        '^8' => "\033[31m",
        '^9' => "\033[35;1m",
        '^0' => "\033[0m"
    ];

    $lastColor = null;
    foreach ($colors as $code => $color) {
        if (strpos($text, $code) !== false) {
            $text      = str_replace($code, $color, $text);
            $lastColor = $code;
        }
    }

    if ($lastColor !== '^0') {
        $text .= "\033[0m";
    }

    return $text;
}
