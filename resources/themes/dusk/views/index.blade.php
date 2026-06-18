<x-app-layout>
    @push('title', __('Welcome to the best hotel on the web!'))


    <div class="col-span-12 md:col-span-6 min-h-[250px] bg-gray-900/50 rounded-xl flex flex-col py-6 px-8 text-white">
        <h2 class="text-2xl">Login</h2>

        <div x-data="walletLogin()" x-init="init()" class="flex flex-col gap-y-3 mt-4">

            <!-- Connect button -->
            <template x-if="step === 'connect'">
                <button
                    @click="connectWallet()"
                    class="w-full flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold py-3 px-4 rounded-md transition duration-300 ease-in-out hover:scale-[102%]"
                >
                    <span>◆ Connect Phantom Wallet</span>
                </button>
            </template>

            <!-- Loading state -->
            <template x-if="step === 'loading'">
                <div class="flex items-center justify-center gap-2 text-gray-300 py-3">
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
                    <div class="bg-yellow-500/20 border border-yellow-400 rounded-md p-3 text-sm text-yellow-200">
                        {{ __('This wallet is new! Choose a username carefully — it cannot be changed later.') }}
                    </div>

                    <input
                        type="text"
                        x-model="username"
                        placeholder="{{ __('Choose your username') }}"
                        maxlength="25"
                        class="py-2 px-3 rounded-md w-full text-black"
                    />

                    <button
                        @click="registerUsername()"
                        class="w-full bg-green-600 hover:bg-green-500 text-white font-semibold py-2 px-4 rounded-md transition duration-300 ease-in-out hover:scale-[102%]"
                    >
                        {{ __('Confirm & Create Account') }}
                    </button>
                </div>
            </template>

            <!-- Success state -->
            <template x-if="step === 'success'">
                <div class="flex items-center justify-center gap-2 text-green-400 py-3 font-semibold">
                    <span x-text="statusMessage"></span>
                </div>
            </template>

            <!-- Error message -->
            <template x-if="errorMessage">
                <div class="bg-red-500/20 border border-red-400 rounded-md p-3 text-sm text-red-300">
                    <span x-text="errorMessage"></span>
                </div>
            </template>

            <div class="text-center text-sm text-gray-400 mt-1">
                {{ __('Don\'t have Phantom?') }}
                <a href="https://phantom.app/" target="_blank" class="underline hover:text-purple-300">
                    {{ __('Install it here') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Articles --}}
    <div class="col-span-12 md:col-span-6 h-[250px]">
        <!-- Slider main container -->
        <div class="swiper h-[250px] rounded-md">

            <!-- If we need pagination -->
            <div class="swiper-pagination"></div>

            <!-- If we need navigation buttons -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>

            <!-- Additional required wrapper -->
            <div class="swiper-wrapper" style="z-index: 14;">
                @foreach($articles as $article)
                    <x-article-card :article="$article" />
                @endforeach
            </div>
        </div>

    </div>

    <div class="col-span-12 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($photos as $photo)
            <a href="{{ $photo->url }}" data-fancybox="gallery" class="cursor-pointer relative transition duration-300 ease-in-out hover:scale-[102%]">
                <div class="photo-overlay"></div>
                <img class="h-[250px] w-full object-cover object-center rounded-md custom-shadow" src="{{ $photo->url }}" alt="">

                <div class="absolute right-2 bottom-2 bg-black/70 p-2 rounded-md text-white flex gap-x-2 z-[5]">
                    <img class="self-center" src="{{ asset('/assets/images/dusk/author_camera_icon.png') }}" alt="">
                    <small>
                        {{ $photo->user->username }}
                    </small>
                </div>
            </a>
        @endforeach
    </div>

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
                        // Intentionally left blank — auth is lazy-loaded on first use
                    },

                    getAuth() {
                        if (!this.auth) {
                            this.auth = new WalletAuth();
                        }
                        return this.auth;
                    },

                    connectWallet() {
                        this.errorMessage = '';
                        this.getAuth().connect((status, message) => {
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
                        this.getAuth().register(this.username, (status, message) => {
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