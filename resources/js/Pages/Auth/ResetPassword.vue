<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { Head, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import { Eye, EyeOff, Mail, Lock } from "lucide-vue-next";

const props = defineProps({
    email: String,
    token: String,
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: "",
    password_confirmation: "",
});

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);

const submit = () => {
    form.post(route("password.store"), {
        onFinish: () => form.reset("password", "password_confirmation"),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Atur Ulang Kata Sandi" />

        <div
            class="flex justify-center bg-gradient-to-br from-gray-50 to-gray-100 py-12 px-6 sm:px-8"
        >
            <div>
                <h2 class="text-2xl font-semibold text-gray-900 text-center">
                    Atur ulang kata sandi Anda
                </h2>
                <p class="text-sm text-gray-500 text-center">
                    Masukkan kata sandi baru Anda di bawah untuk mendapatkan
                    kembali akses.
                </p>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email -->
                    <div>
                        <InputLabel for="email" value="Surel" />
                        <div class="relative mt-1">
                            <Mail
                                class="absolute left-3 top-3 h-5 w-5 text-gray-400"
                            />
                            <TextInput
                                id="email"
                                type="email"
                                class="pl-10 pr-3 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl transition"
                                v-model="form.email"
                                required
                                autocomplete="username"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Password -->
                    <div>
                        <InputLabel for="password" value="Kata Sandi Baru" />
                        <div class="relative mt-1">
                            <Lock
                                class="absolute left-3 top-3 h-5 w-5 text-gray-400"
                            />
                            <TextInput
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="pl-10 pr-10 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl transition"
                                v-model="form.password"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 transition"
                            >
                                <component
                                    :is="showPassword ? EyeOff : Eye"
                                    class="h-5 w-5"
                                />
                            </button>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.password"
                        />
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <InputLabel
                            for="password_confirmation"
                            value="Konfirmasi Kata Sandi"
                        />
                        <div class="relative mt-1">
                            <Lock
                                class="absolute left-3 top-3 h-5 w-5 text-gray-400"
                            />
                            <TextInput
                                id="password_confirmation"
                                :type="
                                    showPasswordConfirmation
                                        ? 'text'
                                        : 'password'
                                "
                                class="pl-10 pr-10 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-xl transition"
                                v-model="form.password_confirmation"
                                required
                                autocomplete="new-password"
                            />
                            <button
                                type="button"
                                @click="
                                    showPasswordConfirmation =
                                        !showPasswordConfirmation
                                "
                                class="absolute right-3 top-3 text-gray-400 hover:text-gray-600 transition"
                            >
                                <component
                                    :is="
                                        showPasswordConfirmation ? EyeOff : Eye
                                    "
                                    class="h-5 w-5"
                                />
                            </button>
                        </div>
                        <InputError
                            class="mt-2"
                            :message="form.errors.password_confirmation"
                        />
                    </div>

                    <!-- Submit -->
                    <div class="pt-4">
                        <PrimaryButton
                            class="w-full flex justify-center items-center py-3 text-base font-medium rounded-xl shadow-md bg-indigo-600 hover:bg-indigo-700 text-white transition"
                            :class="{ 'opacity-50': form.processing }"
                            :disabled="form.processing"
                        >
                            Atur Ulang Kata Sandi
                        </PrimaryButton>
                    </div>
                    <p class="text-sm mt-4 text-right text-gray-500">
                        Ingat kata sandi Anda?
                        <a
                            :href="route('login')"
                            class="text-indigo-600 hover:text-indigo-500 font-medium"
                            >Masuk</a
                        >
                    </p>
                </form>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
input:focus {
    outline: none;
}
</style>
