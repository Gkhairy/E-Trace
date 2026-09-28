# Architecture and tech stack

```mermaid
flowchart LR
    Buyer([Buyer]) -- "pay TLKM" --> ESC[PaymentGatewayV3<br/>on-chain escrow]
    Buyer -- "confirm received" --> ESC
    ESC -- "release minus 1% fee" --> Seller([Seller])
    KEEP[Settlement keeper<br/>+ DeliveryAI] -- "arbiterRelease / arbiterRefund" --> ESC
    Track[(Tracking events)] --> KEEP
    KEEP -- "shipping refund" --> Pool[(Insurance pool)]
    Pool --> Buyer
    RADAR[Disaster Radar<br/>+ AI] -- "open campaign" --> DON[DonationPool]
    Donor([Donor]) -- "donate TLKM" --> DON
    ESC -. "events" .-> IDX[Indexers] --> Explorer[[Public Explorer]]
```

### How the pieces fit

* **Laravel app (web).** Serves the site and the API, signs PIN transactions for embedded wallets, and verifies every payment against the contract before trusting it.
* **Worker.** Runs the scheduler and queue:
  * escrow indexer and TLKM transfer indexer, every minute,
  * settlement keeper, daily,
  * Disaster Radar scan, daily at 07:00,
  * email OTP and notifications through the queue.
* **Smart contracts** on BNB Smart Chain Testnet hold the money and enforce the rules. The server holds the arbiter key and the pool key; both are secrets kept in environment variables, never in the repository.

Both the web and worker services are built from **one Docker image** (nginx + php-fpm) and deployed on Railway, so they always run the same code.

### Tech stack

| Layer              | Technology                                                                   |
| ------------------ | ---------------------------------------------------------------------------- |
| Backend            | Laravel 12, PHP 8.2                                                          |
| Frontend           | Blade, Tailwind CSS, Vite, ethers.js, Chart.js                               |
| Database           | MySQL                                                                        |
| Queue              | Database or RabbitMQ                                                         |
| Blockchain         | Solidity 0.8.20, OpenZeppelin, BEP-20, BNB Smart Chain Testnet (chain ID 97) |
| Web3 on the server | web3.php, ethereum-tx, keccak, elliptic-php                                  |
| AI                 | OpenAI `gpt-4o-mini`: chatbot, DeliveryAI, Disaster Radar                    |
| Security           | Cloudflare Turnstile, google2fa, PIN gate                                    |
| Other              | RajaOngkir (shipping rates), dompdf, maatwebsite/excel, bacon-qr-code        |
| Hosting            | Docker on Railway                                                            |
