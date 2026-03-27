<?php

namespace OrdersBundle\Services;

use OrdersBundle\Entities\CompanyRelDada;
use ThirdPartyBundle\Services\DadaCentre\CityCodeService;

class CompanyRelDadaService
{
    private $companyRelDadaReposity;

    public function __construct()
    {
        $this->companyRelDadaReposity = app('registry')->getManager('default')->getRepository(CompanyRelDada::class);
    }

    /**
     * 新增
     * @param $data
     * @return mixed
     */
    public function createCompanyRelDada($data)
    {
        return $this->companyRelDadaReposity->create($data);
    }

    /**
     * 修改
     */
    public function updateCompanyRelDada($filter, $data)
    {
        return $this->companyRelDadaReposity->updateOneBy($filter, $data);
    }

    /**
     * 获取城市列表
     * @param $company_id
     * @return mixed
     */
    public function getCityList($company_id)
    {
        $cityList = app('redis')->get('dada_city_list');
        if (empty($cityList)) {
            $companyRelDadaService = new CompanyRelDadaService();
            $companyRelDada = $companyRelDadaService->getInfo(['company_id' => $company_id]);
            if (empty($companyRelDada['source_id'])) {
                $cityCodeService = new CityCodeService();
                $cityList = $cityCodeService->getLocalCityCode();
            } else {
                $cityCodeService = new CityCodeService();
                $cityList = $cityCodeService->list($company_id);
                $cityList = json_encode($cityList, JSON_UNESCAPED_UNICODE);
                app('redis')->set('dada_city_list', $cityList, 'EX', 86400);
            }
        }
        return json_decode($cityList, true);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->companyRelDadaReposity->$method(...$parameters);
    }
}
