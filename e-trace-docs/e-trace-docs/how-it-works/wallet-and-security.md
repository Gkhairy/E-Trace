# Wallet and account security

### Embedded wallet with a PIN

Most shoppers have never used a crypto wallet. E-Trace gives every email account an **embedded wallet**:

* You sign up with your email and set a 6-digit PIN. No seed phrase, no browser extension.
* To pay, you enter your PIN. The server signs the transaction for you.
* On testnet only, the platform tops up your wallet with a little tBNB for gas before a PIN transaction, so you never have to buy gas yourself. This may not continue on mainnet.

If you'd rather keep your own keys, log in with MetaMask or any injected wallet instead.

### Account protection

* **Email OTP** on registration.
* **Optional 2FA** with an authenticator app.
* **Cloudflare Turnstile** on both password login and wallet login to block bots.
* **Rate limits** on registration and login.

### Privacy

Personal data (name, address, phone number) is stored in the application database, **never on-chain**. On-chain records hold only wallet addresses, amounts, order IDs and product IDs. This follows Indonesia's Personal Data Protection Law (UU PDP).
