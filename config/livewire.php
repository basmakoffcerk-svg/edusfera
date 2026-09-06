<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Livewire handles file uploads by storing them in a temporary directory
    | until the form is submitted. Here you can configure the disk and
    | directory used for storing these temporary files.
    |
    */

    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => null,
        'directory' => 'livewire-tmp',
        'middleware' => null,
        'preview_mimes' => [
            'png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a',
            'jpg', 'jpeg', 'mpga', 'webp', 'wma', 'ttf', 'otf', 'woff', 'woff2',
        ],
        'max_upload_time' => 5,
    ],

];
