<?php

namespace MembersBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use MembersBundle\Entities\MembersOffineLog;
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\MemberRelTags;
use MembersBundle\Traits\GetCodeTrait;
use PointBundle\Services\PointMemberService;
use GuzzleHttp\Client as Client;

class MemberUploadService
{
    use GetCodeTrait;

    public $header = [
        '手機號碼' => 'mobile',
        '原實體卡號' => 'offline_card_code',
        '姓名' => 'username',
        '性別' => 'sex',
        '會員等級' => 'grade_name',
        '生日' => 'birthday',
        '入會日期' => 'created',
        //'開卡門店'   => 'shop_name',
        '郵箱' => 'email',
        '地址' => 'address',
        '標簽' => 'tags',
        '積分' => 'point',
    ];

    public $headerInfo = [
        '手機號碼' => ['size' => 32, 'remarks' => '不得重復，手機號如果大於11位時，請關閉excel單元格的科學記數法，常用禁用方法：「單元格格式」-「自定義」-「類型」改為「0」', 'is_need' => true],
        '原實體卡號' => ['size' => 20, 'remarks' => '不得重復', 'is_need' => false],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => true],
        '性別' => ['size' => 2, 'remarks' => '性別只能為男,女，未知', 'is_need' => true],
        '會員等級' => ['size' => 8, 'remarks' => '會員等級需和在會員卡中配置的會員等級一致', 'is_need' => true],
        '生日' => ['size' => 10, 'remarks' => '生日時間不得大於今日，格式為mm/dd/yyyy, 如:1/12/2019', 'is_need' => false],
        '入會日期' => ['size' => 10, 'remarks' => '入會時間不得大於今日，格式為mm/dd/yyyy, 如:12/1/2019', 'is_need' => true],
        //'開卡門店'   => 'shop_name',
        '郵箱' => ['size' => 32, 'remarks' => '', 'is_need' => false],
        '地址' => ['size' => 128, 'remarks' => '', 'is_need' => false],
        '標簽' => ['size' => 128, 'remarks' => '標簽名稱多個用逗號「,」隔開(註：逗號為半角逗號),並且標簽必須已存在系統中,例子：時尚,超級會員', 'is_need' => false],
        '積分' => ['size' => 32, 'remarks' => '會員初始積分', 'is_need' => false],
    ];

    public $isNeedCols = [
        '手機號碼' => 'mobile',
        '姓名' => 'username',
        '性別' => 'sex',
        '會員等級' => 'grade_name',
        '入會日期' => 'created',
        //'開卡門店' => 'shop_name',
    ];

    /**
     * 驗證上傳的會員信息
     */
    public function check($fileObject)
    {
        $extension = $fileObject->getClientOriginalExtension();
        if ($extension != 'xlsx') {
            throw new BadRequestHttpException('會員信息上傳只支持Excel文件格式');
        }
    }

    public $tmpTarget = null;

    /**
     * getFilePath function
     *
     * @param $filePath
     * @param string $fileExt
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getFilePath($filePath, $fileExt = '')
    {
        $url = $this->getFileSystem()->privateDownloadUrl($filePath);

        $client = new Client();
        $content = $client->get($url)->getBody()->getContents();

        $this->tmpTarget = tempnam('/tmp', 'import-file') . $fileExt;
        file_put_contents($this->tmpTarget, $content);

        return $this->tmpTarget;
    }

    public function finishHandle()
    {
        unlink($this->tmpTarget);
        return true;
    }


    public function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 獲取頭部標題
     */
    public function getHeaderTitle()
    {
        return ['all' => $this->header, 'is_need' => $this->isNeedCols, 'headerInfo' => $this->headerInfo];
    }

    private function validatorData($row)
    {
        $arr = ['mobile', 'email', 'birthday', 'address', 'username', 'sex', 'grade_name', 'created', 'point'];
        $data = [];
        foreach ($arr as $column) {
            if (isset($row[$column])) {
                $data[$column] = trim($row[$column]);
            }
        }

        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $validatorData = $this->validatorData($row);

        $rules = [
            'mobile'     => ['max:32', '請填寫正確的手機號'],
            'username'   => ['required|max:20', '請填寫正確的姓名'],
            'sex'        => ['required|max:6', '請填寫正確的性別'],
            'grade_name' => ['required|max:8', '請填寫正確的會員等級'],
            'created'    => ['required|date_format:n/j/Y', '請填寫正確的入會日期 請填寫 月/日/年 格式'],
            'birthday'   => ['date_format:n/j/Y', '請填寫正確的生日日期 請填寫 月/日/年 格式'],
            'address'    => ['max:128', '請填寫正確的地址'],
            'email'      => ['email', '請填寫正確的郵箱'],
            'point'      => ['numeric|min:0', '請填寫正確的積分'],
        ];

        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
        }

        if (!$row['mobile'] && !$row['offline_card_code']) {
            throw new BadRequestHttpException('手機號和原實體卡號必填一個');
        }

        if ($row['tags']) {
            $memberTagsService = new MemberTagsService();
            $tags = explode(',', $row['tags']);
            $list = $memberTagsService->getListTags(['tag_name' => $tags, 'company_id' => $companyId]);
            if (!($list['list'] ?? null)) {
                throw new BadRequestHttpException($row['tags'].'標簽不存在');
            }

            $tagsdata = array_column($list['list'], 'tag_name');
            if (count($tags) != count($tagsdata)) {
                foreach ($tags as $v) {
                    if (!in_array($v, $tagsdata)) {
                        throw new BadRequestHttpException($v.'標簽不存在');
                    }
                }
            }

            $tagIds = array_column($list['list'], 'tag_id');
        }

        if ($row['birthday']) {
            $birthdayArr = explode('/', $row['birthday']);
            $birthdayStr = $birthdayArr[2].'-'.$birthdayArr[0].'-'.$birthdayArr[1];
            if (strtotime($birthdayStr) > time()) {
                throw new BadRequestHttpException('生日不可大於當前導入時間');
            }
            $row['birthday'] = date("Y-m-d", strtotime($birthdayStr));
        }

        if ($row['created']) {
            $createdArr = explode('/', $row['created']);
            $createdStr = $createdArr[2].'-'.$createdArr[0].'-'.$createdArr[1];
            if (strtotime($createdStr) > time()) {
                throw new BadRequestHttpException('入會日期不可大於當前導入時間');
            }
            $row['created'] = strtotime($createdStr);
        }


        // 如果有實體卡但是沒有手機號，那麽暫時把數據存儲到實體卡信息日誌裏
        // 用於後續手機號綁定實體卡
        if ($row['offline_card_code'] && !$row['mobile']) {
            $membersOffineRepository = app('registry')->getManager('default')->getRepository(MembersOffineLog::class);
            $offlineMember = [
                'company_id' => $companyId,
                'offline_card_code' => trim($row['offline_card_code']),
                'username' => trim($row['username']),
                'sex' => $this->getSex($row['sex']),
                'grade_id' => $this->getGradeIdByName($companyId, $row['grade_name']),
                'birthday' => $row['birthday'],
                'address' => $row['address'],
                'email' => trim($row['email']),
                'created_time' => $row['created'],
                'created' => time(),
                'updated' => time(),
            ];
            $membersOffineRepository->create($offlineMember);
        } else {
            $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
            $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

            if ($row['offline_card_code']) {
                $offMember = $membersRepository->get(['company_id' => $companyId, 'offline_card_code' => $row['offline_card_code']]);
                if ($offMember) {
                    throw new BadRequestHttpException('當前原實體卡號已經是會員');
                }
            }

            if ($row['mobile']) {
                $member = $membersRepository->get(['company_id' => $companyId, 'mobile' => $row['mobile']]);
                if ($member) {
                    throw new BadRequestHttpException('當前手機號已經是會員');
                }
            }

            //新增-會員信息
            $memberInfo = [
                'company_id' => $companyId,
                'offline_card_code' => trim($row['offline_card_code']),
                'username' => trim($row['username']),
                'mobile' => trim($row['mobile']),
                'sex' => $this->getSex($row['sex']),
                'grade_id' => $this->getGradeIdByName($companyId, $row['grade_name']),
                'birthday' => trim($row['birthday']),
                'address' => $row['address'],
                'email' => $row['email'],
                'created' => $row['created'],
                'password' => substr(str_shuffle('QWERTYUIOPASDFGHJKLZXCVBNM1234567890qwertyuiopasdfghjklzxcvbnm'), 5, 10),
            ];

            if ($memberInfo['offline_card_code']) {
                $memberInfo['user_card_code'] = $memberInfo['offline_card_code'];
            } else {
                $memberInfo['user_card_code'] = $this->getCode();
            }
            $memberInfo["region_mobile"] = $memberInfo["mobile"];
            $memberInfo["mobile_country_code"] = "86";
            $memberInfo["other_params"] = json_encode(['is_upload_member' => true]);

            $conn = app('registry')->getConnection('default');
            $conn->beginTransaction();
            try {
                $result = $membersRepository->create($memberInfo);
                $memberInfo['user_id'] = $result['user_id'];

                if ($tagIds ?? null) {
                    $memberTagsService->createRelTagsByUserId($result['user_id'], $tagIds, $companyId);
                }
                $membersInfoRepository->create($memberInfo);
                if (isset($row['point']) && $row['point'] > 0) {
                    $pointMemberService = new PointMemberService();
                    $pointMemberService->addPoint($memberInfo['user_id'], $companyId, $row['point'], 15, true, '會員信息導入，初始化積分');
                }
                $conn->commit();
            } catch (\Exception $e) {
                $conn->rollback();
                throw new BadRequestHttpException('保存數據錯誤');
            }
        }
    }

    private function getGradeIdByName($companyId, $gradeName)
    {
        $memberCardService = new MemberCardService();
        $gradeId = $memberCardService->getGradeIdByName($companyId, $gradeName);
        if (!$gradeId) {
            throw new BadRequestHttpException('會員等級：'.$gradeName.'  不存在');
        }
        return $gradeId;
    }

    private function getSex($str)
    {
        if ($str == '男') {
            return 1;
        } elseif ($str == '女') {
            return 2;
        } else {
            return 0;
        }
    }
}
