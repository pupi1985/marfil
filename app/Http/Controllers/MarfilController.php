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
                'result' => 'success',
                'message' => 'File saved successfully!',
            ];
        } catch (Exception $e) {
            $result = [
                'result' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($result);
    }

}
