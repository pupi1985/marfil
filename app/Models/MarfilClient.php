<?php

namespace App\Models;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

/**
 * Class MarfilClient
 *
 * Handle all the client logic.
 *
 * @package App\Models
 */
class MarfilClient extends MarfilCommon
{
    /**
     * Send the server a crack request.
     *
     * @param string $server Server to send the request to (only hostname and port)
     * @param string $capFilePath Path for the .cap file to attach to the request
     * @param string $mac Bssid of the network to crack as a formatted string
     *
     * @throws Exception
     */
    public function crack($server, $capFilePath, $mac)
    {
        $capFilePath = $this->compactCapFile($capFilePath, $mac);

        // Prepare and send crack request
        $uploadedFile = new UploadedFile($capFilePath, File::basename($capFilePath), null, File::size($capFilePath));
        $request = Request::create(
            'http://' . $server . '/crack',
            'POST',
            [
                'bssid' => $mac,
                'file_hash' => sha1_file($capFilePath),
            ],
            [],
            ['file' => $uploadedFile]
        );

        $responseContent = app()->dispatch($request)->getContent();

        // Delete sent file
        File::delete($capFilePath);

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
            $workUnitId = $responseObject->data->work_unit_id;
            $capFileId = $responseObject->data->crack_request_id;
            $hash = $responseObject->data->dictionary_hash;
            $partNumber = $responseObject->data->part_number;
            $mac = $responseObject->data->mac;
            $capFilePath = $this->getCapFilepath($capFileId);
            $partFilePath = $this->getDictionaryPartPath($hash, $partNumber);

            // Download and save .cap file
            $this->sendCapDownloadRequest($server, $capFileId, $capFilePath);

            // Check if dictionary part file is present and download it if not
            if (!File::exists($partFilePath)) {
                File::makeDirectory($this->getDictionaryPartsPath($hash), 0755, false, true);
                $this->sendPartFileDownloadRequest($server, $hash, $partNumber, $partFilePath);
            }

            $pass = $this->startCrackProcess($partFilePath, $capFilePath, $mac);

            $responseContent = $this->sendResult($server, $workUnitId, $pass);

            $responseObject = json_decode($responseContent);
            $this->handleError($responseObject);
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
     * Crack a .cap file and return the password if found, null if not found or throw an exception otherwise.
     *
     * @param string $partFilePath Path to the dictionary part file
     * @param string $capFilePath Path to .cap file to crack
     * @param string $mac Bssid to crack
     *
     * @return string
     *
     * @throws Exception
     */
    private function startCrackProcess($partFilePath, $capFilePath, $mac)
    {
        $this->command->line(sprintf(
            'Starting to crack %s using dictionary part %s. This might take a while...',
            $mac,
            File::basename(File::dirname($partFilePath)) . '/' . File::basename($partFilePath)
        ));
        $process = new Process(sprintf('aircrack-ng -w %s -b %s -q %s', $partFilePath, $mac, $capFilePath));
        $process->run();

        $output = $process->getOutput();
        if (!$process->isSuccessful()) {
            if (Str::contains($output, 'Passphrase not in dictionary')) {
                return null;
            }
            throw new ProcessFailedException($process);
        }

        if (preg_match('/KEY FOUND! \[ (.*) \]/i', $output, $matches)) {
            return $matches[1];
        }

        throw new Exception('An unexpected result has been returned from the cracking process: ' . PHP_EOL . $output);
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

    /**
     * Removes unnecessary information from .cap file, leaving the handshake.
     *
     * @param string $capFilePath Path to the .cap file
     * @param string $mac Bssid to check if it is contained in the .cap file
     *
     * @return string
     *
     * @throws Exception
     */
    private function compactCapFile($capFilePath, $mac)
    {
        $this->command->line(sprintf('Compacting .cap file %s.', File::basename($capFilePath)));

        $outputCapFilePath = $this->getCapFilepath(0, true);

        $process = new Process(sprintf('wpaclean %s %s', $outputCapFilePath, $capFilePath));
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        if (!Str::contains($output, $mac)) {
            throw new Exception(sprintf('The .cap file might not contain a handshake for mac %s.', $mac));
        }

        return $outputCapFilePath;
    }

    /**
     * Send the result of a cracking process to the server. It can have a password or not.
     *
     * @param string $server Server to send the request to (only hostname and port)
     * @param int $workUnitId Work unit id for the server to mark as done
     * @param string $pass Password string, if found. Null, if not found
     *
     * @return array
     */
    private function sendResult($server, $workUnitId, $pass)
    {
        // Prepare and send the result request
        $request = Request::create(
            'http://' . $server . '/result',
            'POST',
            [
                'work_unit_id' => $workUnitId,
                'pass' => $pass,
            ]
        );

        return app()->dispatch($request)->getContent();
    }
}
