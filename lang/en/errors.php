<?php

// Custom error pages (resources/views/errors). Copy names the problem and the
// way back, in the product's own language — not technical codes.
return [
    'home'   => 'Go home',
    'back'   => 'Go back',
    'retry'  => 'Try again',
    'search' => 'Search',
    'search_placeholder' => 'Search products or stores…',
    'trace'  => 'Request trace',
    'explorer' => 'Open Explorer',

    '404' => [
        'title' => 'This page doesn’t exist',
        'lead'  => 'The link may be mistyped, or the seller removed the product. Try searching below.',
    ],
    '403' => [
        'title' => 'You don’t have access here',
        'lead'  => 'This page is limited to accounts with a specific role. If that seems wrong, sign in with the right account.',
    ],
    '419' => [
        'title'  => 'Your session expired',
        'lead'   => 'The page was open too long, so the form can’t be submitted again for security. Reopen the page and fill it in again.',
        'reopen' => 'Reopen page',
    ],
    '429' => [
        'title' => 'Too many requests',
        'lead'  => 'You’re sending requests faster than allowed. Wait a moment, then try again.',
        'wait'  => 'You can try again in :seconds seconds.',
        'ready' => 'You can try again now.',
    ],
    '500' => [
        'title'   => 'Something broke on our side',
        'lead'    => 'Your request couldn’t be processed because of a server error. It’s not your fault.',
        'payment' => 'In the middle of paying? Don’t pay again yet. If the transaction went out, it’s recorded on the blockchain even though this page failed — check Orders or the Explorer first.',
        'orders'  => 'Check orders',
    ],
    '503' => [
        'title'  => 'E-Trace is under maintenance',
        'lead'   => 'We’re updating the system and will be back shortly.',
        'escrow' => 'Funds held in escrow are safe: they live in the smart contract, not on this server.',
    ],
    '4xx' => [
        'title' => 'This request couldn’t be processed',
        'lead'  => 'Something is off with this request. Go back to the previous page and try again.',
    ],
    '5xx' => [
        'title' => 'The server is having trouble',
        'lead'  => 'Try again in a few moments.',
    ],
];
