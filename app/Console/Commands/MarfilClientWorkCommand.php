<?php

namespace App\Console\Commands;

use App\Models\MarfilClient;
use Illuminate\Console\Command;

class MarfilClientWorkCommand extends Command
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
    protected $signature = 'marfil:work {server}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start working (wait for work and do it when there is any)';

    /**
     * Create a new command instance.
     *
     * @param MarfilClient $client
     */
    public function __construct(MarfilClient $client)
    {
        parent::__construct();

        $this->client = $client;
        $client->setCommand($this);
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

            $this->line('Starting to work...');

            $this->client->work($server);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }

    }
}
