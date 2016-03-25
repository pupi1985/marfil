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
}
