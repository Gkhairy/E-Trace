<?php

return [
    // Checkout — guarantee checkbox
    'checkout_title' => 'On-Time Guarantee',
    'checkout_desc'  => 'shipping-cost compensation if the parcel is late due to the seller/courier.',
    'eta_label'      => 'Estimated arrival',
    'terms_short'    => 'Claim applies if late by ≥ :grace days and the cause is the seller/courier (not a wrong address / force majeure). Up to :cap TLKM.',
    'demo_note'      => 'Testnet demo — sample premium/payout parameters; pool subsidized by the platform.',

    // Settlement status
    'settlement' => [
        'pending'  => 'Awaiting AI assessment',
        'released' => 'Auto-settled by AI',
        'refunded' => 'Auto-refunded by AI',
        'held'     => 'Held — under supervisor review',
    ],

    // Insurance status
    'status' => [
        'none'     => 'No guarantee',
        'active'   => 'Guarantee active',
        'paid'     => 'Guarantee claim paid',
        'rejected' => 'Claim not applicable',
    ],

    // Late cause (from AI)
    'cause' => [
        'seller_courier' => 'seller/courier',
        'buyer'          => 'buyer side',
        'force_majeure'  => 'force majeure',
        'unknown'        => 'unknown',
    ],

    'ai_badge'     => 'AI decision',
    'reason_label' => 'Reason',
    'payout_label' => 'Compensation',
    'promised'     => 'Estimated arrival',
    'view_tx'      => 'View transaction',
    'demo_sim'     => 'simulated tracking data (demo)',
];
