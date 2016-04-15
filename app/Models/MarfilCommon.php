<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class MarfilCommon
{
    /**
     * Command that called the client.
     *
     * @var \Illuminate\Console\Command
     */
    protected $command;

    /**
     * Set the Command that called the client.
     *
     * @param \Illuminate\Console\Command $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * Remove unnecessary information from .cap file, leaving the handshake.
     *
     * @param string $capFilePath Path to the .cap file
     * @param string $outputCapFilePath Path to the output .cap file
     * @param string $mac Bssid to check if it is contained in the .cap file
     *
     * @return string
     * @throws Exception If the output of the command is not the expected one
     * @throws ProcessFailedException If there is an error executing the command
     */
    public function compactCapFile($capFilePath, $outputCapFilePath, $mac)
    {
        try {
            $process = new Process(sprintf('wpaclean %s %s', $outputCapFilePath, $capFilePath));
            $process->setTimeout(0);
            $process->setIdleTimeout(0);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $output = Str::upper($process->getOutput());

            if (Str::contains($output, 'BAD FILE')) {
                throw new Exception('The file is not a valid .cap file.');
            }

            if (!Str::contains($output, $mac)) {
                throw new Exception(sprintf('The .cap file might not contain a handshake for mac %s.', $mac));
            }
        } catch (Exception $e) {
            File::delete($outputCapFilePath);
            throw $e;
        }
    }

    /**
     * Turn an incomplete mac represented as a string into a formatted mac address.
     *
     * @param string $mac Mac represented by an unformatted string
     *
     * @return string
     */
    public function normalizeMacAddress($mac)
    {
        $mac = str_replace([':', '-'], '', $mac);
        $mac = str_pad($mac, 12, '0', STR_PAD_LEFT);
        $mac = str_split($mac, 2);
        $mac = implode(':', $mac);

        return Str::upper($mac);
    }

    /**
     * Return the root storage path for the app.
     *
     * @return string
     */
    public function getAppStoragePath()
    {
        $defaultFilesystem = Storage::getDefaultDriver();

        return config('filesystems.disks.' . $defaultFilesystem . '.root');
    }

    /**
     * Return the .cap file path for a given crack request id.
     *
     * @param int $id Crack request id
     * @param bool $temp Determines if the file should have a temporary name
     *
     * @return string
     */
    public function getCapFilePath($id, $temp = false)
    {


        return sprintf($this->getCapFilesPath() . '/%s.cap', $temp ? uniqid(rand()) : $id);
    }

    /**
     * Return the .cap files path.
     *
     * @return string
     */
    public function getCapFilesPath()
    {
        return $this->getAppStoragePath() . '/caps';
    }

    /**
     * Return the dictionaries path.
     *
     * @return string
     */
    public function getDictionariesPath()
    {
        return $this->getAppStoragePath() . '/dictionaries';
    }

    /**
     * Return the dictionary path for a given dictionary.
     *
     * @param string $dictionary Name of the dictionary file
     *
     * @return string
     */
    public function getDictionaryPath($dictionary)
    {
        return $this->getDictionariesPath() . '/' . $dictionary;
    }

    /**
     * Return the dictionary parts path for a given dictionary.
     *
     * @param string $hash SHA1 hash of the dictionary
     *
     * @return string
     */
    public function getDictionaryPartsPath($hash)
    {
        return $this->getDictionariesPath() . '/' . sprintf('%s.parts', $hash);
    }

    /**
     * Return the dictionary part path for a given dictionary and part number.
     *
     * @param string $hash SHA1 hash of the dictionary
     * @param int $partNumber Part number of the dictionary
     * @param bool $compressed Determines if the path should be for the compressed file version
     *
     * @return string
     */
    public function getDictionaryPartPath($hash, $partNumber, $compressed = false)
    {
        $dictionaryPartPath = $this->getDictionaryPartsPath($hash) . '/' . $partNumber . '.txt';

        if ($compressed) {
            $dictionaryPartPath = $this->getCompressedFilePath($dictionaryPartPath);
        }

        return $dictionaryPartPath;
    }

    /**
     * Return a file path name for gzipped files
     *
     * @param string $filePath Original file name
     *
     * @return string
     */
    public function getCompressedFilePath($filePath)
    {
        if (!Str::endsWith($filePath, '.gz')) {
            $filePath .= '.gz';
        }

        return $filePath;
    }

    /**
     * Return the maximum amount of lines that each part file must have.
     *
     * @return int
     */
    public function getPartFileLength()
    {
        return 5000000;
    }

}
