<?php

namespace CompanysBundle\Transformers;

use League\Fractal\TransformerAbstract;
use CompanysBundle\Entities\WxShops;

class WxShopsTransformer extends TransformerAbstract
{
    public function transform(WxShops $wxShops)
    {
        return normalize($wxShops);
    }
}
