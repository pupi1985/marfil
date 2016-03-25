<?php

namespace App\Models;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MarfilClient
{
    /**
     * Command that called the client.
     *
     * @var \Illuminate\Console\Command
     */
    private $command;

    /**
     * Set the Command that called the client.
     *
     * @param \Illuminate\Console\Command $command
     */
    public function setCommand($command)
    {
        $this->comand = $command;
    }

    /**
     * Send the server a crack request.
     *
     * @param string $server Server to send the request to (only hostname and port)
     * @param string $file Path for the .cap file to attach to the request
     * @param string $bssid Bssid of the network to crack as a formatted string
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws Exception
     */
    public function crack($server, $file, $bssid)
    {
        // Prepare and send crack request
        $uploadedFile = new UploadedFile($file, File::basename($file), null, File::size($file));
        $request = Request::create(
            'http://' . $server . '/crack',
            'POST',
            [
                'bssid' => $bssid,
                'file_hash' => sha1_file($file),
            ],
            [],
            ['file' => $uploadedFile]
        );

        return app()->dispatch($request)->getContent();
    }

    /**
     * Return the cracking speed using aircrack-ng command
     *
     * @return int
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     * @throws Exception
     */
    private function getSpeed()
    {
        $process = new Process('aircrack-ng -S');
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $speed = (int)$process->getOutput();
        if ($speed < 1) {
            throw new Exception('There has been an error while getting the cracking speed.');
        }

        $this->command->line(sprintf('Cracking speed is %s k/s (keys per second)', $speed));

        return $speed;
    }

}
