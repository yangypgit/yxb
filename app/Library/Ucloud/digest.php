<?php
namespace App\Library\Ucloud;

use App\Library\Ucloud\utils;

require_once("conf.php");
define("NO_AUTH_CHECK", 0);
define("HEAD_FIELD_CHECK", 1);
define("QUERY_STRING_CHECK", 2);

class digest
{
    // ----------------------------------------------------------
    function CanonicalizedResource($bucket, $key)
    {
        return "/" . $bucket . "/" . $key;
    }

    function CanonicalizedUCloudHeaders($headers)
    {

        $keys = array();
        foreach($headers as $header) {
            $header = trim($header);
            $arr = explode(':', $header);
            if (count($arr) < 2) continue;
            list($k, $v) = $arr;
            $k = strtolower($k);
            if (strncasecmp($k, "x-Ucloud") === 0) {
                $keys[] = $k;
            }
        }

        $c = '';
        sort($keys, SORT_STRING);
        foreach($keys as $k) {
            $c .= $k . ":" . trim($headers[$v], " ") . "\n";
        }
        return $c;
    }

    function UCloud_MakeAuth($auth)
    {
        if (isset($auth)) {
            return $auth;
        }

        global $UCLOUD_PUBLIC_KEY;
        global $UCLOUD_PRIVATE_KEY;

        return new UCloud_Auth($UCLOUD_PUBLIC_KEY, $UCLOUD_PRIVATE_KEY);
    }

//@results: token
    function UCloud_SignRequest($auth, $req, $type = HEAD_FIELD_CHECK)
    {
        return $this->UCloud_MakeAuth($auth)->SignRequest($req, $type);
    }

// ----------------------------------------------------------

}


