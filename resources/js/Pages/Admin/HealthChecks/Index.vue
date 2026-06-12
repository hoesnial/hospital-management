<script setup>
import AppLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Link } from "@inertiajs/vue3";
import { ref } from "vue";

const props = defineProps({
    healthChecks: Array,
});

const healthChecks = ref(props.healthChecks);

const deleteHealthCheck = (id) => {
    if (confirm("Apakah Anda yakin ingin menghapus paket kesehatan ini?")) {
        axios
            .delete(route("admin.health-checks.destroy", id))
            .then((response) => {
                // Remove the deleted health check from the local array
                healthChecks.value = healthChecks.value.filter(
                    (healthCheck) => healthCheck.id !== id
                );
                // Show success message
                alert("Paket kesehatan berhasil dihapus.");
            })
            .catch((error) => {
                console.error("Error:", error);
                alert("Terjadi kesalahan saat menghapus paket kesehatan.");
            });
    }
};
</script>

<template>
    <AppLayout title="Paket Kesehatan">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Kelola Paket Kesehatan
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">
                                Paket Kesehatan
                            </h3>
                            <Link
                                :href="route('admin.health-checks.create')"
                                class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Tambah Paket Kesehatan Baru
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Nama
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Harga
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Fitur
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Populer
                                        </th>
                                        <th
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                        >
                                            Tindakan
                                        </th>
                                    </tr>
                                </thead>
                                <tbody
                                    class="bg-white divide-y divide-gray-200"
                                >
                                    <tr
                                        v-for="healthCheck in healthChecks"
                                        :key="healthCheck.id"
                                    >
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                                        >
                                            {{ healthCheck.name }}
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        >
                                            {{ healthCheck.price }}
                                        </td>
                                        <td
                                            class="px-6 py-4 text-sm text-gray-500"
                                        >
                                            <ul class="list-disc list-inside">
                                                <li
                                                    v-for="feature in healthCheck.features"
                                                    :key="feature"
                                                >
                                                    {{ feature }}
                                                </li>
                                            </ul>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                                        >
                                            <span
                                                v-if="healthCheck.popular"
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"
                                            >
                                                Yes
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                                            >
                                                No
                                            </span>
                                        </td>
                                        <td
                                            class="px-6 py-4 whitespace-nowrap text-sm font-medium"
                                        >
                                            <Link
                                                :href="
                                                    route(
                                                        'admin.health-checks.edit',
                                                        healthCheck.id
                                                    )
                                                "
                                                class="text-indigo-600 hover:text-indigo-900 mr-3"
                                            >
                                                Ubah
                                            </Link>
                                            <button
                                                @click="
                                                    deleteHealthCheck(
                                                        healthCheck.id
                                                    )
                                                "
                                                class="text-red-600 hover:text-red-900"
                                            >
                                                Hapus
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
