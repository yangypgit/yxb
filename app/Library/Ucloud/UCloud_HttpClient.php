<?php
namespace App\Library\Ucloud;

use App\Library\Ucloud\http;

class UCloud_HttpClient
{
    private $http;

    public function __construct()
    {
        $this->http = new http();
    }

    //@results: ($resp, $error)
    public function RoundTrip($req)
    {
        return $this->http->UCloud_Client_Do($req);
    }
}
