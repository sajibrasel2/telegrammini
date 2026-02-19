<?php
/**
 * Security Helper Class
 * Provides static methods for input sanitization and security checks
 */
class Security {
    /**
     * Sanitize output to prevent XSS
     */
    public static function xss($data) {
        if (is_array($data)) {
            return array_map([self::class, 'xss'], $data);
        }
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate Telegram User ID
     */
    public static function validateUserId($userId) {
        return filter_var($userId, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Sanitize input for general use
     */
    public static function sanitizeInput($data) {
        return trim(strip_tags($data));
    }
    
    /**
     * Verify Telegram Web App Init Data (Optional but recommended for production)
     * Note: This requires the BOT_TOKEN from config
     */
    public static function verifyTelegramWebAppData($initData, &$debug = null) {
        if (!defined('BOT_TOKEN')) return false;

        $initData = (string)$initData;

        // Extract received hash in a tolerant way
        $receivedHash = null;
        foreach (explode('&', $initData) as $pair) {
            if ($pair === '') continue;
            $pos = strpos($pair, '=');
            if ($pos === false) continue;
            $kRaw = substr($pair, 0, $pos);
            $vRaw = substr($pair, $pos + 1);
            if ($kRaw === 'hash' || rawurldecode($kRaw) === 'hash' || urldecode($kRaw) === 'hash') {
                // hash is usually hex, but keep this tolerant
                $receivedHash = rawurldecode($vRaw);
                break;
            }
        }

        if (!$receivedHash) {
            if (is_array($debug)) {
                $debug['error'] = 'hash missing';
            }
            return false;
        }

        $secretKeys = [
            // Variant 0: key=BOT_TOKEN, data="WebAppData" (older examples)
            hash_hmac('sha256', 'WebAppData', BOT_TOKEN, true),
            // Variant 1: key="WebAppData", data=BOT_TOKEN (Telegram WebAppData official examples)
            hash_hmac('sha256', BOT_TOKEN, 'WebAppData', true),
            // Variant 2: fallback used in some implementations
            hash('sha256', BOT_TOKEN, true)
        ];

        $candidates = [];

        // Strategy A: PHP parse_str decoded
        $dataA = [];
        parse_str($initData, $dataA);
        unset($dataA['hash']);
        ksort($dataA, SORT_STRING);
        $arrA = [];
        foreach ($dataA as $k => $v) {
            $arrA[] = $k . '=' . $v;
        }
        $candidates['parse_str_decoded'] = implode("\n", $arrA);

        // Strategy A2: parse_str decoded, excluding signature
        if (array_key_exists('signature', $dataA)) {
            $dataA2 = $dataA;
            unset($dataA2['signature']);
            ksort($dataA2, SORT_STRING);
            $arrA2 = [];
            foreach ($dataA2 as $k => $v) {
                $arrA2[] = $k . '=' . $v;
            }
            $candidates['parse_str_no_signature'] = implode("\n", $arrA2);
        }

        // Strategy B: rawurldecode pairs (keep signature if present)
        $dataB = [];
        foreach (explode('&', $initData) as $pair) {
            if ($pair === '') continue;
            $pos = strpos($pair, '=');
            if ($pos === false) continue;
            $k = rawurldecode(substr($pair, 0, $pos));
            $v = rawurldecode(substr($pair, $pos + 1));
            if ($k === 'hash') continue;
            $dataB[$k] = $v;
        }
        ksort($dataB, SORT_STRING);
        $arrB = [];
        foreach ($dataB as $k => $v) {
            $arrB[] = $k . '=' . $v;
        }
        $candidates['rawurldecode_pairs'] = implode("\n", $arrB);

        // Strategy B2: rawurldecode pairs, excluding signature
        if (array_key_exists('signature', $dataB)) {
            $dataB2 = $dataB;
            unset($dataB2['signature']);
            ksort($dataB2, SORT_STRING);
            $arrB2 = [];
            foreach ($dataB2 as $k => $v) {
                $arrB2[] = $k . '=' . $v;
            }
            $candidates['rawurldecode_no_signature'] = implode("\n", $arrB2);
        }

        // Strategy C: raw pairs without decoding (sometimes clients differ)
        $dataC = [];
        foreach (explode('&', $initData) as $pair) {
            if ($pair === '') continue;
            $pos = strpos($pair, '=');
            if ($pos === false) continue;
            $k = substr($pair, 0, $pos);
            $v = substr($pair, $pos + 1);
            if ($k === 'hash') continue;
            $dataC[$k] = $v;
        }
        ksort($dataC, SORT_STRING);
        $arrC = [];
        foreach ($dataC as $k => $v) {
            $arrC[] = $k . '=' . $v;
        }
        $candidates['raw_pairs'] = implode("\n", $arrC);

        // Strategy C2: raw pairs, excluding signature
        if (array_key_exists('signature', $dataC)) {
            $dataC2 = $dataC;
            unset($dataC2['signature']);
            ksort($dataC2, SORT_STRING);
            $arrC2 = [];
            foreach ($dataC2 as $k => $v) {
                $arrC2[] = $k . '=' . $v;
            }
            $candidates['raw_no_signature'] = implode("\n", $arrC2);
        }

        // Evaluate candidates
        foreach ($candidates as $name => $dataCheckString) {
            foreach ($secretKeys as $keyIndex => $secretKey) {
                $checkHash = hash_hmac('sha256', $dataCheckString, $secretKey);
                if (hash_equals($checkHash, $receivedHash)) {
                    if (is_array($debug)) {
                        $debug['received_hash'] = $receivedHash;
                        $debug['calculated_hash'] = $checkHash;
                        $debug['data_check_string'] = $dataCheckString;
                        $debug['matched_strategy'] = $name;
                        $debug['matched_key'] = (string)$keyIndex;
                    }
                    return true;
                }
            }
        }

        // No match: return debug for the first (preferred) strategy
        if (is_array($debug)) {
            $preferred = $candidates['parse_str_decoded'];
            $prefHash = hash_hmac('sha256', $preferred, $secretKeys[0]);
            $debug['received_hash'] = $receivedHash;
            $debug['calculated_hash'] = $prefHash;
            $debug['data_check_string'] = $preferred;
            $debug['matched_strategy'] = 'none';
        }

        return false;
    }
}
?>
