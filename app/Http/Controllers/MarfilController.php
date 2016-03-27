<?php

namespace App\Http\Controllers;

use App\Repositories\MarfilRepository;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarfilController extends Controller
{
    /**
     * Store the Marfil repository.
     *
     * @var MarfilRepository
     */
    private $repo;

    public function __construct(MarfilRepository $repo)
    {
        $this->repo = $repo;
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
        $mac = $this->fromHumanToMachine($bssid);
        $fileHash = Request::get('file_hash');

        try {
            // Try to get the file from the request
            if (!Request::hasFile('file')) {
                throw new Exception('File could not be uploaded');
            }
            $id = $this->repo->saveCrackRequest($mac);

            $file = Request::file('file');
            $fileContents = File::get($file);

            if (sha1($fileContents) !== $fileHash) {
                throw new Exception('Hash received does not match file hash');
            }

            // Try to save the file
            Storage::put($this->getFileName($id), $fileContents);

            $result = [
                'result' => 'success',
                'message' => 'File saved successfully!',
            ];
        } catch (QueryException $e) {
            $result = [
                'result' => 'error',
                'message' => 'Error saving new crack request. The bssid might be present already.',
            ];
        } catch (Exception $e) {
            $result = [
                'result' => 'error',
                'message' => $e->getMessage(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Turn a mac represented as a formatted string into an integer.
     *
     * @param string $mac Mac represented as a formatted string
     *
     * @return int
     */
    private function fromHumanToMachine($mac)
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
    private function fromMachineToHuman($mac)
    {
        $mac = Str::upper($mac);
        $mac = str_pad($mac, 12, '0', STR_PAD_LEFT);
        $mac = str_split($mac, 2);
        $mac = implode(':', $mac);

        return $mac;
    }

    /**
     * Return a valid .cap file name for the given crack request id.
     *
     * @param $id Crack request id
     *
     * @return string
     */
    private function getFileName($id)
    {
        return sprintf('%s.cap', $id);
    }

}
