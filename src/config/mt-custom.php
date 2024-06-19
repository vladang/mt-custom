<?php

return [
    'server'     => env('MT5_SERVER', '127.0.0.1'),
    'port'       => env('MT5_PORT', 443),
    'login'      => env('MT5_LOGIN', 1),
    'password'   => env('MT5_PASSWORD', null),
    'group_usd'  => env('MT5_USER_GROUP_USD', null),
    'group_eur'  => env('MT5_USER_GROUP_EUR', null),
];
