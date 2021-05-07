<?php

function jsonEncode($obj) {
    return json_encode($obj, JSON_UNESCAPED_UNICODE);
}

function jsonDecode($json) {
    return json_decode($json, true);
}

function toCamelCase(string $input, string $separator = '_'): string {
    return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
}


/* Connection helpers */

function getMySQL(): mysql {
    static $link = null;
    if (is_null($link))
        $link = new mysql(MYSQL_HOST, MYSQL_USER, MYSQL_PASSWORD, MYSQL_DB);
    return $link;
}

function getJobdMaster(): jobd\MasterClient {
    return new jobd\MasterClient(JOBD_PORT, JOBD_HOST, JOBD_PASSWORD);
}


/* Command line helpers */

function green(string $s): string {
    return "\033[32m$s\033[0m";
}

function yellow(string $s): string {
    return "\033[33m$s\033[0m";
}

function red(string $s): string {
    return "\033[31m$s\033[0m";
}

function input(string $prompt): string {
    echo $prompt;
    return substr(fgets(STDIN), 0, -1);
}
