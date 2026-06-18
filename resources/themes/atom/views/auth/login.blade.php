<x-app-layout>
    @push('title', __('Login'))

    <x-messages.flash-messages />

    <div class="col-span-12">
        <div class="lg:px-[250px]">
            <x-content.content-card icon="hotel-icon" classes="flex flex-col">
                <x-slot:title>
                    {{ __('Login to :hotel', ['hotel' => setting('hotel_name')]) }}
                </x-slot:title>

                <x-slot:under-title>
                    {{ __('Connect your Solana wallet to enter :hotel', ['hotel' => setting('hotel_name')]) }}
                </x-slot:under-title>

                <div x-data="walletLogin()" x-init="init()" class="flex flex-col gap-y-4">

                    <!-- Connect button -->
                    <template x-if="step === 'connect'">
                        <button
                            @click="connectWallet()"
                            class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold py-3 px-4 rounded-lg transition"
                        >
                            <span>◆ Connect Phantom Wallet</span>
                        </button>
                    </template>

                    <!-- Loading state -->
                    <template x-if="step === 'loading'">
                        <div class="flex items-center justify-center gap-2 text-gray-500 dark:text-gray-400 py-3">
                            <svg class="animate-spin h-5 w-5" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                            </svg>
                            <span x-text="statusMessage"></span>
                        </div>
                    </template>

                    <!-- Username picker for new users -->
                    <template x-if="step === 'needs_username'">
                        <div class="flex flex-col gap-y-3">
                            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-300 dark:border-yellow-700 rounded-lg p-3 text-sm text-yellow-800 dark:text-yellow-300">
                                {{ __('This wallet is new! Choose a username carefully — it cannot be changed later.') }}
                            </div>

                            <div>
                                <x-form.label for="username">
                                    {{ __('Choose your username') }}
                                </x-form.label>
                                <input
                                    type="text"
                                    x-model="username"
                                    placeholder="{{ __('Username') }}"
                                    maxlength="25"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                />
                            </div>

                            <button
                                @click="registerUsername()"
                                class="w-full bg-green-600 hover:bg-green-500 text-white font-semibold py-3 px-4 rounded-lg transition"
                            >
                                {{ __('Confirm & Create Account') }}
                            </button>
                        </div>
                    </template>

                    <!-- Success state -->
                    <template x-if="step === 'success'">
                        <div class="flex items-center justify-center gap-2 text-green-600 dark:text-green-400 py-3 font-semibold">
                            <span x-text="statusMessage"></span>
                        </div>
                    </template>

                    <!-- Error message -->
                    <template x-if="errorMessage">
                        <div class="bg-red-50 dark:bg-red-900/20 border border-red-300 dark:border-red-700 rounded-lg p-3 text-sm text-red-700 dark:text-red-400">
                            <span x-text="errorMessage"></span>
                        </div>
                    </template>

                    <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-2">
                        {{ __('Don\'t have Phantom?') }}
                        <a href="https://phantom.app/" target="_blank" class="underline hover:text-purple-500">
                            {{ __('Install it here') }}
                        </a>
                    </div>
                </div>
            </x-content.content-card>
        </div>
    </div>

    @push('scripts')
        @vite('resources/themes/atom/js/wallet/wallet-auth.js')
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
</x-app-layout>