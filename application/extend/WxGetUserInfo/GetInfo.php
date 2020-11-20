<?php
namespace app\extend\WxGetUserInfo;

class GetInfo
{
    private $appid;
    private $sessionKey;
    private $iv ;
    private $encryptedData;
    public function __construct($sessionKey, $iv, $encryptedData)
    {
        $this->appid = config('appid');
        $this->sessionKey = $sessionKey;
        $this->iv = $iv;
        $this->encryptedData = $encryptedData;

    }
    /**
     * @return int
     */
    public function render()
    {
        $pc = new wxBizDataCrypt($this->appid, $this->sessionKey);
        $errCode = $pc->decryptData($this->encryptedData, $this->iv, $data);
        if ($errCode == 0) {
            return $data;
        } else {
            return $errCode;
        }
    }
}