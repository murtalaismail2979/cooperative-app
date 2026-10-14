<x-guest-layout>
    <div class="mb-4 text-center">
        <h2 class="text-2xl font-bold text-indigo-400">Register New Cooperative Organization</h2>
        <p class="text-sm text-gray-400 mt-1">Setup your cooperative workspace and administrative account</p>
    </div>

    <form method="POST" action="{{ route('register.cooperative.store') }}">
        @csrf

        <div class="register-grid">
            <!-- Left Column: Cooperative Info -->
            <div class="register-col">
                <h3 class="border-b border-gray-700 pb-2 mb-3 text-indigo-400 font-semibold text-base">1. Cooperative Details</h3>
                
                <!-- Cooperative Name -->
                <div>
                    <x-input-label for="cooperative_name" :value="__('Cooperative Full Name *')" />
                    <x-text-input id="cooperative_name" class="block mt-1 w-full" type="text" name="cooperative_name" :value="old('cooperative_name')" required autofocus placeholder="e.g. Al-Barakah Multipurpose Cooperative" />
                    <x-input-error :messages="$errors->get('cooperative_name')" class="mt-2" />
                </div>

                <!-- Cooperative Code / Slug -->
                <div class="mt-3">
                    <x-input-label for="cooperative_slug" :value="__('Cooperative Short Code / Prefix')" />
                    <x-text-input id="cooperative_slug" class="block mt-1 w-full" type="text" name="cooperative_slug" :value="old('cooperative_slug')" placeholder="e.g. AL-BARAKAH (Used for Member Code format)" />
                    <x-input-error :messages="$errors->get('cooperative_slug')" class="mt-2" />
                </div>

                <!-- Cooperative Email & Phone -->
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="cooperative_email" :value="__('Cooperative Email')" />
                        <x-text-input id="cooperative_email" class="block mt-1 w-full" type="email" name="cooperative_email" :value="old('cooperative_email')" placeholder="info@coop.org" />
                    </div>
                    <div>
                        <x-input-label for="cooperative_phone" :value="__('Phone Number')" />
                        <x-text-input id="cooperative_phone" class="block mt-1 w-full" type="text" name="cooperative_phone" :value="old('cooperative_phone')" placeholder="+234 800 000 0000" />
                    </div>
                </div>

                <!-- Base Slot Amount & Dividend Allocation -->
                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <x-input-label for="base_slot_amount" :value="__('Base Monthly Slot Fee (₦) *')" />
                        <x-text-input id="base_slot_amount" 
                                      class="block mt-1 w-full font-bold" 
                                      style="background-color: #ffffff !important; color: #000000 !important; font-weight: 700 !important;" 
                                      type="number" 
                                      step="0.01" 
                                      name="base_slot_amount" 
                                      :value="old('base_slot_amount', '2000.00')" 
                                      required />
                    </div>
                    <div>
                        <x-input-label for="dividend_allocation_basis" :value="__('Dividend Allocation *')" />
                        <select id="dividend_allocation_basis" name="dividend_allocation_basis" class="block mt-1 w-full border-gray-700 bg-gray-900 text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="savings_only" {{ old('dividend_allocation_basis') == 'savings_only' ? 'selected' : '' }}>Savings Only</option>
                            <option value="shares_only" {{ old('dividend_allocation_basis') == 'shares_only' ? 'selected' : '' }}>Shares Only</option>
                            <option value="hybrid" {{ old('dividend_allocation_basis') == 'hybrid' ? 'selected' : '' }}>Hybrid (Savings & Shares)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Right Column: Admin Account -->
            <div class="register-col">
                <h3 class="border-b border-gray-700 pb-2 mb-3 text-indigo-400 font-semibold text-base">2. Cooperative Admin Account</h3>

                <!-- Admin Name -->
                <div>
                    <x-input-label for="name" :value="__('Administrator Full Name *')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Admin Email -->
                <div class="mt-3">
                    <x-input-label for="email" :value="__('Administrator Email *')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required placeholder="admin@coop.org" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Admin Phone -->
                <div class="mt-3">
                    <x-input-label for="phone" :value="__('Administrator Phone Number')" />
                    <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
                </div>

                <!-- Password -->
                <div class="mt-3">
                    <x-input-label for="password" :value="__('Password *')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div class="mt-3">
                    <x-input-label for="password_confirmation" :value="__('Confirm Password *')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>

            <!-- Footer Section -->
            <div class="register-footer mt-6 flex justify-between items-center w-full">
                <a class="underline text-sm text-gray-400 hover:text-gray-200" href="{{ route('login') }}">
                    {{ __('Already have an account? Log in') }}
                </a>

                <x-primary-button class="ms-4 bg-indigo-600 hover:bg-indigo-500">
                    {{ __('Register Cooperative Workspace') }}
                </x-primary-button>
            </div>
        </div>
    </form>
</x-guest-layout>
