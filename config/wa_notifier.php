<?php

// Optional local override (jangan di-commit jika pakai git)
// Buat file: config/wa_config.local.php
// Contoh isi:
// <?php
// define('WA_MODE', 'callmebot');
// define('WA_CALLMEBOT_APIKEY', 'ISI_API_KEY_KAMU');
if (is_file(__DIR__ . '/wa_config.local.php')) {
    include_once __DIR__ . '/wa_config.local.php';
}

if (!defined('WA_MODE')) {
    define('WA_MODE', 'disabled');
}

if (!defined('WA_CALLMEBOT_APIKEY')) {
    define('WA_CALLMEBOT_APIKEY', '');
}

if (!defined('WA_FONNTE_TOKEN')) {
    define('WA_FONNTE_TOKEN', '');
}

if (!defined('WA_FONNTE_ENDPOINT')) {
    define('WA_FONNTE_ENDPOINT', 'https://api.fonnte.com/send');
}

if (!function_exists('wa_normalize_phone')) {
    function wa_normalize_phone($phone)
    {
        $digits = preg_replace('/\D+/', '', (string)$phone);
        if ($digits === '') {
            return '';
        }
        if (strpos($digits, '0') === 0) {
            return '62' . substr($digits, 1);
        }
        if (strpos($digits, '62') === 0) {
            return $digits;
        }
        return $digits;
    }
}

if (!function_exists('wa_send_notification')) {
    function wa_send_notification($phone, $message, &$error = '')
    {
        $error = '';
        $phone = wa_normalize_phone($phone);
        if ($phone === '') {
            $error = 'Nomor WA kosong/tidak valid.';
            return false;
        }

        if (WA_MODE === 'fonnte') {
            if (WA_FONNTE_TOKEN === '') {
                $error = 'Token Fonnte belum diatur.';
                return false;
            }

            if (!function_exists('curl_init')) {
                $error = 'cURL wajib aktif untuk mode Fonnte.';
                return false;
            }

            $target = (strpos($phone, '62') === 0) ? ('0' . substr($phone, 2)) : $phone;
            $ch = curl_init(WA_FONNTE_ENDPOINT);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'target' => $target,
                    'message' => $message,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: ' . WA_FONNTE_TOKEN,
                    'Content-Type: application/x-www-form-urlencoded',
                ],
            ]);
            $response = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if ($response === false || $httpCode >= 400) {
                $error = $curlErr !== '' ? $curlErr : 'HTTP ' . $httpCode;
                return false;
            }

            $parsed = json_decode((string)$response, true);
            if (is_array($parsed) && isset($parsed['status']) && $parsed['status'] === false) {
                $error = (string)($parsed['detail'] ?? 'Gagal kirim via Fonnte.');
                return false;
            }

            return true;
        }

        if (WA_MODE === 'callmebot') {
            if (WA_CALLMEBOT_APIKEY === '') {
                $error = 'API key WA belum diatur.';
                return false;
            }

            $url = 'https://api.callmebot.com/whatsapp.php?phone=' . rawurlencode('+' . $phone)
                . '&text=' . rawurlencode($message)
                . '&apikey=' . rawurlencode(WA_CALLMEBOT_APIKEY);

            if (function_exists('curl_init')) {
                $ch = curl_init($url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                ]);
                $response = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr = curl_error($ch);
                curl_close($ch);

                if ($response === false || $httpCode >= 400) {
                    $error = $curlErr !== '' ? $curlErr : 'HTTP ' . $httpCode;
                    return false;
                }
                return true;
            }

            $response = @file_get_contents($url);
            if ($response === false) {
                $error = 'Gagal menghubungi gateway WA.';
                return false;
            }
            return true;
        }

        $error = 'Mode WA belum aktif.';
        return false;
    }
}

if (!function_exists('wa_provider_name')) {
    function wa_provider_name()
    {
        if (WA_MODE === 'fonnte') {
            return 'fonnte';
        }
        if (WA_MODE === 'callmebot') {
            return 'callmebot';
        }
        return 'disabled';
    }
}
