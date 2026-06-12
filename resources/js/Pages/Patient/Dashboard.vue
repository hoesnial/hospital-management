<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { Head, Link } from "@inertiajs/vue3";
</script>

<template>
    <Head title="Profil Pasien" />

    <AuthenticatedLayout>
        <template #header>
            <div
                class="flex flex-col sm:flex-row items-start sm:items-center justify-between space-y-2 sm:space-y-0"
            >
                <div>
                    <h2
                        class="font-bold text-xl sm:text-2xl text-slate-800 leading-tight"
                    >
                        Dasbor Manajemen Rumah Sakit
                    </h2>
                    <p class="text-slate-600 mt-1">
                        Selamat datang! Kelola kesehatan dan janji temu Anda.
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <div
                        class="w-12 h-12 bg-gradient-to-br from-blue-500 to-purple-600 rounded-xl flex items-center justify-center shadow-lg"
                    >
                        <svg
                            class="w-6 h-6 text-white"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                            />
                        </svg>
                    </div>
                </div>
            </div>
        </template>

        <div class="py-2">
            <div class="max-w-7l mx-auto sm:px-6 lg:px-8 space-y-8">
                <!-- Welcome Section -->
                <div
                    class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-2xl p-4 sm:p-8 border border-blue-100"
                >
                    <div
                        class="flex flex-col sm:flex-row items-center space-y-4 sm:space-y-0 sm:space-x-6"
                    >
                        <div
                            class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl flex items-center justify-center shadow-lg flex-shrink-0"
                        >
                            <svg
                                class="w-8 h-8 text-white"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                />
                            </svg>
                        </div>
                        <div class="text-center sm:text-left">
                            <h3
                                class="text-xl sm:text-2xl font-bold text-slate-800"
                            >
                                Selamat Datang di Rumah Sakit Kami
                            </h3>
                            <p class="text-slate-600 mt-2">
                                Akses rekam medis Anda, buat janji temu,
                                dan kelola paket kesehatan Anda dalam satu
                                tempat.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Grid -->
                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6"
                >
                    <!-- Book Appointment -->
                    <div
                        class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20 hover:shadow-lg transition-all duration-300 group"
                    >
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 mb-4"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 bg-blue-100 rounded-xl flex items-center justify-center group-hover:bg-blue-500 transition-colors duration-300 flex-shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-blue-600 group-hover:text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="font-semibold text-slate-800 text-sm sm:text-base"
                                >
                                    Janji Temu
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Jadwal dengan dokter kami
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('patient.book-appointment')"
                            class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-xl transition-colors duration-300 text-center block text-sm sm:text-base"
                        >
                            Pesan Janji Temu
                        </Link>
                    </div>

                    <!-- Health Packages -->
                    <div
                        class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20 hover:shadow-lg transition-all duration-300 group"
                    >
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 mb-4"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:bg-green-500 transition-colors duration-300 flex-shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-green-600 group-hover:text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="font-semibold text-slate-800 text-sm sm:text-base"
                                >
                                    Paket Kesehatan
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Jelajahi perawatan pencegahan
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('patient.health-packages')"
                            class="w-full bg-green-500 hover:bg-green-600 text-white font-medium py-2 px-4 rounded-xl transition-colors duration-300 text-center block text-sm sm:text-base"
                        >
                            Lihat Paket
                        </Link>
                    </div>

                    <!-- Medical Records -->
                    <div
                        class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20 hover:shadow-lg transition-all duration-300 group"
                    >
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 mb-4"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 bg-purple-100 rounded-xl flex items-center justify-center group-hover:bg-purple-500 transition-colors duration-300 flex-shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-purple-600 group-hover:text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="font-semibold text-slate-800 text-sm sm:text-base"
                                >
                                    Rekam Medis
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Akses riwayat kesehatan Anda
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('patient.medical-records')"
                            class="w-full bg-purple-500 hover:bg-purple-600 text-white font-medium py-2 px-4 rounded-xl transition-colors duration-300 text-center block text-sm sm:text-base"
                        >
                            Lihat Rekam
                        </Link>
                    </div>

                    <!-- Emergency Contact -->
                    <div
                        class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20 hover:shadow-lg transition-all duration-300 group"
                    >
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 mb-4"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 bg-red-100 rounded-xl flex items-center justify-center group-hover:bg-red-500 transition-colors duration-300 flex-shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-red-600 group-hover:text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="font-semibold text-slate-800 text-sm sm:text-base"
                                >
                                    Darurat
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Layanan darurat 24/7
                                </p>
                            </div>
                        </div>
                        <a
                            href="tel:999"
                            class="w-full bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-xl transition-colors duration-300 text-center block text-sm sm:text-base"
                        >
                            Hubungi Sekarang
                        </a>
                    </div>

                    <!-- Health Tips -->
                    <div
                        class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20 hover:shadow-lg transition-all duration-300 group"
                    >
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 mb-4"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:bg-orange-500 transition-colors duration-300 flex-shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-orange-600 group-hover:text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M13 10V3L4 14h7v7l9-11h-7z"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="font-semibold text-slate-800 text-sm sm:text-base"
                                >
                                    Tips Kesehatan
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Saran kesehatan harian
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('patient.health-tips')"
                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-xl transition-colors duration-300 text-center block text-sm sm:text-base"
                        >
                            Baca Selengkapnya
                        </Link>
                    </div>

                    <!-- Profile Settings -->
                    <div
                        class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20 hover:shadow-lg transition-all duration-300 group"
                    >
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 mb-4"
                        >
                            <div
                                class="w-10 h-10 sm:w-12 sm:h-12 bg-indigo-100 rounded-xl flex items-center justify-center group-hover:bg-indigo-500 transition-colors duration-300 flex-shrink-0"
                            >
                                <svg
                                    class="w-5 h-5 sm:w-6 sm:h-6 text-indigo-600 group-hover:text-white"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                    />
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h4
                                    class="font-semibold text-slate-800 text-sm sm:text-base"
                                >
                                    Profil
                                </h4>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Kelola akun Anda
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="route('patient.profile')"
                            class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-2 px-4 rounded-xl transition-colors duration-300 text-center block text-sm sm:text-base"
                        >
                            Ubah Profil
                        </Link>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div
                    class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-white/20"
                >
                    <h4
                        class="font-bold text-lg sm:text-xl text-slate-800 mb-4"
                    >
                        Aktivitas Terbaru
                    </h4>
                    <div class="space-y-3 sm:space-y-4">
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 p-3 sm:p-4 bg-slate-50 rounded-xl"
                        >
                            <div
                                class="w-8 h-8 sm:w-10 sm:h-10 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0"
                            >
                                <svg
                                    class="w-4 h-4 sm:w-5 sm:h-5 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="font-medium text-slate-800 text-sm sm:text-base"
                                >
                                    Janji temu dengan Dr. Smith
                                </p>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Besok pukul 10:00
                                </p>
                            </div>
                            <span class="text-xs text-slate-500 flex-shrink-0"
                                >2 jam yang lalu</span
                            >
                        </div>
                        <div
                            class="flex items-center space-x-3 sm:space-x-4 p-3 sm:p-4 bg-slate-50 rounded-xl"
                        >
                            <div
                                class="w-8 h-8 sm:w-10 sm:h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0"
                            >
                                <svg
                                    class="w-4 h-4 sm:w-5 sm:h-5 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"
                                    />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="font-medium text-slate-800 text-sm sm:text-base"
                                >
                                    Paket Kesehatan dibeli
                                </p>
                                <p class="text-xs sm:text-sm text-slate-600">
                                    Paket Pemeriksaan Tahunan
                                </p>
                            </div>
                            <span class="text-xs text-slate-500 flex-shrink-0"
                                >1 hari yang lalu</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
