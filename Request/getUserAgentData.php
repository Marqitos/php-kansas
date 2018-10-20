<?php

namespace Kansas\Request;
use function Kansas\Request\bbcParseUserAgent;

// Devuelve el robot o navegador y sistema operativo, a partir de USER_AGENT
function getUserAgentData($userAgent) {
    global $robot, $os, $browser;
    require_once 'bbClone/robot.php';
    require_once 'Kansas/Request/bbcParseUserAgent.php';
    $data = bbcParseUserAgent($userAgent, $robot);
    if($data !== false)
        return [ 'robot' => $data ];

    $result = [];
    require_once 'bbClone/os.php';
    $data = bbcParseUserAgent($userAgent, $os);
    if($data !== false)
        $result['os'] = $data;
    require_once 'bbClone/browser.php';
    $data = bbcParseUserAgent($userAgent, $browser);
    if($data !== false)
        $result['browser'] = $data;
    return $result;
}