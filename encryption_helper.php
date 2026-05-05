<?php

function vigenere_encrypt($text, $key) {
    $result = "";
    $key = strtoupper($key);
    $keyLen = strlen($key);
    $textLen = strlen($text);
    $kIdx = 0;

    for ($i = 0; $i < $textLen; $i++) {
        $char = $text[$i];
        $kChar = $key[$kIdx % $keyLen];
        $shift = ord($kChar) - ord('A');

        if (ctype_upper($char)) {
            $result .= chr(((ord($char) - ord('A') + $shift) % 26) + ord('A'));
            $kIdx++;
        } else if (ctype_lower($char)) {
            $result .= chr(((ord($char) - ord('a') + $shift) % 26) + ord('a'));
            $kIdx++;
        } else if (ctype_digit($char)) {
            $result .= chr(((ord($char) - ord('0') + $shift) % 10) + ord('0'));
            $kIdx++;
        } else {
            $result .= $char;
        }
    }
    return $result;
}

function vigenere_decrypt($text, $key) {
    $result = "";
    $key = strtoupper($key);
    $keyLen = strlen($key);
    $textLen = strlen($text);
    $kIdx = 0;

    for ($i = 0; $i < $textLen; $i++) {
        $char = $text[$i];
        $kChar = $key[$kIdx % $keyLen];
        $shift = ord($kChar) - ord('A');

        if (ctype_upper($char)) {
            $result .= chr(((ord($char) - ord('A') - $shift + 26) % 26) + ord('A'));
            $kIdx++;
        } else if (ctype_lower($char)) {
            $result .= chr(((ord($char) - ord('a') - $shift + 26) % 26) + ord('a'));
            $kIdx++;
        } else if (ctype_digit($char)) {
            $result .= chr(((ord($char) - ord('0') - $shift + 100) % 10) + ord('0')); // +100 to ensure positive
            $kIdx++;
        } else {
            $result .= $char;
        }
    }
    return $result;
}
?>
