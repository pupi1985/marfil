<?php

namespace App\Models;

use App\Repositories\MarfilRepository;
use Exception;
use Illuminate\Support\Facades\File;

class MarfilServer extends MarfilCommon
{
    /**
     * Store the Marfil repository.
     *
     * @var MarfilRepository
     */
    private $repo;

    /**
     * MarfilServer constructor.
     *
     * @param MarfilRepository $repo
     */
    public function __construct(MarfilRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Add a crack request to the database and saves the .wpa file.
     *
     * @param $file
     * @param $fileHash
     * @param $mac
     *
     * @return void
     *
     * @throws Exception
     */
    public function addCrackRequest($file, $fileHash, $mac)
    {
        $fileContents = File::get($file);

        if (sha1($fileContents) !== $fileHash) {
            throw new Exception('Hash received does not match file hash');
        }

        $id = $this->repo->saveCrackRequest($mac);

        // Try to save the file
        File::put($this->getCapFilepath($id), $fileContents);

        // Add work units
        $dictionaries = $this->repo->getAllDictionaries();
        foreach ($dictionaries as $dictionary) {
            for ($partNumber = 1; $partNumber <= $dictionary->parts; $partNumber++) {
                $this->repo->saveWorkUnit($dictionary->id, $id, $partNumber, null);
            }
        }
    }

    /**
     * Refresh dictionaries by regenerating all dictionary parts and adding them to the database.
     *
     * @return void
     */
    public function refreshDictionaries()
    {
        $dictionariesPath = $this->getDictionariesPath();
        File::makeDirectory($dictionariesPath, 0755, false, true);

        // Remove all directories
        $dictionaryPartsDirectories = File::directories($dictionariesPath);
        foreach ($dictionaryPartsDirectories as $dictionaryPartDirectory) {
            File::deleteDirectory($dictionaryPartDirectory);
        }
        $this->repo->deleteAllDictionaries();

        // Refresh each dictionary
        $dictionaries = File::files($dictionariesPath);
        foreach ($dictionaries as $dictionary) {
            $this->refreshDictionary(File::basename($dictionary));
        }
    }

    /**
     * Recreates the dictionary's directory
     *
     * @param $dictionary
     */
    private function refreshDictionary($dictionary)
    {
        $this->command->line('Please, wait while refreshing dictionary ' . $dictionary);

        $dictionaryPath = $this->getDictionaryPath($dictionary);

        $this->command->line('Calculating SHA1 of dictionary...');

        $hash = sha1_file($dictionaryPath);

        $dictionaryPartsPath = $this->getDictionaryPartsPath($dictionary, $hash);

        File::makeDirectory($dictionaryPartsPath, 0755, false, true);

        // Split file into pieces
        $filesCreated = $this->splitFile($dictionaryPath, $dictionaryPartsPath);

        $this->repo->saveDictionary($dictionary, $hash, $filesCreated);

        $this->command->info('Dictionary successuflly refreshed.' . "\n");
    }

    /**
     * Split a dictionary into parts.
     *
     * @param $dictionaryPath Path to dictionary file
     * @param $dictionaryPartsPath Path to dictionary's parts file
     *
     * @return string Amount of files created
     */
    private function splitFile($dictionaryPath, $dictionaryPartsPath)
    {
        $fs = new FileSplitter($dictionaryPath, $dictionaryPartsPath . '/%s.txt');
        $fs->setLinesToSplitBy($this->getPartFileLength());
        $fs->setReadBufferSize(1048576);
        $fs->setEstimatedInMemoryLines(40000);
        $fs->split();

        return $fs->getAmountOfFilesCreated();
    }

}
