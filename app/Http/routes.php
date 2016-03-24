<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;

$app->post('/crack', function () use ($app) {
    try {
        $file = Request::file('file');
        if (!$file->isValid()) {
            throw new Exception();
        }
        Storage::put($file->getClientOriginalName(), File::get($file));
        $result = [
            'result' => 'success',
            'message' => 'File saved successfully',
        ];
    } catch (Exception $e) {
        $result = [
            'result' => 'error',
            'message' => 'File was not properly received',
        ];
    }

    return response()->json($result);
});
