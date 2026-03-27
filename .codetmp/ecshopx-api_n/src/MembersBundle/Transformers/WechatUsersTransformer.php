<?php

namespace MembersBundle\Transformers;

use League\Fractal\TransformerAbstract;
use MembersBundle\Entities\WechatUsers;

class WechatUsersTransformer extends TransformerAbstract
{
    public function transform(WechatUsers $wechatUsers)
    {
        return normalize($wechatUsers);
    }
}
