<?php

namespace App\Console\Commands;

use App\Models\MarfilClient;
use App\Models\MarfilServer;
use Illuminate\Console\Command;

class MarfilServerDictionaryCommand extends Command
{
    /**
     * Client which the command will interact with.
     *
     * @var MarfilServer
     */
    private $server;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marfil:refresh-dictionaries';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Split dictionaries and set them up in the dictionaries table';

    /**
     * Create a new command instance.
     *
     * @param MarfilServer $server
     */
    public function __construct(MarfilServer $server)
    {
        parent::__construct();

        $this->server = $server;
        $server->setCommand($this);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            $this->line('Refreshing dictionaries...');

            $this->server->refreshDictionaries();
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }
}
