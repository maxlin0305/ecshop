<?php

namespace SuperAdminBundle\Console;

use Illuminate\Console\Command;
use SuperAdminBundle\Services\ShopMenuService;

class UploadDealerMenuCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'menu:upload_dealer';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '导入经销商端菜单';

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
        $dealerJson = file_get_contents(storage_path('static/dealer_menu.json'));
        //经销商菜单
        if ($dealerJson) {
            $menus = json_decode($dealerJson, true);
            $shopMenuService = new ShopMenuService();
            $shopMenuService->uploadMenus($menus);
        }
        $this->info('导入经销商菜单成功，请到shop_menu表中确认是否正确');
    }
}
