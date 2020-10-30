<?php

return [

    'tables' => [
        'main' => 's_files',
    ],

    'models' => [
        'main' => 'san4o101\Models\SFile'
    ],

    'allow_mime_types' => [
        'images' => ['png', 'jpg', 'jpeg', 'svg', 'bmp', 'gif', 'svg+xml', 'tiff', 'webp'],
        'videos' => ['avi', 'mp4', 'mpeg', 'ogg', 'webm', '3gpp', '3gpp2'],
    ],

    'random_file_name_length' => 16,

];