<?php

namespace ThirdPartyBundle\Services\ShopexCrm;

class GetMemberListService
{
    private $apiName = 'getMemberList';

    public function GetMemberList($mobile)
    {
        $data['mobiles'] = $mobile;
        $data['source'] = 'custom_source1';
        $request = new Request();
        $result = $request->sendRequest($this->apiName, $data);
        if (!empty($result)) {
            $result = json_decode($result, true);
        }
        return $result;
    }
}
