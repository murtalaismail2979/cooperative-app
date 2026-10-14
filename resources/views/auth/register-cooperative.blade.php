<x-guest-layout>
    <div class="mb-6 text-center">
        <h2 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Register New Cooperative Organization</h2>
        <p class="text-sm text-slate-300 mt-1 font-medium">Setup your cooperative workspace and administrative account</p>
    </div>

    <form method="POST" action="{{ route('register.cooperative.store') }}">
        @csrf

        <div class="register-grid">
            <!-- Left Column: Cooperative Info -->
            <div class="register-col">
                <h3 class="border-b border-slate-700 pb-2.5 mb-2 text-indigo-300 font-bold text-base flex items-center gap-2">
                    <i class="bi bi-building"></i> 1. Cooperative Details
                </h3>
                
                <!-- Cooperative Name -->
                <div>
                    <x-input-label for="cooperative_name" :value="__('Cooperative Full Name *')" />
                    <x-text-input id="cooperative_name" class="block mt-1 w-full" type="text" name="cooperative_name" :value="old('cooperative_name')" required autofocus placeholder="e.g. Al-Barakah Multipurpose Cooperative" />
                    <x-input-error :messages="$errors->get('cooperative_name')" class="mt-2" />
                </div>

                <!-- Cooperative Code / Slug -->
                <div>
                    <x-input-label for="cooperative_slug" :value="__('Cooperative Short Code / Prefix')" />
                    <x-text-input id="cooperative_slug" class="block mt-1 w-full" type="text" name="cooperative_slug" :value="old('cooperative_slug')" placeholder="e.g. AL-BARAKAH (Used for Member Code format)" />
                    <x-input-error :messages="$errors->get('cooperative_slug')" class="mt-2" />
                </div>

                <!-- Cooperative Email & Phone -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <x-input-label for="cooperative_email" :value="__('Cooperative Email')" />
                        <x-text-input id="cooperative_email" class="block mt-1 w-full" type="email" name="cooperative_email" :value="old('cooperative_email')" placeholder="info@coop.org" />
                        <x-input-error :messages="$errors->get('cooperative_email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="cooperative_phone" :value="__('Phone Number')" />
                        <x-text-input id="cooperative_phone" class="block mt-1 w-full" type="text" name="cooperative_phone" :value="old('cooperative_phone')" placeholder="+234 800 000 0000" />
                        <x-input-error :messages="$errors->get('cooperative_phone')" class="mt-2" />
                    </div>
                </div>

                <!-- Cooperative Address -->
                <div>
                    <x-input-label for="cooperative_address" :value="__('Registered Office Address')" />
                    <x-text-input id="cooperative_address" class="block mt-1 w-full" type="text" name="cooperative_address" :value="old('cooperative_address')" placeholder="e.g. Plot 12, Commercial District, Off Marina Road" />
                    <x-input-error :messages="$errors->get('cooperative_address')" class="mt-2" />
                </div>

                <!-- State & LGA (Dynamic Dropdowns) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <x-input-label for="state" :value="__('State')" />
                        <select id="state" name="state" class="block mt-1 w-full border-slate-600 bg-slate-800 text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">-- Select State --</option>
                            @foreach([
                                'Abia', 'Adamawa', 'Akwa Ibom', 'Anambra', 'Bauchi', 'Bayelsa', 'Benue', 'Borno',
                                'Cross River', 'Delta', 'Ebonyi', 'Edo', 'Ekiti', 'Enugu', 'FCT - Abuja', 'Gombe',
                                'Imo', 'Jigawa', 'Kaduna', 'Kano', 'Katsina', 'Kebbi', 'Kogi', 'Kwara', 'Lagos',
                                'Nasarawa', 'Niger', 'Ogun', 'Ondo', 'Osun', 'Oyo', 'Plateau', 'Rivers', 'Sokoto',
                                'Taraba', 'Yobe', 'Zamfara'
                            ] as $stateOption)
                                <option value="{{ $stateOption }}" {{ old('state') == $stateOption ? 'selected' : '' }}>{{ $stateOption }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('state')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="lga" :value="__('LGA (Local Govt Area)')" />
                        <select id="lga" name="lga" class="block mt-1 w-full border-slate-600 bg-slate-800 text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="">-- Select State First --</option>
                        </select>
                        <x-input-error :messages="$errors->get('lga')" class="mt-2" />
                    </div>
                </div>

                <!-- Base Slot Amount & Dividend Allocation -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <x-input-label for="base_slot_amount" :value="__('Base Monthly Slot Fee (₦) *')" />
                        <x-text-input id="base_slot_amount" 
                                      class="block mt-1 w-full font-bold" 
                                      style="background-color: #ffffff !important; color: #000000 !important; font-weight: 800 !important; font-size: 1rem !important;" 
                                      type="number" 
                                      step="0.01" 
                                      name="base_slot_amount" 
                                      :value="old('base_slot_amount', '2000.00')" 
                                      required />
                        <x-input-error :messages="$errors->get('base_slot_amount')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="dividend_allocation_basis" :value="__('Dividend Allocation *')" />
                        <select id="dividend_allocation_basis" name="dividend_allocation_basis" class="block mt-1 w-full border-slate-600 bg-slate-800 text-white focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                            <option value="savings_only" {{ old('dividend_allocation_basis') == 'savings_only' ? 'selected' : '' }}>Savings Only</option>
                            <option value="shares_only" {{ old('dividend_allocation_basis') == 'shares_only' ? 'selected' : '' }}>Shares Only</option>
                            <option value="hybrid" {{ old('dividend_allocation_basis') == 'hybrid' ? 'selected' : '' }}>Hybrid (Savings & Shares)</option>
                        </select>
                        <x-input-error :messages="$errors->get('dividend_allocation_basis')" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Right Column: Admin Account -->
            <div class="register-col">
                <h3 class="border-b border-slate-700 pb-2.5 mb-2 text-indigo-300 font-bold text-base flex items-center gap-2">
                    <i class="bi bi-shield-lock"></i> 2. Cooperative Admin Account
                </h3>

                <!-- Admin Name -->
                <div>
                    <x-input-label for="name" :value="__('Administrator Full Name *')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Admin Email -->
                <div>
                    <x-input-label for="email" :value="__('Administrator Email *')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required placeholder="admin@coop.org" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Admin Phone -->
                <div>
                    <x-input-label for="phone" :value="__('Administrator Phone Number')" />
                    <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" placeholder="+234 800 000 0000" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password *')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password *')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                </div>
            </div>

            <!-- Footer Section -->
            <div class="register-footer flex justify-between items-center w-full">
                <a class="underline text-sm text-slate-300 hover:text-white transition font-medium" href="{{ route('login') }}">
                    {{ __('Already have an account? Log in') }}
                </a>

                <x-primary-button class="ms-4 bg-indigo-600 hover:bg-indigo-500 font-bold py-2.5 px-6 text-sm sm:text-base shadow-lg shadow-indigo-600/30">
                    {{ __('Register Cooperative Workspace') }}
                </x-primary-button>
            </div>
        </div>
    </form>

    <script src="{{ asset('js/nigeria-states-lgas.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initNigeriaStateLga('state', 'lga', @json(old('lga', '')));
        });
    </script>
</x-guest-layout>

