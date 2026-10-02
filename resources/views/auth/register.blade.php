<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="register-grid">
            <!-- Left Column: Member Details -->
            <div class="register-col">
                <h3 class="border-b border-gray-700 pb-2 mb-2 text-primary font-semibold text-lg">{{ __('Member Details') }}</h3>
                
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
                    <x-text-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" />
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
                    <x-text-input id="nok_phone" class="block mt-1 w-full" type="text" name="nok_phone" :value="old('nok_phone')" required />
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
</x-guest-layout>
