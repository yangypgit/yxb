<?php
namespace App\Library\Ucloud;

use App\Library\Ucloud\http;
use App\Library\Ucloud\digest;

class UCloud_Auth {

    public $PublicKey;
    public $PrivateKey;
    private $http;
    private $digest;

    public function __construct($publicKey, $privateKey)
    {
        $this->PublicKey = $publicKey;
        $this->PrivateKey = $privateKey;

        $this->http = new http();
        $this->digest = new digest();
    }

    public function Sign($data)
    {
        $sign = base64_encode(hash_hmac('sha1', $data, $this->PrivateKey, true));
        return "UCloud " . $this->PublicKey . ":" . $sign;
    }

    //@results: $token
    public function SignRequest($req, $mimetype = null, $type = HEAD_FIELD_CHECK)
    {
        $url = $req->URL;
        $url = parse_url($url['path']);
        $data = '';
        $data .= strtoupper($req->METHOD) . "\n";
        $data .= $this->http->UCloud_Header_Get($req->Header, 'Content-MD5') . "\n";
        if ($mimetype)
            $data .=  $mimetype . "\n";
        else
            $data .= $this->http->UCloud_Header_Get($req->Header, 'Content-Type') . "\n";
        if ($type === HEAD_FIELD_CHECK)
            $data .= $this->http->UCloud_Header_Get($req->Header, 'Date') . "\n";
        else
            $data .= $this->http->UCloud_Header_Get($req->Header, 'Expires') . "\n";
        $data .= $this->digest->CanonicalizedUCloudHeaders($req->Header);
        $data .= $this->digest->CanonicalizedResource($req->Bucket, $req->Key);
        return $this->Sign($data);
    }
}
