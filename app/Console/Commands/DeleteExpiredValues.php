<?php

namespace App\Console\Commands;

use App\Models\Value;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeleteExpiredValues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:values';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove all values stored over more than 5 minutes.';

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
     * @return int
     */
    public function handle()
    {
        $values = Value::where('expires_at', '<=', now())->get();

        $values->chunk(10000)->each(function($value) {
            $value->delete();
        });
    }
}
