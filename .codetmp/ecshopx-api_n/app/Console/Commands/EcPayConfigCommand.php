<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EcPayConfigCommand extends Command
{
    protected $signature = 'ecpay-config';

    protected $description = 'get ecpay-config';

    public function handle()
    {
        $this->info(json_encode(config('ecpay'), JSON_PRETTY_PRINT));
    }
}
