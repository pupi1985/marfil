<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        $pattern = $temp ? '/%s.temp.cap' : '/%s.cap';

        return sprintf($this->getCapFilesPath() . $pattern, $id);
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
