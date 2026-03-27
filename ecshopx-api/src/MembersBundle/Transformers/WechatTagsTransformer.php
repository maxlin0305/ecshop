<?php

namespace MembersBundle\Transformers;

use League\Fractal\TransformerAbstract;
use MembersBundle\Entities\WechatTags;

class WechatTagsTransformer extends TransformerAbstract
{
    public function transform(WechatTags $wechatTags)
    {
        return normalize($wechatTags);
    }
}
