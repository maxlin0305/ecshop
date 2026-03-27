<?php

namespace MembersBundle\Entities;

use Dingo\Api\Exception\ResourceException;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MembersInfo 会员详情信息表
 *
 * @ORM\Table(name="members_info", options={"comment"="会员详情信息表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersInfoRepository")
 */
class MembersInfo
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", nullable=true, length=500, options={"comment":"姓名"})
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", nullable=true, length=255, options={"comment":"头像"})
     */
    private $avatar;

    /**
     * @var integer
     *
     *  @ORM\Column(name="sex", type="smallint", nullable=true, options={"comment":"性别。0 未知 1 男 2 女","default":0})
     */
    private $sex;

    /**
     * @var string
     *
     * @ORM\Column(name="birthday", type="string", nullable=true, length=100, options={"comment":"出生日期"})
     */
    private $birthday;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", nullable=true, length=255, options={"comment":"家庭住址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true, length=100, options={"comment":"常用邮箱"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="industry", type="string", nullable=true, options={"comment":"从事行业"})
     */
    private $industry;

    /**
     * @var string
     *
     * @ORM\Column(name="income", type="string", nullable=true, length=50, options={"comment":"年收入"})
     */
    private $income;

    /**
     * @var string
     *
     * @ORM\Column(name="edu_background", type="string", nullable=true, length=50, options={"comment":"学历"})
     */
    private $edu_background;

    /**
     * @var json_array
     *
     * @ORM\Column(name="habbit", type="json_array", nullable=true, options={"comment":"爱好"})
     */
    private $habbit;

    /**
     * @var boolean
     *
     * @ORM\Column(name="have_consume", type="boolean", nullable=true, options={"comment":"是否有消费","default":false})
     */
    private $have_consume = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="year", type="integer", nullable=true, options={"comment":"生日年份","default":0})
     */
    private $year;

    /**
     * @var integer
     *
     * @ORM\Column(name="month", type="integer", nullable=true, options={"comment":"生日月份","default":0})
     */
    private $month;

    /**
     * @var integer
     *
     * @ORM\Column(name="day", type="integer", nullable=true, options={"comment":"生日日期","default":0})
     */
    private $day;

    /**
     * @var integer
     *
     * @ORM\Column(name="other_params", type="text", options={"comment":"其他参数，透传前端传递进来的参数"})
     */
    private $other_params;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * @var string
     *
     * @ORM\Column(name="lng", type="string", options={"comment":"地图纬度","default": ""})
     */
    private $lng;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", options={"comment":"地图经度","default": ""})
     */
    private $lat;

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return UserInfo
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UserInfo
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return UserInfo
     */
    public function setUsername($username)
    {
        $this->username = fixedencrypt($username);

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return fixeddecrypt($this->username);
    }

    /**
     * Set sex
     *
     * @param integer $sex
     *
     * @return UserInfo
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex
     *
     * @return integer
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set birthday
     *
     * @param string $birthday
     *
     * @return UserInfo
     */
    public function setBirthday($birthday)
    {
        $this->birthday = $birthday;

        return $this;
    }

    /**
     * Get birthday
     *
     * @return string
     */
    public function getBirthday()
    {
        return $this->birthday;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return UserInfo
     */
    public function setAddress($address)
    {
        $geocode = $this->getLngAndLat($address);
        if (!$geocode) {
            throw new Exception('地區轉換錯誤,請換個地址！'.json_encode($geocode));
        }
        $location = $geocode[0]['geometry']['location']??[];
        if (!isset($location['lat'], $location['lng'])) {
            throw new Exception('地區轉換錯誤,請換個地址！'.json_encode($geocode));
        }
        $this->address = $address;
        $this->lat = $location['lat'];
        $this->lng = $location['lng'];
        return $this;
    }




    /**
     * 地址转经纬度
     */
    public function getLngAndLat($address)
    {
        $key = config('GOOGLE_MAP_KEY',"AIzaSyBmSZouTYm8ViLN3MOrpFuCNvCJfBriaSs");
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?address={$address}&key=$key",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET'
        ));
        $response = curl_exec($curl);
        curl_close($curl);
//        $response = '{ "results" : [ { "address_components" : [ { "long_name" : "411", "short_name" : "411", "types" : [ "postal_code" ] }, { "long_name" : "新福里", "short_name" : "新福里", "types" : [ "administrative_area_level_3", "political" ] }, { "long_name" : "太平區", "short_name" : "太平區", "types" : [ "administrative_area_level_2", "political" ] }, { "long_name" : "台中市", "short_name" : "台中市", "types" : [ "administrative_area_level_1", "political" ] }, { "long_name" : "Taiwan", "short_name" : "TW", "types" : [ "country", "political" ] } ], "formatted_address" : "411, Taiwan, 台中市太平區", "geometry" : { "bounds" : { "northeast" : { "lat" : 24.1693703, "lng" : 120.8378002 }, "southwest" : { "lat" : 24.0403117, "lng" : 120.6995378 } }, "location" : { "lat" : 24.119605, "lng" : 120.7809676 }, "location_type" : "APPROXIMATE", "viewport" : { "northeast" : { "lat" : 24.1693703, "lng" : 120.8378002 }, "southwest" : { "lat" : 24.0403117, "lng" : 120.6995378 } } }, "place_id" : "ChIJO-rJaVA9aTQRMRKY334H_Rs", "types" : [ "postal_code" ] } ], "status" : "OK" }';

        $response = json_decode($response, true);
        if ($response['status'] !== 'OK') {
            return false;
        }
        return $response['results'];

    }
    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }
    /**
     * Get getLng
     *
     * @return string
     */
    public function getLng()
    {
        return $this->lng;
    }
    /**
     * Get address
     *
     * @return string
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return UserInfo
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set industry
     *
     * @param array $industry
     *
     * @return UserInfo
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * Get industry
     *
     * @return array
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * Set income
     *
     * @param string $income
     *
     * @return UserInfo
     */
    public function setIncome($income)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income
     *
     * @return string
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set eduBackground
     *
     * @param string $eduBackground
     *
     * @return UserInfo
     */
    public function setEduBackground($eduBackground)
    {
        $this->edu_background = $eduBackground;

        return $this;
    }

    /**
     * Get eduBackground
     *
     * @return string
     */
    public function getEduBackground()
    {
        return $this->edu_background;
    }

    /**
     * Set habbit
     *
     * @param array $habbit
     *
     * @return UserInfo
     */
    public function setHabbit($habbit)
    {
        $this->habbit = $habbit;

        return $this;
    }

    /**
     * Get habbit
     *
     * @return array
     */
    public function getHabbit()
    {
        return $this->habbit;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return UserInfo
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return UserInfo
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set haveConsume
     *
     * @param boolean $haveConsume
     *
     * @return MembersInfo
     */
    public function setHaveConsume($haveConsume)
    {
        $this->have_consume = $haveConsume;

        return $this;
    }

    /**
     * Get haveConsume
     *
     * @return boolean
     */
    public function getHaveConsume()
    {
        return $this->have_consume;
    }

    /**
     * Set year
     *
     * @param integer $year
     *
     * @return MembersInfo
     */
    public function setYear($year)
    {
        $this->year = $year;

        return $this;
    }

    /**
     * Get year
     *
     * @return integer
     */
    public function getYear()
    {
        return $this->year;
    }

    /**
     * Set month
     *
     * @param integer $month
     *
     * @return MembersInfo
     */
    public function setMonth($month)
    {
        $this->month = $month;

        return $this;
    }

    /**
     * Get month
     *
     * @return integer
     */
    public function getMonth()
    {
        return $this->month;
    }

    /**
     * Set day
     *
     * @param integer $day
     *
     * @return MembersInfo
     */
    public function setDay($day)
    {
        $this->day = $day;

        return $this;
    }

    /**
     * Get day
     *
     * @return integer
     */
    public function getDay()
    {
        return $this->day;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     *
     * @return MembersInfo
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set otherParams.
     *
     * @param string $otherParams
     *
     * @return MembersInfo
     */
    public function setOtherParams($otherParams)
    {
        $this->other_params = $otherParams;

        return $this;
    }

    /**
     * Get otherParams.
     *
     * @return string
     */
    public function getOtherParams()
    {
        return $this->other_params;
    }
}
