<?php
namespace App\Library\Ucloud;

use App\Library\Ucloud\http;
use App\Library\Ucloud\digest;

class UCloud_AuthHttpClient
{
    public $Auth;
    public $Type;
    public $MimeType;

    private $http;
    private $digest;

    public function __construct($auth, $mimetype = null, $type = HEAD_FIELD_CHECK)
    {
        $this->digest = new digest();

        $this->Type = $type;
        $this->MimeType = $mimetype;
        $this->Auth = $this->digest->UCloud_MakeAuth($auth);

        $this->http = new http();

    }

    //@results: ($resp, $error)
    public function RoundTrip($req)
    {
        if ($this->Type === HEAD_FIELD_CHECK) {
            $token = $this->Auth->SignRequest($req, $this->MimeType, $this->Type);
            $req->Header['Authorization'] = $token;
        }
        return $this->http->UCloud_Client_Do($req);
    }
}
