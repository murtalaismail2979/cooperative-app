<x-guest-layout>
    <style>
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .password-wrapper input {
            padding-right: 2.75rem !important;
        }
        .password-toggle-btn {
            position: absolute;
            right: 0.75rem;
            background: transparent;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            transition: color 0.2s;
            z-index: 5;
        }
        .password-toggle-btn:hover {
            color: #ffffff;
        }
        .section-header-title {
            color: #c7d2fe !important;
            font-size: 1.05rem !important;
            font-weight: 700 !important;
            letter-spacing: -0.01em !important;
            border-bottom: 2px solid #334155 !important;
            padding-bottom: 0.45rem !important;
            margin-bottom: 0.85rem !important;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>

    <form method="POST" action="{{ route('register') }}" id="registerForm">
        @csrf

        @php
            $selectedCoopId = old('cooperative_id', request('cooperative_id'));
            if (!$selectedCoopId && request('coop')) {
                $selectedCoopId = \App\Models\Cooperative::where('slug', strtoupper(request('coop')))->value('id');
            }
            $activeCooperatives = \App\Models\Cooperative::active()->get();
            $currentCoop = $selectedCoopId ? $activeCooperatives->firstWhere('id', $selectedCoopId) : null;
            $regFeeAmount = app(\App\Services\RegistrationFeeService::class)->getCurrentFeeAmount();
        @endphp

        @if(request('coop') && $currentCoop)
            <!-- Direct Invitation Banner -->
            <div class="mb-4 p-3.5 rounded-xl border border-indigo-500/40 bg-indigo-950/40 shadow-sm flex items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold">
                        <i class="bi bi-patch-check-fill text-xl text-indigo-400"></i>
                    </div>
                    <div>
                        <p class="text-xs text-indigo-300 uppercase font-semibold tracking-wider mb-0">Direct Cooperative Membership Invitation</p>
                        <h4 class="text-white font-bold text-sm mb-0">Applying to: {{ $currentCoop->name }} ({{ $currentCoop->slug }})</h4>
                    </div>
                </div>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Active</span>
            </div>
        @endif

        <div class="register-grid">
            <!-- Left Column: Member Details -->
            <div class="register-col">
                <div class="section-header-title">
                    <i class="bi bi-person-badge text-indigo-400"></i>
                    <span>{{ __('Member Details') }}</span>
                </div>
                
                <!-- Cooperative Selection -->
                <div>
                    <x-input-label for="cooperative_id">
                        {{ __('Select Cooperative') }} <span class="text-rose-400 font-bold">*</span>
                    </x-input-label>
                    <select id="cooperative_id" name="cooperative_id" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        <option value="" disabled {{ $selectedCoopId ? '' : 'selected' }}>-- Choose Cooperative to Join * --</option>
                        @foreach($activeCooperatives as $cooperative)
                            @php
                                $coopBaseSlot = (float)(($cooperative->base_slot_amount > 0) ? $cooperative->base_slot_amount : 2000);
                            @endphp
                            <option value="{{ $cooperative->id }}" 
                                    data-logo="{{ $cooperative->logo_url ?? '' }}" 
                                    data-name="{{ $cooperative->name }}" 
                                    data-slug="{{ $cooperative->slug }}"
                                    data-slot="{{ number_format($coopBaseSlot, 2) }}"
                                    data-raw-slot="{{ $coopBaseSlot }}"
                                    {{ (string)$selectedCoopId === (string)$cooperative->id ? 'selected' : '' }}>
                                {{ $cooperative->name }} ({{ $cooperative->slug }})
                            </option>
                        @endforeach
                    </select>
                    
                    <!-- Selected Cooperative Financial Identity Card -->
                    <div id="selected_coop_card" class="mt-2.5 p-3 rounded-xl border border-indigo-500/40 bg-slate-900/90 shadow-lg transition-all duration-200 hidden">
                        <div class="flex items-center gap-3 mb-2">
                            <div id="selected_coop_logo_wrapper" class="w-11 h-11 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center overflow-hidden flex-shrink-0 shadow">
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
                                    <i class="bi bi-patch-check-fill text-emerald-400"></i> Official Membership Portal
                                </p>
                            </div>
                        </div>

                        <!-- Financial Terms Preview Badge -->
                        <div class="pt-2 border-t border-slate-800 flex flex-wrap items-center gap-2 text-xs">
                            <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300">
                                <i class="bi bi-piggy-bank text-indigo-400 me-1"></i> Base Slot: <strong class="text-white" id="coop_slot_display">₦0.00</strong>/mo
                            </span>
                            <span class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-300">
                                <i class="bi bi-receipt text-emerald-400 me-1"></i> Reg Fee: <strong class="text-white">₦{{ number_format($regFeeAmount, 2) }}</strong>
                            </span>
                        </div>
                    </div>

                    <x-input-error :messages="$errors->get('cooperative_id')" class="mt-2" />
                </div>

                <!-- Monthly Savings Commitment (Slots) -->
                <div>
                    <x-input-label for="slots">
                        {{ __('Monthly Savings Commitment') }} <span class="text-rose-400 font-bold">*</span>
                    </x-input-label>
                    @php
                        $oldSlots = (int) old('slots', 1);
                        $initialBaseSlot = ($currentCoop && $currentCoop->base_slot_amount > 0) 
                            ? (float) $currentCoop->base_slot_amount 
                            : (($activeCooperatives->first() && $activeCooperatives->first()->base_slot_amount > 0) ? (float) $activeCooperatives->first()->base_slot_amount : 2000.0);
                    @endphp
                    <select id="slots" name="slots" required class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                        @for($i = 1; $i <= 10; $i++)
                            @php
                                $monthly = $i * $initialBaseSlot;
                                $annual = $monthly * 12;
                            @endphp
                            <option value="{{ $i }}" {{ $oldSlots === $i ? 'selected' : '' }}>
                                {{ $i }} Slot{{ $i > 1 ? 's' : '' }} &mdash; &#8358;{{ number_format($monthly, 2) }}/month (&#8358;{{ number_format($annual, 2) }}/yr)
                            </option>
                        @endfor
                    </select>
                    <p class="text-xs text-slate-400 mt-1 mb-0 flex items-center gap-1">
                        <i class="bi bi-info-circle text-indigo-400"></i>
                        <span>Select the monthly savings slots you commit to contribute (1 to 10 slots).</span>
                    </p>
                    <x-input-error :messages="$errors->get('slots')" class="mt-2" />
                </div>

                <!-- Full Name -->
                <div>
                    <x-input-label for="name">
                        {{ __('Full Name') }} <span class="text-rose-400 font-bold">*</span>
                    </x-input-label>
                    <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Enter your full name" />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <!-- Phone (User) -->
                <div>
                    <x-input-label for="phone">
                        {{ __('Phone Number') }} <span class="text-rose-400 font-bold">*</span>
                    </x-input-label>
                    <x-text-input id="phone" class="block mt-1 w-full" type="tel" inputmode="tel" pattern="[\+]?[0-9\s\-\(\)]{7,20}" maxlength="20" name="phone" :value="old('phone')" required placeholder="e.g. 08012345678 or +234 800 000 0000" />
                    <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                </div>

                <!-- Email Address -->
                <div>
                    <x-input-label for="email">
                        {{ __('Email Address') }} <span class="text-rose-400 font-bold">*</span>
                    </x-input-label>
                    <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="e.g. member@example.com" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Address (User) -->
                <div>
                    <x-input-label for="address" :value="__('Residential / Contact Address')" />
                    <textarea id="address" class="block mt-1 w-full" name="address" rows="2" placeholder="e.g. House No. 24, Crescent Road, Commercial Area">{{ old('address') }}</textarea>
                    <x-input-error :messages="$errors->get('address')" class="mt-2" />
                </div>
            </div>

            <!-- Right Column: Next of Kin & Account Security -->
            <div class="register-col">
                <!-- Next of Kin Section -->
                <div>
                    <div class="section-header-title">
                        <i class="bi bi-people text-indigo-400"></i>
                        <span>{{ __('Next of Kin Details') }}</span>
                    </div>

                    <!-- Next of Kin Name -->
                    <div class="mb-3">
                        <x-input-label for="nok_name">
                            {{ __('Next of Kin Full Name') }} <span class="text-rose-400 font-bold">*</span>
                        </x-input-label>
                        <x-text-input id="nok_name" class="block mt-1 w-full" type="text" name="nok_name" :value="old('nok_name')" required placeholder="Full Name of Next of Kin" />
                        <x-input-error :messages="$errors->get('nok_name')" class="mt-2" />
                    </div>

                    <!-- Next of Kin Relationship -->
                    <div class="mb-3">
                        <x-input-label for="nok_relationship">
                            {{ __('Relationship to Member') }} <span class="text-rose-400 font-bold">*</span>
                        </x-input-label>
                        <select id="nok_relationship" name="nok_relationship" required>
                            <option value="" disabled {{ old('nok_relationship') ? '' : 'selected' }}>-- Select Relationship * --</option>
                            @foreach([
                                'Spouse', 'Child', 'Son', 'Daughter', 'Father', 'Mother', 
                                'Brother', 'Sister', 'Sibling', 'Uncle', 'Aunt', 
                                'Nephew', 'Niece', 'Cousin', 'Guardian', 'Other'
                            ] as $rel)
                                <option value="{{ $rel }}" {{ old('nok_relationship') == $rel ? 'selected' : '' }}>{{ $rel }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('nok_relationship')" class="mt-2" />
                    </div>

                    <!-- Next of Kin Phone -->
                    <div class="mb-3">
                        <x-input-label for="nok_phone">
                            {{ __('Next of Kin Phone Number') }} <span class="text-rose-400 font-bold">*</span>
                        </x-input-label>
                        <x-text-input id="nok_phone" class="block mt-1 w-full" type="tel" inputmode="tel" pattern="[\+]?[0-9\s\-\(\)]{7,20}" maxlength="20" name="nok_phone" :value="old('nok_phone')" required placeholder="e.g. 08012345678 or +234 800 000 0000" />
                        <x-input-error :messages="$errors->get('nok_phone')" class="mt-2" />
                    </div>

                    <!-- Next of Kin Address -->
                    <div>
                        <x-input-label for="nok_address" :value="__('Next of Kin Contact Address')" />
                        <textarea id="nok_address" class="block mt-1 w-full" name="nok_address" rows="1" placeholder="Next of kin residential address">{{ old('nok_address') }}</textarea>
                        <x-input-error :messages="$errors->get('nok_address')" class="mt-2" />
                    </div>
                </div>

                <!-- Account Security Section -->
                <div class="mt-2">
                    <div class="section-header-title">
                        <i class="bi bi-shield-lock text-indigo-400"></i>
                        <span>{{ __('Account Password & Security') }}</span>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <x-input-label for="password">
                            {{ __('Password') }} <span class="text-rose-400 font-bold">*</span>
                        </x-input-label>
                        <div class="password-wrapper">
                            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimum 8 characters" />
                            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password', this)" title="Show/Hide password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <x-input-label for="password_confirmation">
                            {{ __('Confirm Password') }} <span class="text-rose-400 font-bold">*</span>
                        </x-input-label>
                        <div class="password-wrapper">
                            <x-text-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Re-enter password" />
                            <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('password_confirmation', this)" title="Show/Hide password" aria-label="Toggle password visibility">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Footer Section: Terms & Actions -->
            <div class="register-footer" style="flex-direction: column; align-items: stretch; gap: 1.25rem;">
                <!-- Information Notice -->
                <div class="p-3 bg-slate-800/80 border border-slate-700/80 rounded-xl text-xs text-slate-300 flex items-start gap-2.5">
                    <i class="bi bi-info-circle-fill text-indigo-400 text-sm mt-0.5 flex-shrink-0"></i>
                    <span class="leading-relaxed">
                        <strong class="text-indigo-300">Application Notice:</strong> Upon registration, your profile will be queued for administrative review. Your official Member ID and cooperative savings slots will be activated upon management approval.
                    </span>
                </div>

                <!-- Terms and By-laws Agreement Checkbox -->
                <div class="p-3.5 bg-slate-900/90 border border-slate-700 rounded-xl">
                    <div class="flex items-start gap-3">
                        <input type="checkbox" id="terms" name="terms" value="1" required class="mt-1" {{ old('terms') ? 'checked' : '' }}>
                        <label for="terms" class="text-xs text-slate-200 leading-relaxed cursor-pointer mb-0">
                            I declare that the information provided is accurate. I agree to abide by the cooperative's constitution, monthly savings obligations, and consent to official membership vetting. <span class="text-rose-400 font-bold">*</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('terms')" class="mt-1.5" />
                </div>

                <!-- Action Controls -->
                <div class="flex flex-wrap justify-between items-center gap-3 pt-1">
                    <a class="underline text-sm text-gray-400 hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                        <i class="bi bi-box-arrow-in-right me-1"></i> {{ __('Already registered? Log in') }}
                    </a>

                    <x-primary-button id="btnSubmitRegister">
                        <span>{{ __('Complete Registration') }}</span>
                        <i class="bi bi-arrow-right ms-1"></i>
                    </x-primary-button>
                </div>
            </div>
        </div>
    </form>

    <script>
        function togglePasswordVisibility(fieldId, button) {
            const input = document.getElementById(fieldId);
            if (!input) return;
            const icon = button.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('bi-eye');
                    icon.classList.add('bi-eye-slash');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('bi-eye-slash');
                    icon.classList.add('bi-eye');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const coopSelect = document.getElementById('cooperative_id');
            const coopCard = document.getElementById('selected_coop_card');
            const coopLogoImg = document.getElementById('selected_coop_logo_img');
            const coopLogoFallback = document.getElementById('selected_coop_logo_fallback');
            const coopNameTxt = document.getElementById('selected_coop_name_txt');
            const coopSlugTxt = document.getElementById('selected_coop_slug_txt');
            const coopSlotDisplay = document.getElementById('coop_slot_display');
            const headerLogoImg = document.getElementById('guestHeaderLogoImg');
            const slotsSelect = document.getElementById('slots');

            function updateSlotOptions(baseAmount) {
                if (!slotsSelect) return;
                const currentVal = parseInt(slotsSelect.value) || 1;
                slotsSelect.innerHTML = '';

                for (let i = 1; i <= 10; i++) {
                    const monthly = i * baseAmount;
                    const annual = monthly * 12;
                    const opt = document.createElement('option');
                    opt.value = i;
                    const monthlyFormatted = monthly.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    const annualFormatted = annual.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                    opt.textContent = `${i} Slot${i > 1 ? 's' : ''} — ₦${monthlyFormatted}/month (₦${annualFormatted}/yr)`;
                    if (i === currentVal) {
                        opt.selected = true;
                    }
                    slotsSelect.appendChild(opt);
                }
            }

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
                const slot = selectedOption.getAttribute('data-slot');
                const rawSlot = parseFloat(selectedOption.getAttribute('data-raw-slot')) || 2000;

                if (coopNameTxt) coopNameTxt.textContent = name;
                if (coopSlugTxt) coopSlugTxt.textContent = slug;
                if (coopSlotDisplay) coopSlotDisplay.textContent = '₦' + (slot || '0.00');

                // Dynamically update savings commitment slots to match selected cooperative's rate
                updateSlotOptions(rawSlot);

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
