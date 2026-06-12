<script setup>
import { computed } from "vue";
import GuestLayout from "@/Layouts/GuestLayout.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, Link, useForm } from "@inertiajs/vue3";

const props = defineProps({
    status: String,
});

const form = useForm({});

const submit = () => {
    form.post(route("verification.send"));
};

const verificationLinkSent = computed(
    () => props.status === "verification-link-sent"
);
</script>

<template>
    <GuestLayout>
        <Head title="Verifikasi Surel" />

        <div class="mb-4 text-sm text-gray-600">
            Terima kasih telah mendaftar! Sebelum memulai, bisakah Anda
            memverifikasi alamat email Anda dengan mengklik tautan yang baru
            saja kami kirimkan ke email Anda? Jika Anda tidak menerima email
            tersebut, kami dengan senang hati akan mengirimkannya lagi.
        </div>

        <div
            class="mb-4 font-medium text-sm text-green-600"
            v-if="verificationLinkSent"
        >
            Tautan verifikasi baru telah dikirim ke alamat email yang Anda
            berikan saat pendaftaran.
        </div>

        <form @submit.prevent="submit">
            <div
                class="mt-4 flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0"
            >
                <PrimaryButton
                    :class="{
                        'opacity-25': form.processing,
                        'w-full sm:w-auto': true,
                    }"
                    :disabled="form.processing"
                >
                    Kirim Ulang Email Verifikasi
                </PrimaryButton>

                <Link
                    :href="route('logout')"
                    method="post"
                    as="button"
                    class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 text-center sm:text-left"
                    >Keluar</Link
                >
            </div>
        </form>
    </GuestLayout>
</template>
