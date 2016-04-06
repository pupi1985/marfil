<?php

namespace App\Repositories;

use Carbon\Carbon;
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
            'password' => null,
            'created_at' => Carbon::createFromTimestamp(time()),
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

    /**
     * Return a single work unit ordered by the oldest crack request and assigned part.
     *
     * If a work unit is present in the database then it has not yet finished and represents work left to do.
     *
     * @return array
     */
    public function getOldestWorkUnit()
    {
        return DB::table('work_units as wu')
            ->join('crack_requests as cr', 'wu.crack_request_id', '=', 'cr.id')
            ->join('dictionaries as d', 'wu.dictionary_id', '=', 'd.id')
            ->orderBy('cr.created_at')
            ->orderBy('wu.assigned_at')
            ->orderBy('wu.part')
            ->take(1)
            ->first(['wu.id', 'wu.part', 'cr.id as cr_id', 'cr.bssid', 'd.hash']);
    }

    /**
     * Set the the work united as assigned now.
     *
     * @param int $id Work unit id
     *
     * @return void
     */
    public function assignWorkUnit($id)
    {
        DB::table('work_units')
            ->where('id', $id)
            ->update(['assigned_at' => Carbon::createFromTimestamp(time())]);
    }

    /**
     * Delete the given work unit.
     *
     * @param int $workUnitId Work unit id to delete
     *
     * @return void
     */
    public function deleteWorkUnit($workUnitId)
    {
        DB::table('work_units')->delete($workUnitId);
    }

    /**
     * Return a work unit row from its id.
     *
     * @param int $workUnitId Work unit id to look for
     *
     * @return stdClass
     */
    public function getWorkUnit($workUnitId)
    {
        return DB::table('work_units')->find($workUnitId);
    }

    /**
     * Return a crack request row from its id.
     *
     * @param int $crackRequestId Crack request id
     *
     * @return stdClass
     *
     */
    public function getCrackRequest($crackRequestId)
    {
        return DB::table('crack_requests')->find($crackRequestId);
    }

    /**
     * Delete all work units for the given crack request id.
     *
     * @param int $crackRequestId Crack request id
     *
     * @return void
     */
    public function deleteAllWorkUnitsForCrackRequestId($crackRequestId)
    {
        DB::table('work_units')
            ->where('crack_request_id', $crackRequestId)
            ->delete();
    }

    /**
     * Update the crack request that corresponds to the given crack request id.
     *
     * @param int $id Id of the crack request to update
     * @param array $fields Fields and values to update
     *
     * @result void
     */
    public function updateCrackRequest($id, $fields)
    {
        DB::table('crack_requests')
            ->where('id', $id)
            ->update($fields);
    }
}
