<?php

namespace App\Http\Controllers;

use App\Models\MarfilServer;
use App\Models\MessageResults;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;

class MarfilController extends Controller
{
    /**
     * Store the Marfil server.
     *
     * @var MarfilServer
     */
    private $server;

    public function __construct(MarfilServer $server)
    {
        $this->server = $server;
    }

    /**
     * Process a crack request.
     *
     * The crack request is added to the database and the .cap file saved.
     *
     * @return \Illuminate\Http\JsonResponse;
     */
    public function crackRequest()
    {
        $bssid = Request::get('bssid');
        $mac = $this->server->normalizeMacAddress($bssid);
        $fileHash = Request::get('file_hash');

        try {
            // Try to get the file from the request
            if (!Request::hasFile('file')) {
                throw new Exception('File could not be uploaded');
            }

            $file = Request::file('file');

            $this->server->addCrackRequest($file, $fileHash, $mac);

            $result = [
                'result' => MessageResults::SUCCESS,
                'message' => 'File saved successfully!',
            ];
        } catch (Exception $e) {
            $result = [
                'result' => MessageResults::ERROR,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Process a work request.
     *
     * The worker is assigned a piece of the dictionary to solve.
     *
     * @return \Illuminate\Http\JsonResponse;
     */
    public function workRequest()
    {
        try {
            $workUnit = $this->server->assignWorkUnit();

            if (is_null($workUnit)) {
                $result = [
                    'result' => MessageResults::NO_WORK_NEEDED,
                    'message' => 'No work is needed at the moment.',
                ];
            } else {
                $result = [
                    'result' => MessageResults::WORK_NEEDED,
                    'message' => 'Assigning new work unit.',
                    'data' => [
                        'work_unit_id' => $workUnit->id,
                        'crack_request_id' => $workUnit->cr_id,
                        'mac' => $workUnit->bssid,
                        'dictionary_hash' => $workUnit->hash,
                        'part_number' => $workUnit->part,
                    ],
                ];
            }
        } catch (Exception $e) {
            $result = [
                'result' => MessageResults::ERROR,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Process a result request.
     *
     * @return \Illuminate\Http\JsonResponse;
     */
    public function resultRequest()
    {
        $workUnitId = Request::get('work_unit_id');
        $pass = Request::get('pass');

        $this->server->processResult($workUnitId, $pass);

        return response()->json([
            'result' => MessageResults::SUCCESS,
            'message' => 'Result has been received. You can ask for new work.',
        ]);
    }

    /**
     * Return a response to download the .cap file for the given id.
     *
     * @param int $id
     *
     * @return Response
     */
    public function downloadCapRequest($id)
    {
        $filePath = $this->server->getCapFilepath($id);

        return response()->download($filePath);
    }

    /**
     * Return a response to download the part file for the given dictionary hash and part number.
     *
     * @param string $hash Dictionary hash
     * @param int $partNumber Part number of the dictionary
     *
     * @return Response
     */
    public function downloadPartRequest($hash, $partNumber)
    {
        $filePath = $this->server->getDictionaryPartPath($hash, $partNumber);

        return response()->download($filePath);
    }

}
