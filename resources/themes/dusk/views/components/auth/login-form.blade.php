<x-slot name="title">
    <h2 class="text-2xl font-semibold">{{ __('Hello!') }}</h2>
    <p class="dark:text-gray-400">
        {{ __('There is currently :online users online', ['online' => DB::table('users')->where('online', '1')->count()]) }}
    </p>
</x-slot>

<div x-data="walletLogin()" x-init="init()" class="flex flex-col gap-y-3">

    <!-- Connect button -->
    <template x-if="step === 'connect'">
        <button
            @click="connectWallet()"
            class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold py-2 px-4 rounded-md transition"
        >
            <span>◆ Connect Phantom Wallet</span>
        </button>
    </template>

    <!-- Loading state -->
    <template x-if="step === 'loading'">
        <div class="flex items-center justify-center gap-2 text-gray-500 dark:text-gray-400 py-2">
            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
            </svg>
            <span x-text="statusMessage"></span>
        </div>
    </template>

    <!-- Username picker -->
    <template x-if="step === 'needs_username'">
        <div class="flex flex-col gap-y-2">
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-md p-2 text-xs text-yellow-800 dark:text-yellow-300">
                {{ __('This wallet is new! Username cannot be changed later.') }}
            </div>
            <input
                type="text"
                x-model="username"
                placeholder="{{ __('Choose your username') }}"
                maxlength="25"
                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
            />
            <button
                @click="registerUsername()"
                class="w-full bg-green-600 hover:bg-green-500 text-white font-semibold py-2 px-4 rounded-md transition"
            >
                {{ __('Confirm & Create Account') }}
            </button>
        </div>
    </template>

    <!-- Success -->
    <template x-if="step === 'success'">
        <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400 py-2 font-semibold text-sm">
            <span x-text="statusMessage"></span>
        </div>
    </template>

    <!-- Error -->
    <template x-if="errorMessage">
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-md p-2 text-xs text-red-700 dark:text-red-400">
            <span x-text="errorMessage"></span>
        </div>
    </template>

    <div class="text-center text-xs text-gray-500 dark:text-gray-400">
        {{ __('Don\'t have Phantom?') }}
        <a href="https://phantom.app/" target="_blank" class="underline hover:text-purple-500">
            {{ __('Install it here') }}
        </a>
    </div>
</div>

@once
    @push('scripts')
        @vite('resources/themes/dusk/js/wallet/wallet-auth.js')
        <script>
            function walletLogin() {
                return {
                    step: 'connect',
                    statusMessage: '',
                    errorMessage: '',
                    username: '',
                    walletAddress: null,
                    auth: null,

                    init() {
                        this.auth = new WalletAuth();
                    },

                    connectWallet() {
                        this.errorMessage = '';
                        this.auth.connect((status, message) => {
                            if (status === 'loading') {
                                this.step = 'loading';
                                this.statusMessage = message;
                            } else if (status === 'error') {
                                this.step = 'connect';
                                this.errorMessage = message;
                            } else if (status === 'needs_username') {
                                this.step = 'needs_username';
                                this.walletAddress = message;
                            } else if (status === 'success') {
                                this.step = 'success';
                                this.statusMessage = message;
                            }
                        });
                    },

                    registerUsername() {
                        this.errorMessage = '';
                        if (!this.username || this.username.length < 3) {
                            this.errorMessage = 'Username must be at least 3 characters.';
                            return;
                        }
                        this.auth.register(this.username, (status, message) => {
                            if (status === 'loading') {
                                this.step = 'loading';
                                this.statusMessage = message;
                            } else if (status === 'error') {
                                this.step = 'needs_username';
                                this.errorMessage = message;
                            } else if (status === 'success') {
                                this.step = 'success';
                                this.statusMessage = message;
                            }
                        });
                    },
                }
            }
        </script>
    @endpush
@endonce