<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * @class TestLog
 */
class TestLog extends Command
{
    /**
     * @var string
     */
    protected $signature = 'test:log';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        Log::info('Testing');
    }
}
