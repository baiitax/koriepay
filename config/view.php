<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | Do NOT wrap the fallback in realpath(): it returns false when the
    | directory is missing (empty dirs are not deployed) and Blade then
    | throws "Please provide a valid cache path."
    |
    */

    'compiled' => env('VIEW_COMPILED_PATH')
        ?: (is_dir(storage_path('framework/views'))
            ? storage_path('framework/views')
            : sys_get_temp_dir()),

];
