<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        @php
            $selectedCoopId = old('cooperative_id', request('cooperative_id'));
            if (!$selectedCoopId && request('coop')) {
                $selectedCoopId = \App\Models\Cooperative::where('slug', strtoupper(request('coop')))->value('id');
            }
        @endphp

        <div class="register-grid">
            <!-- Left Column: Member Details -->
            <div class="register-col">
                <h3 class="border-b border-gray-700 pb-2 mb-2 text-primary font-semibold text-lg">{{ __('Member Details') }}</h3>
                
                <!-- Cooperative Selection -->
                <div>
                    <x-input-label for="cooperative_id" :value="__('Select Cooperative *')" />
                    <select id="cooperative_id" name="cooperative_id" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="" disabled {{ $selectedCoopId ? '' : 'selected' }}>Select Cooperative</option>
                        @foreach(\App\Models\Cooperative::active()->get() as $cooperative)
                            <option value="{{ $cooperative->id }}" 
                                    data-logo="{{ $cooperative->logo_url ?? '' }}" 
                                    data-name="{{ $cooperative->name }}" 
                                    data-slug="{{ $cooperative->slug }}"
                                    {{ (string)$selectedCoopId === (string)$cooperative->id ? 'selected' : '' }}>
                                {{ $cooperative->name }} ({{ $cooperative->slug }})
                            </option>
                        @endforeach
                    </select>
                    
                    <!-- Selected Cooperative Logo & Identity Card -->
                    <div id="selected_coop_card" class="mt-2.5 p-3 rounded-xl border border-indigo-500/30 bg-slate-900/80 shadow-lg flex items-center gap-3 transition-all duration-200 hidden">
                        <div id="selected_coop_logo_wrapper" class="w-12 h-12 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center overflow-hidden flex-shrink-0 shadow">
                            <img id="selected_coop_logo_img" src="" alt="Cooperative Logo" class="w-full h-full object-contain hidden">
                            <div id="selected_coop_logo_fallback" class="w-full h-full flex items-center justify-center font-bold text-base text-white bg-indigo-600/40">
                                <i class="bi bi-building"></i>
                            </div>
                        </div>
                        <div class="flex-grow min-w-0">
                            <div class="flex items-center gap-2">
                                <h5 id="selected_coop_name_txt" class="text-white font-bold text-sm truncate mb-0"></h5>
                                <span id="selected_coop_slug_txt" class="text-xs px-2 py-0.5 rounded font-mono font-semibold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30"></span>
                            </div>
                            <p class="text-xs text-emerald-400 mt-0.5 mb-0 flex items-center gap-1 font-medium">
                                <i class="bi bi-patch-check-fill text-emerald-400"></i> Official Member Registration
                            </p>
                        </div>
                    </div>

                    <x-input-error :messages="$errors->get('cooperative_id')" class="mt-2" />
                </div>

                <!-- Name -->
                <div>
                    <x-input-label for="name" :value="__('Name')" />
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Email Address -->
                <div>
                    <x-input-label for="email" :value="__('Email')" />
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label for="password" :value="__('Password')" />
                    <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirm Password -->
                <div>
                    <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <!-- Phone (User) -->
                <div>
                    <x-input-label for="phone" :value="__('Phone Number')" />
                    <x-text-input id="phone" class="block mt-1 w-full" type="tel" inputmode="tel" pattern="[\+]?[0-9\s\-\(\)]{7,20}" maxlength="20" name="phone" :value="old('phone')" placeholder="e.g. 08012345678 or +234 800 000 0000" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Address (User) -->
                <div>
                    <x-input-label for="address" :value="__('Contact Address')" />
                    <textarea id="address" class="block mt-1 w-full" name="address" rows="2">{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
            </div>

            <!-- Right Column: Next of Kin Details -->
            <div class="register-col">
                <h3 class="border-b border-gray-700 pb-2 mb-2 text-primary font-semibold text-lg">{{ __('Next of Kin Details') }}</h3>

                <!-- Next of Kin Name -->
                <div>
                    <x-input-label for="nok_name" :value="__('Next of Kin Full Name *')" />
                    <x-text-input id="nok_name" class="block mt-1 w-full" type="text" name="nok_name" :value="old('nok_name')" required />
                    <x-input-error :messages="$errors->get('nok_name')" class="mt-2" />
                </div>

                <!-- Next of Kin Relationship -->
                <div>
                    <x-input-label for="nok_relationship" :value="__('Relationship to Member *')" />
                    <select id="nok_relationship" name="nok_relationship" required>
                        <option value="" disabled {{ old('nok_relationship') ? '' : 'selected' }}>Select Relationship</option>
                        @foreach(['Son', 'Daughter', 'Father', 'Mother', 'Sister', 'Brother', 'Spouse', 'Sibling', 'Aunty', 'Uncle'] as $rel)
                            <option value="{{ $rel }}" {{ old('nok_relationship') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('nok_relationship')" class="mt-2" />
                </div>

                <!-- Next of Kin Phone -->
                <div>
                    <x-input-label for="nok_phone" :value="__('Next of Kin Phone Number *')" />
                    <x-text-input id="nok_phone" class="block mt-1 w-full" type="tel" inputmode="tel" pattern="[\+]?[0-9\s\-\(\)]{7,20}" maxlength="20" name="nok_phone" :value="old('nok_phone')" required placeholder="e.g. 08012345678 or +234 800 000 0000" />
                    <x-input-error :messages="$errors->get('nok_phone')" class="mt-2" />
                </div>

                <!-- Next of Kin Address -->
                <div>
                    <x-input-label for="nok_address" :value="__('Next of Kin Contact Address')" />
                    <textarea id="nok_address" class="block mt-1 w-full" name="nok_address" rows="2">{{ old('nok_address') }}</textarea>
                    <x-input-error :messages="$errors->get('nok_address')" class="mt-2" />
                </div>
            </div>

            <!-- Footer Section -->
            <div class="register-footer">
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                    {{ __('Already registered?') }}
                </a>

                <x-primary-button class="ms-4">
                    {{ __('Register') }}
                </x-primary-button>
            </div>
        </div>
    </form>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const coopSelect = document.getElementById('cooperative_id');
            const coopCard = document.getElementById('selected_coop_card');
            const coopLogoImg = document.getElementById('selected_coop_logo_img');
            const coopLogoFallback = document.getElementById('selected_coop_logo_fallback');
            const coopNameTxt = document.getElementById('selected_coop_name_txt');
            const coopSlugTxt = document.getElementById('selected_coop_slug_txt');
            const headerLogoImg = document.getElementById('guestHeaderLogoImg');
            const headerLogoText = document.getElementById('guestHeaderLogoText');

            function updateCoopDisplay() {
                if (!coopSelect) return;
                const selectedOption = coopSelect.options[coopSelect.selectedIndex];
                if (!selectedOption || !selectedOption.value) {
                    if (coopCard) coopCard.classList.add('hidden');
                    return;
                }

                const logo = selectedOption.getAttribute('data-logo');
                const name = selectedOption.getAttribute('data-name');
                const slug = selectedOption.getAttribute('data-slug');

                if (coopNameTxt) coopNameTxt.textContent = name;
                if (coopSlugTxt) coopSlugTxt.textContent = slug;

                if (logo && logo.trim() !== '') {
                    if (coopLogoImg) {
                        coopLogoImg.src = logo;
                        coopLogoImg.classList.remove('hidden');
                    }
                    if (coopLogoFallback) coopLogoFallback.classList.add('hidden');
                    if (headerLogoImg) {
                        headerLogoImg.src = logo;
                        headerLogoImg.alt = name;
                    }
                } else {
                    if (coopLogoImg) coopLogoImg.classList.add('hidden');
                    if (coopLogoFallback) {
                        coopLogoFallback.classList.remove('hidden');
                        coopLogoFallback.innerHTML = '<i class="bi bi-building"></i>';
                    }
                }

                if (coopCard) coopCard.classList.remove('hidden');
            }

            if (coopSelect) {
                coopSelect.addEventListener('change', updateCoopDisplay);
                updateCoopDisplay();
            }
        });
    </script>
</x-guest-layout>
