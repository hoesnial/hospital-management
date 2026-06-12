<script setup>
import { Link } from "@inertiajs/vue3";
const features = [
    {
        link: "/find-doctor",
        title: "Spesialis Ahli",
        desc: "200+ spesialis medis ternama di semua departemen",
        icon: "👨‍⚕️",
    },
    {
        link: "#",
        title: "Teknologi Canggih",
        desc: "Peralatan dan fasilitas medis tercanggih",
        icon: "🔬",
    },
    {
        link: "#",
        title: "Darurat 24/7",
        desc: "Layanan darurat dan perawatan kritis 24 jam",
        icon: "🚨",
    },
    {
        link: "#",
        title: "Pasien Utama",
        desc: "Rencana perawatan pribadi dan perawatan penuh kasih",
        icon: "❤️",
    },
];
</script>

<template>
    <section class="py-12 md:py-14 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-card">
                <h2 class="text-2xl md:text-3xl font-semibold text-gray-900">
                    Mengapa Memilih
                    <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500"
                    >
                        Xet Hospital?
                    </span>
                </h2>
                <p
                    class="text-base md:text-lg text-gray-600 max-w-lg md:max-w-2xl mt-2"
                >
                    Rasakan keunggulan layanan kesehatan dengan pendekatan yang berpusat pada pasien dan teknologi medis mutakhir.
                </p>
            </div>

            <div
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 mt-8"
            >
                <Link
                    v-for="feature in features"
                    :key="feature.title"
                    :href="feature.link"
                    class="text-center p-4 rounded-2xl bg-gradient-to-br from-gray-50 to-white border border-gray-200 hover:border-blue-300 hover:shadow-xl transition-all duration-500 transform hover:-translate-y-2"
                >
                    <div class="text-3xl md:text-4xl mb-4">
                        {{ feature.icon }}
                    </div>
                    <h3 class="text-lg md:text-xl font-bold text-gray-900 mb-2">
                        {{ feature.title }}
                    </h3>
                    <p
                        class="text-gray-600 text-sm md:text-base leading-relaxed"
                    >
                        {{ feature.desc }}
                    </p>
                </Link>
            </div>
        </div>
    </section>
</template>

<style scoped>
.text-card {
    margin: 0 100px;
    margin-bottom: 10px;
}

@media (max-width: 768px) {
    .text-card {
        margin: 0 20px;
    }
}

@media (max-width: 480px) {
    .text-card {
        margin: 0 10px;
    }
}
</style>
