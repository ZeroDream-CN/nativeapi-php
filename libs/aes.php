<?php
class ZeroAES {
    private $key;
    private $iv;

    public function __construct($key, $iv) {
        $this->key = $key;
        $this->iv  = $iv;
    }

    public function encrypt($text) {
        $cipher    = "aes-256-cfb";
        $encrypted = openssl_encrypt($text, $cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
        return bin2hex($encrypted);
    }

    public function decrypt($text) {
        if (!$text || empty($text)) {
            return '';
        }
        // is even?
        if (strlen($text) % 2 != 0) {
            echo "[ERROR] Invalid hex string: {$text}\n";
            return '';
        }
        $cipher    = "aes-256-cfb";
        $text      = hex2bin($text);
        $decrypted = openssl_decrypt($text, $cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
        return $decrypted;
    }
}
