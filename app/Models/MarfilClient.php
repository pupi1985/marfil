<?php

namespace App\Models;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MarfilClient extends MarfilCommon
{
    /**
     * Send the server a crack request.
     *
     * @param string $server Server to send the request to (only hostname and port)
     * @param string $file Path for the .cap file to attach to the request
     * @param string $bssid Bssid of the network to crack as a formatted string
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

        $responseContent = app()->dispatch($request)->getContent();

        $responseObject = json_decode($responseContent);
        if ($responseObject->result == 'error') {
            throw new Exception($responseObject->message);
        }
        $this->command->info($responseObject->message);
    }

    /**
     * Send the server a work request.
     *
     * @param string $server Server to send the request to (only hostname and port)
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws Exception
     */
    public function work($server)
    {
        $responseContent = $this->sendWorkRequest($server);

        $responseObject = json_decode($responseContent);
        if ($responseObject->result == 'error') {
            throw new Exception($responseObject->message);
        }

        $this->command->info($responseObject->message);
    }

    /**
     * Send a work request to the server.
     *
     * @param string $server Server to send the request to (only hostname and port)
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @throws Exception
     */
    private function sendWorkRequest($server)
    {
        // Prepare and send the work request
        $request = Request::create(
            'http://' . $server . '/work',
            'POST'
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
