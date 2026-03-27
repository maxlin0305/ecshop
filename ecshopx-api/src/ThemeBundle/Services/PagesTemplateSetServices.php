<?php

namespace ThemeBundle\Services;

use ThemeBundle\Entities\PagesTemplateSet;

class PagesTemplateSetServices
{
    private $pagesTemplateSetRepository;

    public function __construct()
    {
        $this->pagesTemplateSetRepository = app('registry')->getManager('default')->getRepository(PagesTemplateSet::class);
    }

    /**
     * 保存数据
     */
    public function saveData($params)
    {
        //判断数据是否存着
        $info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $params['company_id']]);
        if (empty($info)) {
            $result = $this->pagesTemplateSetRepository->create($params);
        } else {
            $result = $this->pagesTemplateSetRepository->updateOneBy(['company_id' => $params['company_id']], $params);
        }

        return $result;
    }

    /**
     * 获取设置信息
     */
    public function getInfo($params)
    {
        //判断数据是否存着
        $info = $this->pagesTemplateSetRepository->getInfo(['company_id' => $params['company_id']]);

        return $info;
    }
}
