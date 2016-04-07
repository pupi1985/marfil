<?php

namespace App\Models;

use App\Repositories\MarfilRepository;
use Exception;
use Illuminate\Database\QueryException;
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
        File::put($this->getCapFilePath($id), $fileContents);

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
     * Return an avaiable work unit and set it as assigned.
     *
     * @return array
     */
    public function assignWorkUnit()
    {
        $workUnit = $this->repo->getOldestWorkUnit();

        if (is_null($workUnit)) {
            return null;
        }

        $this->repo->assignWorkUnit($workUnit->id);

        return $workUnit;
    }

    /**
     * Process a result and reflect it into the database.
     *
     * @param int $workUnitId Work unit id for the server to mark as done
     * @param string $pass Password string, if found. Null, if not found
     */
    public function processResult($workUnitId, $pass)
    {
        $workUnit = $this->repo->getWorkUnit($workUnitId);

        // The work unit has already been processed
        if (is_null($workUnit)) {
            return;
        }

        if (is_null($pass)) {
            $this->repo->deleteWorkUnit($workUnitId);

            $workFinished = !$this->repo->crackRequestHasWorkUnits($workUnit->crack_request_id);

            if ($workFinished) {
                $this->repo->updateCrackRequest($workUnit->crack_request_id, ['finished' => true,]);
            }

            info(sprintf('Work unit id %s has been processed.', $workUnitId));
        } else {
            $crackRequest = $this->repo->getCrackRequest($workUnit->crack_request_id);

            $this->repo->deleteAllWorkUnitsForCrackRequestId($crackRequest->id);
            $this->repo->updateCrackRequest($crackRequest->id, [
                'password' => $pass,
                'finished' => true,
            ]);

            info(sprintf('Password found for bssid %s: [%s]', $crackRequest->bssid, $pass));
        }
    }

    /**
     * Recreates the dictionary's directory
     *
     * @param $dictionary
     *
     * @return void
     *
     * @throws QueryException If dictionary hash is duplicated
     */
    private function refreshDictionary($dictionary)
    {
        $this->command->line('Please, wait while refreshing dictionary ' . $dictionary);

        $dictionaryPath = $this->getDictionaryPath($dictionary);

        $this->command->line('Calculating SHA1 of dictionary...');

        $hash = sha1_file($dictionaryPath);

        $dictionaryPartsPath = $this->getDictionaryPartsPath($hash);

        File::makeDirectory($dictionaryPartsPath, 0755, false, true);

        $this->command->line('Splitting dictionary into parts...');

        $filesCreated = $this->splitFile($dictionaryPath, $dictionaryPartsPath);

        try {
            $this->repo->saveDictionary($dictionary, $hash, $filesCreated);
        } catch (QueryException $e) {
            $this->command->error(
                'Error while adding dictionary to the database. There might be dictionaries with same content.'
            );
            throw $e;
        }

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
