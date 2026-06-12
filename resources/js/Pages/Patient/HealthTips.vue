<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head } from "@inertiajs/vue3";
</script>
<template>
    <Head title="Tips Kesehatan" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-2xl text-slate-800 leading-tight">
                Tips Kesehatan
            </h2>
            <p class="text-slate-600 mt-1">Saran kesehatan harian</p>
        </template>

        <div class="py-2">
            <div class="max-w-7l mx-auto sm:px-6 lg:px-8">
                <div
                    class="bg-white rounded-2xl p-6 shadow-sm border border-white/20"
                >
                    <p>Konten tips kesehatan akan ada di sini.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
