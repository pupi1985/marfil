<?php

namespace App\Models;

use Exception;
use GuzzleHttp\Client;
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
        $this->handleError($responseObject);
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
        $this->handleError($responseObject);

        if ($responseObject->result == MessageResults::WORK_NEEDED) {
            $capFileId = $responseObject->data->crack_request_id;
            $hash = $responseObject->data->dictionary_hash;
            $partNumber = $responseObject->data->part_number;
            $mac = $responseObject->data->mac;
            $partFilePath = $this->getDictionaryPartPath($hash, $partNumber);

            // Download and save .cap file
            $this->sendCapDownloadRequest($server, $capFileId, $this->getCapFilepath($capFileId));

            // Check if dictionary part file is present and download it if not
            if (!File::exists($partFilePath)) {
                File::makeDirectory($this->getDictionaryPartsPath($hash), 0755, false, true);
                $this->sendPartFileDownloadRequest($server, $hash, $partNumber, $partFilePath);
            }
        }

        $this->command->info($responseObject->message);
    }

    /**
     * Send a .cap file download request to the server.
     *
     * @param string $server Server to send the request to (only hostname and port)
     * @param int $crackRequestId Crack request id which also matches the .cap file id
     * @param string $capFilePath Path to the .cap file to store the result into
     *
     * @return void
     */
    private function sendCapDownloadRequest($server, $crackRequestId, $capFilePath)
    {
        $this->sendFileDownloadRequest(
            sprintf('http://%s/download-cap/%s', $server, $crackRequestId),
            $capFilePath
        );
    }

    /**
     * Send a part file download request to the server.
     *
     * @param string $server Server to send the request to (only hostname and port)
     * @param int $partNumber Part number of the dictionary file
     * @param string $partFilePath Path to the part file to store the result into
     *
     * @return void
     *
     */
    private function sendPartFileDownloadRequest($server, $hash, $partNumber, $partFilePath)
    {
        $this->sendFileDownloadRequest(
            sprintf('http://%s/download-part/%s/%s', $server, $hash, $partNumber),
            $partFilePath
        );
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

    /**
     * Throws an exception if an error is returned in the response.
     *
     * @param \stdClass $responseObject
     *
     * @throws Exception
     */
    private function handleError($responseObject)
    {
        if ($responseObject->result == MessageResults::ERROR) {
            throw new Exception($responseObject->message);
        }
    }

    /**
     * Send a file download request to the server and save it locally.
     *
     * @param string $uri URI of the file to download
     * @param string Path of the file to download the stream to
     *
     * @return void
     */
    private function sendFileDownloadRequest($uri, $path)
    {
        (new Client())->request('GET', $uri, ['sink' => $path]);

        // Seems to be needed to avoid issues with the console output
        sleep(1);
    }
}
