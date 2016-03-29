<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

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
     * Turn a mac represented as a formatted string into an integer.
     *
     * @param string $mac Mac represented as a formatted string
     *
     * @return int
     */
    public function fromHumanToMachine($mac)
    {
        $mac = str_replace([':', '-'], '', $mac);

        return (int)base_convert($mac, 16, 10);
    }

    /**
     * Turn a mac represented as an integer into a formatted string.
     *
     * @param int $mac Mac represented by an integer
     *
     * @return string
     */
    public function fromMachineToHuman($mac)
    {
        $mac = Str::upper($mac);
        $mac = str_pad($mac, 12, '0', STR_PAD_LEFT);
        $mac = str_split($mac, 2);
        $mac = implode(':', $mac);

        return $mac;
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
     * @param $id Crack request id
     *
     * @return string
     */
    public function getCapFilepath($id)
    {
        return sprintf($this->getCapFilesPath() . '/%s.cap', $id);
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
     * @param $dictionary Name of the dictionary file
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
     * @param $dictionary Name of the dictionary file
     *
     * @return string
     */
    public function getDictionaryPartsPath($dictionary)
    {
        return $this->getDictionaryPath($dictionary) . '.parts';
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
