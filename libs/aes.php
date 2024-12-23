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
        $cipher    = "aes-256-cfb";
        $text      = hex2bin($text);
        $decrypted = openssl_decrypt($text, $cipher, $this->key, OPENSSL_RAW_DATA, $this->iv);
        return $decrypted;
    }
}
