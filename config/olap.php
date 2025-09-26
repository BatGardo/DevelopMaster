<?php

return [
    'connection' => 'olap',
    'queue' => env('OLAP_QUEUE', 'olap-etl'),
    'batch_size' => (int) env('OLAP_ETL_BATCH_SIZE', 500),
    'run_until_empty' => filter_var(env('OLAP_ETL_RUN_UNTIL_EMPTY', false), FILTER_VALIDATE_BOOL),
    'schedule_time' => env('OLAP_ETL_SCHEDULE', '02:00'),
];
