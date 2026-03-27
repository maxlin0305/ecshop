<?php

namespace MembersBundle\Services;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use KaquanBundle\Services\MemberCardService;
use MembersBundle\Entities\Members;
use MembersBundle\Entities\MembersInfo;
use MembersBundle\Entities\MemberRelTags;
use MembersBundle\Traits\GetCodeTrait;
use PointBundle\Services\PointMemberService;
use GuzzleHttp\Client as Client;

class MemberUploadUpdateService
{
    use GetCodeTrait;

    public $header = [
        '手機號碼' => 'mobile',
        '姓名' => 'username',
        '性別' => 'sex',
        '會員等級' => 'grade_name',
        '郵箱' => 'email',
        '標簽' => 'tags',
        '禁用' => 'disabled',
        '積分' => 'point',
    ];

    public $headerInfo = [
        '手機號碼' => ['size' => 32, 'remarks' => '不得重復，手機號如果大於11位時，請關閉excel單元格的科學記數法，常用禁用方法：「單元格格式」-「自定義」-「類型」改為「0」', 'is_need' => true],
        '姓名' => ['size' => 20, 'remarks' => '', 'is_need' => false],
        '性別' => ['size' => 2, 'remarks' => '性別只能為男,女，未知', 'is_need' => false],
        '會員等級' => ['size' => 8, 'remarks' => '會員等級需和在會員卡中配置的會員等級一致', 'is_need' => false],
        '郵箱' => ['size' => 32, 'remarks' => '標準郵箱格式', 'is_need' => false],
        '標簽' => ['size' => 128, 'remarks' => '標簽名稱多個用逗號「,」隔開(註：逗號為半角逗號),並且標簽必須已存在系統中,例子：時尚,超級會員', 'is_need' => false],
        '禁用' => ['size' => 1, 'remarks' => '否:可用；是:禁用', 'is_need' => false],
        '積分' => ['size' => 32, 'remarks' => '增加積分：正整數；減少積分：-正整數', 'is_need' => false],
    ];

    public $isNeedCols = [
        '手機號碼' => 'mobile'
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
     * @return void
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
        $arr = ['mobile', 'email', 'username', 'sex', 'grade_name', 'point'];
        $data = [];
        foreach ($arr as $column) {
            if ($row[$column]) {
                $data[$column] = trim($row[$column]);
            }
        }

        return $data;
    }

    public function handleRow($companyId, $row)
    {
        $validatorData = $this->validatorData($row);

        $rules = [
            'mobile' => ['required|max:32', '請填寫正確的手機號'],
            'username' => ['max:20', '請填寫正確的姓名'],
            'sex' => ['max:6', '請填寫正確的性別'],
            'grade_name' => ['max:8', '請填寫正確的會員等級'],
            'email' => ['email', '請填寫正確的郵箱'],
            'disabled' => ['max:1', '請填寫正確的禁用'],
            'point' => ['numeric', '請填寫正確的積分'],
        ];
        $errorMessage = validator_params($validatorData, $rules, false);
        if ($errorMessage) {
            $msg = implode(', ', $errorMessage);
            throw new BadRequestHttpException($msg);
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

        $membersRepository = app('registry')->getManager('default')->getRepository(Members::class);
        $membersInfoRepository = app('registry')->getManager('default')->getRepository(MembersInfo::class);

        $member = $membersRepository->get(['company_id' => $companyId, 'mobile' => $row['mobile']]);
        if (empty($member)) {
            throw new BadRequestHttpException('會員數據不存在');
        }

        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter = [
                'user_id' => $member['user_id'],
            ];

            if ($row['username'] ?? null) {
                $data['username'] = trim($row['username']);
            }

            if ($row['sex'] ?? null) {
                $data['sex'] = $this->getSex($row['sex']);
            }

            if ($row['email'] ?? null) {
                $data['email'] = $row['email'];
            }
            if ($data ?? null) {
                $membersInfoRepository->updateOneBy($filter, $data);
            }


            //更新會員等級
            if ($row['grade_name'] ?? null) {
                $params['grade_id'] = $this->getGradeIdByName($companyId, $row['grade_name']);
            }
            // 更新禁用
            if ($row['disabled'] ?? null) {
                $disabled = $this->getDisabled(trim($row['disabled']));
                if ($disabled != 2) {
                    $params['disabled'] = $disabled;
                }
            }
            if ($params ?? null) {
                $membersRepository->update($params, $filter);
            }

            //更新會員標簽
            if ($tagIds ?? null) {
                $memberTagsService->createRelTagsByUserId($member['user_id'], $tagIds, $companyId);
            }
            // 更新會員積分
            if ($row['point'] ?? null) {
                if ($row['point'] < 0) {
                    $status = false;
                } else {
                    $status = true;
                }
                $point = intval(abs($row['point']));
                if ($point > 0) {
                    $pointMemberService = new PointMemberService();
                    $pointMemberService->addPoint($member['user_id'], $companyId, $point, 15, $status, '會員信息導入，修改會員積分');
                }
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new BadRequestHttpException($e->getMessage());
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

    private function getDisabled($str)
    {
        if ($str == '否') {
            return 0;
        } elseif ($str == '是') {
            return 1;
        } else {
            return 2;
        }
    }
}
