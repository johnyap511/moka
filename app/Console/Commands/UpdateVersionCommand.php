<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateVersionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update css and js versions';

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
        $file = config_path('version.php');
        $fd=fopen($file,"w");
        fwrite($fd, '<?php return ["versionNumber" => "'.time().'"];');
        fclose($fd);
    }
}
