<?php

return [
    'nav' => [
        'catalog' => 'Browse Catalog',
        'login'   => 'Enter Store',
        'how'     => 'How it Works',
    ],

    'hero' => [
        'badge'     => 'Web3, made for real life',
        'title'     => 'Shop, borrow, and donate —',
        'title_hl'  => 'all on-chain.',
        'subtitle'  => 'A marketplace where payments are guarded by a smart-contract escrow. Funds are released to the seller only after you confirm the goods arrived, and every transaction is verifiable by anyone on the blockchain.',
        'cta'       => 'Enter Store',
        'cta2'      => 'How it Works',
        'scroll'    => 'SCROLL',
    ],

    'value' => [
        'title' => 'One app for digital transactions that are transparent, secure, and sustainable.',
    ],

    'how' => [
        'eyebrow' => 'How it Works',
        'title'   => 'Safe without having to trust anyone.',
        'subtitle'=> 'It is not the platform that holds your money — it is code. Here is how a single purchase flows:',
        'steps'   => [
            ['t' => 'Pay with TLKM',   'd' => 'The buyer pays using TLKM tokens straight from their wallet.'],
            ['t' => 'Held in escrow',  'd' => 'Funds go into the smart contract and are held — not to the seller yet.'],
            ['t' => 'Item shipped',    'd' => 'The seller ships; the delivery status is tracked.'],
            ['t' => 'Confirm receipt', 'd' => 'The buyer taps "Confirm Receipt" when the goods arrive.'],
            ['t' => 'Funds released',  'd' => 'Only then are funds passed to the seller. Failed delivery? Money back.'],
        ],
        'note'    => 'A problem (e.g. wrong item)? Open a dispute with evidence — decided by a supervisor, not a one-sided refund.',
    ],

    'features' => [
        'eyebrow'  => 'Capabilities',
        'title'    => 'Everything you need, distilled into four pillars.',
        'subtitle' => 'One app, four pillars that reinforce each other — without the overwhelm.',
        'groups' => [
            [
                't' => 'Safe Shopping',
                'intro' => 'Transact without having to trust anyone.',
                'items' => [
                    ['t' => 'Trustless escrow',   'd' => 'Funds held by a smart contract, released on confirmation.'],
                    ['t' => 'Multi-seller cart',  'd' => 'Many sellers in one cart, escrow split per item.'],
                    ['t' => 'On-chain verification','d' => 'The smart contract is the source of truth, not the browser.'],
                ],
            ],
            [
                't' => 'On-Chain Finance',
                'intro' => 'More than a means of payment.',
                'items' => [
                    ['t' => 'Wallet + PIN',         'd' => 'Sign up with email/phone, no seed phrase; pay with a PIN.'],
                    ['t' => 'TLKM token',           'd' => 'A BEP-20 means of payment on BNB Smart Chain Testnet.'],
                    ['t' => 'Paylater — Borrow & Fund','d' => 'Buy now pay later, or fund the pool and earn profit-share.'],
                ],
            ],
            [
                't' => 'Automated & Protected',
                'intro' => 'Guarded by the system, not just promises.',
                'items' => [
                    ['t' => 'AI Auto-Settlement', 'd' => 'Not shipped in 3 days → refund; not confirmed → auto-complete.'],
                    ['t' => 'On-Time Guarantee',  'd' => 'Shipping insurance when late due to seller/courier.'],
                    ['t' => 'Layered security',   'd' => 'OTP, 2FA, a PIN gate, and anti-bot protection.'],
                ],
            ],
            [
                't' => 'Transparent & Social',
                'intro' => 'Open for everyone.',
                'items' => [
                    ['t' => 'Transparency explorer', 'd' => 'See transactions & TLKM transfers live on-chain.'],
                    ['t' => 'Donations & community wallet','d' => 'Recorded on-chain, 0% fee, multisig community funds.'],
                    ['t' => 'EVA & seller reports',  'd' => 'AI assistant + automatic sales recap (Excel/PDF).'],
                ],
            ],
        ],
    ],

    'sustain' => [
        'eyebrow'  => 'Sustainable',
        'title'    => 'Built to last — economy, energy, and social impact.',
        'subtitle' => 'Not just transactions, but a blockchain infrastructure that sustains itself and its environment.',
        'items' => [
            ['t' => 'A Self-Sustaining Economy','d' => 'Escrow, a profit-sharing Paylater pool, and an insurance pool form an economic loop that supports itself.'],
            ['t' => 'Energy Efficient',        'd' => 'Runs on BNB Smart Chain (Proof-of-Stake) — a far smaller energy footprint than Proof-of-Work chains.'],
            ['t' => 'Social Impact',           'd' => 'Transparency curbs misuse, donations flow intact, and financial inclusion opens up to more people.'],
        ],
        'asset_note' => '3D asset',
    ],

    'transparency' => [
        'title' => 'Don\'t trust us — verify it yourself.',
        'desc'  => 'Every transaction has an on-chain trail. Open the Explorer to see volume, escrow status, and TLKM transfers live.',
        'cta'   => 'Open Explorer',
    ],

    'personas' => [
        'title' => 'Who is E-Trace for?',
        'items' => [
            ['t' => 'Buyers', 'd' => 'Shop with peace of mind — funds stay safe in escrow until goods truly arrive.'],
            ['t' => 'Sellers', 'd' => 'Wider reach, guaranteed payments, automatic reports, a light 1% fee.'],
            ['t' => 'Communities & Donors', 'd' => 'Raise & distribute funds transparently, recorded on-chain, with no fee.'],
        ],
    ],

    'cta' => [
        'title'      => 'Ready to try a marketplace that is genuinely transparent?',
        'desc'       => 'Sign in, connect your wallet, and experience secure on-chain transactions.',
        'button'     => 'Get Started',
        'disclaimer' => 'Runs on BNB Smart Chain Testnet with test tokens — not real money. Smart contracts are unaudited; this is a prototype for competition/education.',
    ],

    'footer' => [
        'tagline' => 'A fully transparent on-chain marketplace.',
    ],
];
