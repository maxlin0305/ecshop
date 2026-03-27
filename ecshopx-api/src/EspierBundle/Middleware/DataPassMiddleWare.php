<?php

namespace EspierBundle\Middleware;

use Closure;
use CompanysBundle\Entities\OperatorDataPassLog;
use CompanysBundle\Repositories\OperatorDataPassLogRepository;
use CompanysBundle\Services\OperatorDataPassService;

class DataPassMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = app('auth')->user();
        $companyId = $user->get('company_id');
        $operatorId = $user->get('operator_id');
        if ($user->get('operator_type') == 'admin') {
            return $next($request);
        }
        if ($user->get('operator_type') == 'merchant' && $user->get('is_merchant_main') == '1') {
            return $next($request);
        }
        $passService = new OperatorDataPassService();
        if (!$passService->check($companyId, $operatorId)) {
            $request->attributes->add(['x-datapass-block' => 1]);
            return $next($request);
        }

        // 记录日志
        $router = $request->route();
        $path = $router[1]['as'];
        $url = $request->fullUrl();

        /** @var OperatorDataPassLogRepository $logRepo */
        $logRepo = app('registry')->getManager('default')->getRepository(OperatorDataPassLog::class);
        $logRepo->create([
            'company_id' => $companyId,
            'operator_id' => $operatorId,
            'create_time' => time(),
            'path' => $path,
            'url' => $url,
        ]);

        return $next($request);
    }
}
