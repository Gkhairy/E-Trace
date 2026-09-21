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
        'eyebrow'  => 'Full Feature Set',
        'title'    => 'Everything you need, on a single on-chain infrastructure.',
        'subtitle' => 'From shopping to credit and donations — explained in plain language.',
        'items' => [
            ['t' => 'Trustless Escrow',        'd' => 'Funds held by a smart contract, released only when the buyer confirms receipt.'],
            ['t' => 'Multi-Seller Cart',       'd' => 'One cart, many sellers; escrow is split per item.'],
            ['t' => 'TLKM Token',              'd' => 'A BEP-20 means of payment on the BNB Smart Chain Testnet.'],
            ['t' => 'Wallet + PIN',            'd' => 'Sign up with email/phone, no seed phrase — or MetaMask. Pay with just a PIN.'],
            ['t' => 'Paylater — Borrow & Fund','d' => 'Buy now pay later, or fund the liquidity pool and earn profit-share (flexible/30/90 days).'],
            ['t' => 'AI Auto-Settlement',      'd' => 'Not shipped in 3 days → auto refund; received but not confirmed → auto complete.'],
            ['t' => 'On-Time Guarantee',       'd' => 'Shipping insurance: late due to seller/courier → shipping fee refunded from a pool. ETA by distance.'],
            ['t' => 'Transparency Explorer',   'd' => 'See transactions + on-chain TLKM transfers, top stores, and verified entities.'],
            ['t' => 'Donations & Community Wallet','d' => 'Donations recorded on-chain (0% fee); community funds need multisig approval.'],
            ['t' => 'EVA — AI Assistant',      'd' => 'Helps you use the app and explains blockchain for everyday users.'],
            ['t' => 'Layered Security',        'd' => 'Email OTP, 2FA, a PIN gate, and anti-bot protection.'],
            ['t' => 'Seller Reports',          'd' => 'Automatic sales recap + COGS, export to Excel/PDF.'],
            ['t' => 'Roles & Bilingual',       'd' => 'Buyer, seller, supervisor — available in Indonesian & English.'],
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
