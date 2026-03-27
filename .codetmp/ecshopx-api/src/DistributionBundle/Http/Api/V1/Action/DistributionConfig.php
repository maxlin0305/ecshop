<?php

namespace DistributionBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use DistributionBundle\Services\DistributionService;
use Illuminate\Http\Request;

class DistributionConfig extends Controller
{
    /**
     * @SWG\Get(
     *     path="/distribution/config",
     *     summary="获取分润配置",
     *     tags={"店铺"},
     *     description="获取分润配置",
     *     operationId="getConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getConfig()
    {
        $distributionService = new DistributionService();
        $companyId = app('auth')->user()->get('company_id');
        $result = $distributionService->getDistributionConfig($companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/distribution/config",
     *     summary="保存分润配置",
     *     tags={"店铺"},
     *     description="保存分润配置",
     *     operationId="setConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function setConfig(Request $request)
    {
        $params = $request->all(
            'distributor.show',
            'distributor.distributor',
            'distributor.seller',
            'distributor.popularize_seller',
            'distributor.distributor_seller',
            'distributor.plan_limit_time'
        );
        $distributionService = new DistributionService();

        $companyId = app('auth')->user()->get('company_id');
        $result = $distributionService->setDistributionConfig($companyId, $params);
        return $this->response->array($result);
    }
}
