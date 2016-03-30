<?php

namespace App\Http\Controllers;

use App\Models\MarfilServer;
use App\Models\MessageResults;
use App\Repositories\MarfilRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Request;

class MarfilController extends Controller
{
    /**
     * Store the Marfil server.
     *
     * @var MarfilRepository
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
        $mac = $this->server->fromHumanToMachine($bssid);
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
        } catch (QueryException $e) {
            $result = [
                'result' => MessageResults::ERROR,
                'message' => 'Error saving new crack request. The bssid might be present already.' . PHP_EOL
                    . $e->getMessage(),
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
            $result = [
                'result' => MessageResults::WORK_NEEDED,
                'message' => 'Assigning new work unit.',
                'data' => [
                    'crack_request_id' => 1,
                    'mac' => '01:23:45:67:89:AB',
                    'dictionary_hash' => '16dc8ef9cad85ac333a63e7e00e8c61eac444f22',
                    'part_number' => 1,
                ],
            ];
//            $result = [
//                'result' => MessageResults::NO_WORK_NEEDED,
//                'message' => 'No work is needed at the moment.',
//            ];
        } catch (Exception $e) {
            $result = [
                'result' => MessageResults::ERROR,
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($result);
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
