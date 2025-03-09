<?php

return [
    'key' => 'secret',
    'adapter' => 'mysql',
    'host' => env('DB_HOST' , '0.0.0.0'),
    'port' => env('DB_PORT' , 3306), // optional
    'username' => env('DB_USERNAME' , 'root'),
    'password' => env('DB_PASSWORD' , 'root'),
    'db_name' => env('DB_DATABASE' , 'ody'),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_general_ci', // optional, if not set default collation for utf8mb4 is used
    'prefix'    => '',
    'default_environment' => 'local',
    'log_table_name' => 'migrations_log',

];