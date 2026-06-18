class WalletAuth {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    async getProvider() {
        if ('phantom' in window) {
            const provider = window.phantom?.solana;
            if (provider?.isPhantom) return provider;
        }
        if (window.solana?.isPhantom) {
            return window.solana;
        }
        return null;
    }

    async connect(onStatus) {
        const provider = await this.getProvider();

        if (!provider) {
            onStatus('error', 'Phantom wallet not found. Please install it from phantom.app');
            window.open('https://phantom.app/', '_blank');
            return;
        }

        try {
            onStatus('loading', 'Connecting to Phantom...');
            const resp = await provider.connect();
            const walletAddress = resp.publicKey.toString();

            onStatus('loading', 'Requesting login challenge...');
            const challengeRes = await fetch('/api/wallet/challenge', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ wallet_address: walletAddress }),
            });

            const challengeData = await challengeRes.json();

            if (!challengeRes.ok) {
                onStatus('error', challengeData.error || 'Failed to get challenge.');
                return;
            }

            onStatus('loading', 'Please sign the message in Phantom...');
            const encodedMessage = new TextEncoder().encode(challengeData.nonce);
            const signed = await provider.signMessage(encodedMessage, 'utf8');
            const signatureBase64 = btoa(String.fromCharCode(...signed.signature));

            onStatus('loading', 'Verifying signature...');
            const verifyRes = await fetch('/api/wallet/verify', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    wallet_address: walletAddress,
                    signature: signatureBase64,
                }),
            });

            const verifyData = await verifyRes.json();

            if (!verifyRes.ok) {
                onStatus('error', verifyData.error || 'Verification failed.');
                return;
            }

            if (verifyData.status === 'authenticated') {
                onStatus('success', `Welcome back, ${verifyData.username}!`);
                setTimeout(() => window.location.href = '/', 1000);
                return;
            }

            if (verifyData.status === 'registration_required') {
                onStatus('needs_username', walletAddress);
                return;
            }
        } catch (err) {
            if (err.code === 4001) {
                onStatus('error', 'Connection rejected.');
            } else {
                onStatus('error', err.message || 'Something went wrong.');
            }
        }
    }

    async register(username, onStatus) {
        try {
            onStatus('loading', 'Creating your account...');
            const res = await fetch('/api/wallet/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ username }),
            });

            const data = await res.json();

            if (!res.ok) {
                onStatus('error', data.error || 'Registration failed.');
                return;
            }

            onStatus('success', `Welcome, ${data.username}!`);
            setTimeout(() => window.location.href = '/', 1000);
        } catch (err) {
            onStatus('error', err.message || 'Something went wrong.');
        }
    }
}

window.WalletAuth = WalletAuth;
