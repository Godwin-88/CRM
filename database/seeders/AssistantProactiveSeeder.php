<?php

use Illuminate\Support\Facades\Redis;

return [
    'allow_empty' => true,
    'order' => [
        'assistant:proactive:{user_id}',
    ],
];
