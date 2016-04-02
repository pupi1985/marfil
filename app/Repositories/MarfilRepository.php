<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class MarfilRepository
{
    /**
     * Save a new crack request into the database.
     *
     * @param $bssid
     *
     * @return int Id of the inserted record
     *
     * @throws \Illuminate\Database\QueryException If there is a duplicated bssid
     */
    public function saveCrackRequest($bssid)
    {
        $id = DB::table('crack_requests')->insertGetId([
            'bssid' => $bssid,
            'created_at' => time(),
        ]);

        return $id;
    }

    /**
     * Save a new dictionary into the database.
     *
     * @param $name Name of the dictionary
     * @param $hash SHA1 hash of the dictionary
     * @param $parts Amount of parts of the dictionary
     *
     * @return void
     */
    public function saveDictionary($name, $hash, $parts)
    {
        DB::table('dictionaries')->insert([
            'name' => $name,
            'hash' => $hash,
            'parts' => $parts,
        ]);
    }

    /**
     * Delete all dictionaries from the database.
     *
     * @return void
     */
    public function deleteAllDictionaries()
    {
        DB::table('dictionaries')->truncate();
    }

    /**
     * Save a work unit to the database.
     *
     * @param $dictionaryId Foreign key of the dictionary
     * @param $crackRequestId Foreign key of the crack request
     * @param $part Part number of the dictionary
     * @param $assigned_at Last assignment time
     *
     * @return void
     */
    public function saveWorkUnit($dictionaryId, $crackRequestId, $part, $assigned_at)
    {
        DB::table('work_units')->insert([
            'part' => $part,
            'assigned_at' => $assigned_at,
            'crack_request_id' => $crackRequestId,
            'dictionary_id' => $dictionaryId,
        ]);
    }

    /**
     * Get all dictionaries from the detabase.
     *
     * @return array
     */
    public function getAllDictionaries()
    {
        return DB::table('dictionaries')->get();
    }
}
