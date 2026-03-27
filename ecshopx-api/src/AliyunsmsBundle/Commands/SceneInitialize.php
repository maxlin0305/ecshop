<?php

namespace AliyunsmsBundle\Commands;

use AliyunsmsBundle\Entities\Scene;
use CompanysBundle\Services\CompanysService;
use Illuminate\Console\Command;

class SceneInitialize extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'aliyunsms:scene:initialize {company_id}';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化短信场景 参数：companyId';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $companyId = $this->argument('company_id');
        $company = (new CompanysService())->getInfo(['company_id'=> $companyId]);
        if(!$company) {
            echo "companyId 不存在";
            return true;
        }
        try {
            $input = file_get_contents(storage_path('static/sms_scene.json'));
            $input = json_decode($input, true);
        } catch (\Exception $e) {
            echo "读取json文件出错".$e->getMessage();
            return true;
        }
        if (!$input) {
            echo "未读取到模板json文件";
            return true;
        }
        $repository = app('registry')->getManager('default')->getRepository(Scene::class);
        //判断是否执行过
        if($repository->getInfo(['company_id' => $companyId])) {
            return true;
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        //template_type: 0-验证码; 1-短信通知; 2-推广短信
        try {
            foreach ($input as $item) {
                $tmp = [
                    'company_id' => $companyId,
                    'scene_name' => $item['scene_name'],
                    'scene_title' => $item['scene_title'],
                    'template_type' => $item['template_type'],
                    'default_template' => $item['default_template'] ?? null,
                ];
                if($item['variables'] ?? 0) {
                    $tmp['variables'] = json_encode($item['variables']);
                }
                $repository->create($tmp);
            }
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            echo "导入短信场景数据出错：".$e->getMessage();
            return true;
        }
        echo "操作完成".PHP_EOL;
    }
}
