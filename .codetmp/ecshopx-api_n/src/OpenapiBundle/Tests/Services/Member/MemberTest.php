<?php

namespace OpenapiBundle\Tests\Services\Member;

use EspierBundle\Services\TestBaseService;
use OpenapiBundle\Services\Member\MemberService;

class MemberTest extends TestBaseService
{
    public function testCreate()
    {
        // 20554
        $params = [
            // 会员信息
            "mobile" => "17321265274", // 手机号
            "source_from" => "api", // 来源渠道
            "inviter_id" => 0, // 推荐人的用户id
            "salesperson_id" => 0, // 需要绑定的导购id
            "union_id" => "oCzyo50TTotbWvc4m2_LiLmYf7oc", // 微信的unionid
            "status" => 1, // 会员的状态，【0 已禁用】【1 未禁用】
            // 会员标签
            "tag_name" => (array)explode(",", "内部会员,优质会员"), // 标签名
            "tag_id" => (array)explode(",", "3,4,11111111"), // 标签名
            // 会员卡与等级
            "card_code" => "", // 会员卡号
            "grade_id" => 0, // 会员等级id
            // 会员积分
            // "available_point"       => (string)$request->input("available_point"), // 剩余的积分
            // "total_point"           => (string)$request->input("total_point"), // 累计积分（只增不减）
            // 会员储值
            // "deposit_money"         => (string)$request->input("deposit_money"), // 储值余额, 单位为分
            // "total_deposit_money"   => (string)$request->input("total_deposit_money"), // 累计储值金额，单位为分
            // 会员基础信息
            "username" => "🐮🍺", // 姓名
            "nickname" => "yjm", // 昵称
            "avatar_url" => "https://pics4.baidu.com/feed/3ac79f3df8dcd1007a4125911d92eb18b8122f47.jpeg?token=d7ba840103a8d3f2b13ca6db6140b6a6", // 头像url
            "sex" => 1, // 性别，【0 未知】【1 男】【2 女】
            "birthday" => "2021-06-18 11:34:15", // 生日，日期格式 2021-06-16 15:35:41
            "habbit" => [
                ["name" => "游戏", "ischecked" => "true"]
            ], // 爱好
            "edu_background" => 4, // 学历  【0 硕士及以上】【1 本科】【2 大专】【3 高中/中专及以下】【4 其他】
            "income" => 4, // 年收入 【0 5万以下】【1 5万 ~ 15万】【2 15万 ~ 30万】【3 30万以上】【4 其他】
            "industry" => 12, // 行业 【0 金融/银行/投资】【1 计算机/互联网】【2 媒体/出版/影视/文化】【3 政府/公共事业】【4 房地产/建材/工程】【5 咨询/法律】【6 加工制造】【7 教育培训】【8 医疗保健】【9 运输/物流/交通】【10 零售/贸易】【11 旅游/度假】【12 其他】
            "email" => "123456789@qq.com", // email
            "address" => "上海市徐汇区宜山路700号C1栋12楼", // 地址
            "remakes" => "备注一些信息 ...", // 备注
        ];
        (new MemberService())->createDetail($this->getCompanyId(), $params);
    }
}
