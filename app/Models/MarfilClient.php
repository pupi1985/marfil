<?php

namespace App\Models;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;

class MarfilClient
{
    public function crack($server, $file, $bssid)
    {
        $uploadedFile = new UploadedFile($file, File::basename($file), null, File::size($file));
        $request = Request::create(
            'http://' . $server . '/crack',
            'POST',
            ['bssid' => $bssid],
            [],
            ['file' => $uploadedFile]
        );

        return app()->dispatch($request)->getContent();
    }

}
