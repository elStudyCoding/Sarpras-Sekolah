<?php

if (!defined('SCHOOL_TIMEZONE')) {
    define('SCHOOL_TIMEZONE', 'Asia/Jakarta');
}

if (!defined('SCHOOL_OPEN_TIME')) {
    define('SCHOOL_OPEN_TIME', '06:45');
}

if (!defined('SCHOOL_CLOSE_TIME')) {
    define('SCHOOL_CLOSE_TIME', '15:20');
}

if (!defined('SCHOOL_REQUEST_CLOSE_TIME')) {
    define('SCHOOL_REQUEST_CLOSE_TIME', '15:00');
}

if (!function_exists('school_hours_is_open_now')) {
    function school_hours_is_open_now()
    {
        $tz = new DateTimeZone(SCHOOL_TIMEZONE);
        $now = new DateTime('now', $tz);
        $open = DateTime::createFromFormat('H:i', SCHOOL_OPEN_TIME, $tz);
        $close = DateTime::createFromFormat('H:i', SCHOOL_CLOSE_TIME, $tz);

        if (!$open || !$close) {
            return true;
        }

        $open->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        $close->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

        return ($now >= $open && $now <= $close);
    }
}

if (!function_exists('school_hours_label')) {
    function school_hours_label()
    {
        return SCHOOL_OPEN_TIME . ' - ' . SCHOOL_CLOSE_TIME;
    }
}

if (!function_exists('school_request_is_open_now')) {
    function school_request_is_open_now()
    {
        $tz = new DateTimeZone(SCHOOL_TIMEZONE);
        $now = new DateTime('now', $tz);
        $open = DateTime::createFromFormat('H:i', SCHOOL_OPEN_TIME, $tz);
        $close = DateTime::createFromFormat('H:i', SCHOOL_REQUEST_CLOSE_TIME, $tz);

        if (!$open || !$close) {
            return true;
        }

        $open->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));
        $close->setDate((int)$now->format('Y'), (int)$now->format('m'), (int)$now->format('d'));

        return ($now >= $open && $now <= $close);
    }
}

if (!function_exists('school_request_hours_label')) {
    function school_request_hours_label()
    {
        return SCHOOL_OPEN_TIME . ' - ' . SCHOOL_REQUEST_CLOSE_TIME;
    }
}
