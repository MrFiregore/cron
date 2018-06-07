<?php

    if (!defined("__ROOT__")) {
        define("__ROOT__", substr(__DIR__, 0, strpos(__DIR__, "vendor") ?: strpos(__DIR__, "src")));
    }
    define("__LOCK__", __ROOT__ . "lock" . DIRECTORY_SEPARATOR);
    define("__LOG__", __ROOT__ . "log" . DIRECTORY_SEPARATOR);
