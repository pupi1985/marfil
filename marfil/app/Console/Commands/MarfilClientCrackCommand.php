<?php

namespace App\Console\Commands;

use App\Models\MarfilClient;
use Illuminate\Console\Command;

class MarfilClientCrackCommand extends Command
{
    /**
     * Client which the command will interact with.
     *
     * @var MarfilClient
     */
    private $client;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marfil:crack {server} {file} {bssid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a crack request to the server';

    /**
     * Create a new command instance.
     *
     * @param MarfilClient $server
     */
    public function __construct(MarfilClient $server)
    {
        parent::__construct();

        $this->client = $server;
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
            $server = $this->argument('server');
            $file = $this->argument('file');
            $bssid = $this->argument('bssid');

            $this->line('Sending crack request...');

            $this->client->crack($server, $file, $bssid);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }
}
