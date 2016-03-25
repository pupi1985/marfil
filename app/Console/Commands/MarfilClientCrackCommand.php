<?php

namespace App\Console\Commands;

use App\Models\MarfilClient;
use Illuminate\Console\Command;

class MarfilClientCrackCommand extends Command
{
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
     * @param MarfilClient $client
     */
    public function __construct(MarfilClient $client)
    {
        parent::__construct();

        $this->client = $client;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $server = $this->argument('server');
        $file = $this->argument('file');
        $bssid = $this->argument('bssid');

        $this->line('Sending crack request...');

        try {
            $response = $this->client->crack($server, $file, $bssid);
            $responseObject = json_decode($response);
            if ($responseObject->result == 'success') {
                $this->info($responseObject->message);
            } else {
                $this->error($responseObject->message);
            }
        } catch (Exception $e) {
            $this->error('There has been an error while executing the request.');
        }

    }
}
