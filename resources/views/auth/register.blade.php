<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <div class="relative mt-1" x-data="{ showPassword: false }">
                <x-text-input id="password"
                              class="block w-full pr-11"
                              type="password"
                              x-bind:type="showPassword ? 'text' : 'password'"
                              name="password"
                              required autocomplete="new-password" />
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
                        :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                        @click="showPassword = !showPassword">
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3c-4.5 0-8.1 3.2-9 7 0 0 2.9 7 9 7s9-7 9-7c-.9-3.8-4.5-7-9-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                    </svg>
                    <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4.03 3.97a.75.75 0 0 0-1.06 1.06l1.6 1.6C2.83 8.1 1.66 9.9 1 10c.9 3.8 4.5 7 9 7 1.7 0 3.3-.5 4.6-1.3l1.4 1.4a.75.75 0 1 0 1.06-1.06l-12-12zm5.97 10.53a4 4 0 0 1-4-4c0-.5.1-1 .3-1.5l5.2 5.2c-.5.2-1 .3-1.5.3zm4.9-1.4-1.1-1.1a4 4 0 0 0-5.2-5.2L7.5 5.7c.8-.4 1.6-.7 2.5-.7 4.5 0 8.1 3.2 9 7-.3 1.1-1 2.3-2.1 3.6z"/>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <div class="relative mt-1" x-data="{ showPassword: false }">
                <x-text-input id="password_confirmation"
                              class="block w-full pr-11"
                              type="password"
                              x-bind:type="showPassword ? 'text' : 'password'"
                              name="password_confirmation" required autocomplete="new-password" />
                <button type="button"
                        class="absolute inset-y-0 right-2 inline-flex items-center justify-center rounded-lg px-2 text-gray-500 hover:text-gray-700"
                        :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'"
                        @click="showPassword = !showPassword">
                    <svg x-show="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10 3c-4.5 0-8.1 3.2-9 7 0 0 2.9 7 9 7s9-7 9-7c-.9-3.8-4.5-7-9-7zm0 11a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"/>
                    </svg>
                    <svg x-show="showPassword" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4.03 3.97a.75.75 0 0 0-1.06 1.06l1.6 1.6C2.83 8.1 1.66 9.9 1 10c.9 3.8 4.5 7 9 7 1.7 0 3.3-.5 4.6-1.3l1.4 1.4a.75.75 0 1 0 1.06-1.06l-12-12zm5.97 10.53a4 4 0 0 1-4-4c0-.5.1-1 .3-1.5l5.2 5.2c-.5.2-1 .3-1.5.3zm4.9-1.4-1.1-1.1a4 4 0 0 0-5.2-5.2L7.5 5.7c.8-.4 1.6-.7 2.5-.7 4.5 0 8.1 3.2 9 7-.3 1.1-1 2.3-2.1 3.6z"/>
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
