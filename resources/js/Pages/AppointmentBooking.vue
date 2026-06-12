<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref, onMounted, computed, watch } from "vue";
import Header from "@/Components/Landing/Header.vue";
import Footer from "@/Components/Landing/Footer.vue";

const {
    doctors,
    canLogin,
    canRegister,
    laravelVersion,
    phpVersion,
    appointments,
    selectedDoctor,
} = defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: String,
    phpVersion: String,
    doctors: Array,
    appointments: Object,
    selectedDoctor: Object,
});

// Function to generate time slots from doctor's schedules (start and end times only)
const generateTimeSlotsFromSchedules = (schedules) => {
    if (!schedules || schedules.length === 0) return [];

    const slots = [];
    schedules.forEach((schedule) => {
        const start = new Date(`1970-01-01T${schedule.start_time}`);
        const end = new Date(`1970-01-01T${schedule.end_time}`);

        // Format start time
        const startHours = start.getHours();
        const startMinutes = start.getMinutes();
        const startAmpm = startHours >= 12 ? "PM" : "AM";
        const startDisplayHours = startHours % 12 || 12;
        const startDisplayTime = `${startDisplayHours}:${startMinutes
            .toString()
            .padStart(2, "0")} ${startAmpm}`;
        slots.push(startDisplayTime);

        // Format end time
        const endHours = end.getHours();
        const endMinutes = end.getMinutes();
        const endAmpm = endHours >= 12 ? "PM" : "AM";
        const endDisplayHours = endHours % 12 || 12;
        const endDisplayTime = `${endDisplayHours}:${endMinutes
            .toString()
            .padStart(2, "0")} ${endAmpm}`;
        slots.push(endDisplayTime);
    });

    // Remove duplicates and sort
    return [...new Set(slots)].sort();
};

const form = ref({
    firstName: "",
    lastName: "",
    email: "",
    phone: "",
    gender: "",
    age: "",
    preferredDate: "",
    preferredTime: "",
    speciality: "",
    doctorId: "",
    additionalNotes: "",
});

const submitting = ref(false);
const showSuccessModal = ref(false);
const successAppointment = ref(null);

// Computed property for minimum date (today)
const minDate = computed(() => {
    const today = new Date();
    return today.toISOString().split("T")[0];
});

const submitForm = async () => {
    submitting.value = true;
    try {
        const formData = new FormData();
        formData.append("first_name", form.value.firstName);
        formData.append("last_name", form.value.lastName);
        formData.append("email", form.value.email);
        formData.append("phone", form.value.phone);
        formData.append("gender", form.value.gender);
        formData.append("age", form.value.age);
        formData.append("preferred_date", form.value.preferredDate);
        formData.append("preferred_time", form.value.preferredTime);
        formData.append("speciality", form.value.speciality);
        formData.append("doctor_id", form.value.doctorId || "");
        formData.append("additional_notes", form.value.additionalNotes || "");
        formData.append(
            "_token",
            document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content")
        );

        const response = await fetch("/appointments", {
            method: "POST",
            body: formData,
        });

        let result;
        const contentType = response.headers.get("content-type");
        if (contentType && contentType.includes("application/json")) {
            result = await response.json();
        } else {
            // If not JSON, treat as error and get text
            const text = await response.text();
            console.error("Non-JSON response:", text);
            result = {
                message:
                    "Server returned an unexpected response. Please try again.",
            };
        }

        if (response.ok) {
            successAppointment.value = result.appointment;
            showSuccessModal.value = true;
        } else {
            alert(
                "Error booking appointment: " +
                    (result.message || "Unknown error")
            );
        }
    } catch (error) {
        console.error("Error:", error);
        alert("Terjadi kesalahan saat memesan janji temu.");
    } finally {
        submitting.value = false;
    }
};

// Available time slots
const timeSlots = ref([]);

// Computed property for unique specialties from doctors
const specialties = computed(() => {
    const uniqueSpecialties = new Set();
    doctors.forEach((doctor) => {
        if (doctor.speciality) {
            uniqueSpecialties.add(doctor.speciality);
        }
    });
    return Array.from(uniqueSpecialties).map((speciality) => {
        // Map speciality to label and icon (you can customize this mapping)
        const mappings = {};
        const mapping = mappings[speciality] || {
            label: speciality,
            icon: "👨‍⚕️",
        };
        return { value: speciality, label: mapping.label, icon: mapping.icon };
    });
});

// Computed property for filtered doctors based on selected speciality
const filteredDoctors = computed(() => {
    if (!form.value.speciality) return [];
    return doctors.filter(
        (doctor) => doctor.speciality === form.value.speciality
    );
});

// Computed property for available dates based on selected doctor's schedule
const availableDates = computed(() => {
    if (!form.value.doctorId) return []; // No dates if no doctor selected

    const selectedDoctor = doctors.find(
        (doctor) => doctor.id == form.value.doctorId
    );
    if (
        !selectedDoctor ||
        !selectedDoctor.schedules ||
        selectedDoctor.schedules.length === 0
    ) {
        return []; // No dates available
    }

    // Collect all available days of the week from doctor's schedules
    const availableDays = new Set();
    selectedDoctor.schedules.forEach((schedule) => {
        schedule.day_of_week.forEach((day) => availableDays.add(day));
    });

    // Generate available dates starting from tomorrow for the next 7 days
    const dates = [];
    const today = new Date();
    for (let i = 1; i < 8; i++) {
        const date = new Date(today);
        date.setDate(today.getDate() + i);
        const dayName = date
            .toLocaleDateString("id-ID", { weekday: "long" })
            .toLowerCase()
            .slice(0, 3);
        if (availableDays.has(dayName)) {
            const value = date.toISOString().split("T")[0];
            const label = date.toLocaleDateString("id-ID", {
                weekday: "long",
                year: "numeric",
                month: "short",
                day: "numeric",
            });

            const appointmentKey = `${selectedDoctor.id}-${value}`;
            const appointmentCount = appointments[appointmentKey]?.count || 0;
            const maxPatients =
                selectedDoctor.schedules.find((schedule) =>
                    schedule.day_of_week.includes(dayName)
                )?.max_patients_per_day || 0;

            let status = "";
            if (appointmentCount >= maxPatients) {
                                status = "Penuh";
                            } else if (appointmentCount > 0) {
                                status = "Terbatas";
            }

            dates.push({ value, label, status });
        }
    }

    return dates;
});

// Computed property for available time slots based on selected doctor and date
const availableTimeSlots = computed(() => {
    if (!form.value.doctorId) return timeSlots.value; // Default slots if no doctor selected

    const selectedDoctor = doctors.find(
        (doctor) => doctor.id == form.value.doctorId
    );
    if (
        !selectedDoctor ||
        !selectedDoctor.schedules ||
        selectedDoctor.schedules.length === 0
    ) {
        return []; // No slots available
    }

    let schedulesToUse = selectedDoctor.schedules;

    if (form.value.preferredDate) {
        // Get day of week for the selected date
        const selectedDate = new Date(form.value.preferredDate);
        const dayName = selectedDate
            .toLocaleDateString("id-ID", { weekday: "long" })
            .toLowerCase()
            .slice(0, 3);

        // Filter schedules for that day
        schedulesToUse = selectedDoctor.schedules.filter((schedule) =>
            schedule.day_of_week.includes(dayName)
        );
    }

    return generateTimeSlotsFromSchedules(schedulesToUse);
});

// Watch for speciality changes to reset doctor selection
watch(
    () => form.value.speciality,
    (newSpeciality) => {
        if (newSpeciality && form.value.doctorId) {
            const selectedDoctor = doctors.find(
                (doctor) => doctor.id === form.value.doctorId
            );
            if (
                !selectedDoctor ||
                selectedDoctor.speciality !== newSpeciality
            ) {
                form.value.doctorId = "";
            }
        }
    }
);

// Watch for doctor changes to reset date and time selection
watch(
    () => form.value.doctorId,
    (newDoctorId) => {
        if (newDoctorId !== form.value.doctorId) {
            form.value.preferredDate = "";
            form.value.preferredTime = "";
        }
    }
);

// Pre-fill form if selectedDoctor is provided
onMounted(() => {
    if (selectedDoctor) {
        form.value.doctorId = selectedDoctor.id;
        form.value.speciality = selectedDoctor.speciality;
    }
});

const downloadAppointmentDetails = (bookingId) => {
    // Create a temporary link to download the PDF
    const link = document.createElement("a");
    link.href = `/appointments/${bookingId}/download-pdf`;
    link.download = `appointment_${bookingId}.pdf`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

const closeModal = () => {
    showSuccessModal.value = false;
    successAppointment.value = null;
    // Reset form
    form.value = {
        firstName: "",
        lastName: "",
        email: "",
        phone: "",
        gender: "",
        age: "",
        preferredDate: "",
        preferredTime: "",
        speciality: "",
        doctorId: "",
        additionalNotes: "",
    };
};

// Benefits information
const benefits = ref([
    {
        icon: "⏰",
        title: "Konfirmasi Cepat",
        description: "Dapatkan konfirmasi janji dalam 2 jam",
    },
    {
        icon: "👨‍⚕️",
        title: "Dokter Ahli",
        description: "Konsultasi dengan tenaga medis spesialis",
    },
    {
        icon: "💻",
        title: "Konsultasi Online",
        description: "Tersedia opsi janji virtual",
    },
    {
        icon: "📱",
        title: "Pengingat",
        description: "Dapatkan pengingat SMS & email sebelum janji Anda",
    },
]);

// Hospital stats
const hospitalStats = ref([
    { number: "50,000+", label: "Pasien Ditangani" },
    { number: "200+", label: "Dokter Ahli" },
    { number: "98%", label: "Tingkat Keberhasilan" },
    { number: "24/7", label: "Perawatan Darurat" },
]);
</script>

<template>
    <Head title="Buat Janji - Xet Specialized Hospital" />

    <!-- Header -->
    <Header :canLogin="canLogin" :canRegister="canRegister" />

    <!-- Main Content -->
    <main
        class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50/30"
    >
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Header Section -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-100 rounded-full text-blue-700 text-sm font-semibold mb-4"
                >
                    <div
                        class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"
                    ></div>
                    Buat Janji Temu
                </div>
                <h1
                    class="text-3xl md:text-4xl font-black text-gray-900 mb-4 leading-tight"
                >
                    Jadwalkan
                    <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-cyan-500"
                    >
                        Kunjungan Medis
                    </span>
                </h1>
                <p
                    class="text-sm md:text-base text-gray-700 leading-relaxed max-w-3xl mx-auto"
                >
                    Ambil langkah pertama menuju kesehatan yang lebih baik. Buat janji
                    dengan tim medis ahli kami dan rasakan layanan kesehatan kelas dunia.
                </p>
            </div>

            <!-- Main Content Grid -->
            <div class="grid lg:grid-cols-12 gap-8">
                <!-- Left Side - Information (4 columns) -->
                <div class="hidden lg:block lg:col-span-3 space-y-8">
                    <!-- Benefits Card -->
                    <div
                        class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8"
                    >
                        <h3 class="text-2xl font-black text-gray-900 mb-6">
                            Mengapa Xet Hospital?
                        </h3>
                        <div class="space-y-6">
                            <div
                                v-for="benefit in benefits"
                                :key="benefit.title"
                                class="flex items-start gap-4 p-4 rounded-l bg-blue-50/50 hover:bg-blue-100/50 transition-colors duration-300"
                            >
                                <div class="text-2xl flex-shrink-0">
                                    {{ benefit.icon }}
                                </div>
                                <div>
                                    <h4
                                        class="font-semibold text-gray-900 mb-1"
                                    >
                                        {{ benefit.title }}
                                    </h4>
                                    <p class="text-sm text-gray-600">
                                        {{ benefit.description }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Card -->
                    <!-- <div
                        class="bg-gradient-to-br from-blue-600 to-cyan-500 rounded-3xl p-8 text-white"
                    >
                        <h3 class="text-2xl font-black mb-6">Our Excellence</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div
                                v-for="stat in hospitalStats"
                                :key="stat.label"
                                class="text-center p-4 bg-white/10 rounded-l backdrop-blur-sm"
                            >
                                <div class="text-2xl font-black mb-1">
                                    {{ stat.number }}
                                </div>
                                <div class="text-sm text-blue-100">
                                    {{ stat.label }}
                                </div>
                            </div>
                        </div>
                    </div> -->

                    <!-- Emergency Contact -->
                    <div
                        class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8"
                    >
                        <h3 class="text-2xl font-black text-gray-900 mb-4">
                            Darurat?
                        </h3>
                        <p class="text-gray-600 mb-6">
                            Untuk perhatian medis segera, hubungi layanan darurat
                            kami sekarang juga.
                        </p>
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-4 p-4 bg-red-50 rounded-l border border-red-200"
                            >
                                <div
                                    class="w-12 h-12 bg-red-100 rounded-l flex items-center justify-center text-red-600"
                                >
                                    <svg
                                        class="w-6 h-6"
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
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        Saluran Darurat
                                    </h4>
                                    <p class="text-red-600 font-bold">999</p>
                                </div>
                            </div>
                            <div
                                class="flex items-center gap-4 p-4 bg-blue-50 rounded-l border border-blue-200"
                            >
                                <div
                                    class="w-12 h-12 bg-blue-100 rounded-l flex items-center justify-center text-blue-600"
                                >
                                    <svg
                                        class="w-6 h-6"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900">
                                        Dukungan 24/7
                                    </h4>
                                    <p class="text-blue-600">
                                        Selalu tersedia untuk keadaan darurat
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Contact -->
                    <div
                        class="bg-gradient-to-br from-gray-900 to-blue-900 rounded-3xl p-8 text-white"
                    >
                        <h3 class="text-2xl font-black mb-6">Info Kontak</h3>
                        <div class="space-y-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-200">
                                        Lokasi
                                    </p>
                                    <p class="font-semibold">
                                        123 Medical Avenue
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-200">Telepon</p>
                                    <p class="font-semibold">999</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-white/10 rounded-lg flex items-center justify-center"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-blue-200">Email</p>
                                    <p class="font-semibold">
                                        appointments@xethospital.com
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Appointment Form (8 columns) -->
                <div class="lg:col-span-9">
                    <div
                        class="bg-white rounded-3xl shadow-2xl border border-gray-100 p-8 lg:p-12"
                    >
                        <!-- Form Header -->
                        <div class="text-center mb-8">
                            <h2 class="text-3xl font-black text-gray-900 mb-1">
                                Buat Janji Temu
                            </h2>
                            <p class="text-gray-600">
                                Isi detail Anda dan kami akan menghubungi Anda
                                dalam 2 jam
                            </p>
                        </div>

                        <form @submit.prevent="submitForm" class="space-y-8">
                            <!-- Appointment Details -->
                            <div>
                                <h3
                                    class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-3"
                                >
                                    <div
                                        class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600"
                                    >
                                        📅
                                    </div>
                                    Detail Janji Temu
                                </h3>

                                <div class="grid md:grid-cols-2 gap-6 mt-5">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Spesialisasi Medis *
                                        </label>
                                        <select
                                            v-model="form.speciality"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option value="">
                                                Pilih Spesialisasi
                                            </option>
                                            <option
                                                v-for="specialty in specialties"
                                                :key="specialty.value"
                                                :value="specialty.value"
                                            >
                                                {{ specialty.icon }}
                                                {{ specialty.label }}
                                            </option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Dokter Pilihan
                                        </label>
                                        <select
                                            v-model="form.doctorId"
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option value="">
                                                Pilih Dokter (Opsional)
                                            </option>
                                            <option
                                                v-for="doctor in filteredDoctors"
                                                :key="doctor.id"
                                                :value="doctor.id"
                                            >
                                                {{ doctor.user.name }} -
                                                {{ doctor.speciality }}
                                            </option>
                                        </select>
                                        <!-- <p class="text-sm text-gray-500">
                                            You can leave this blank and we'll
                                            assign the best available doctor
                                        </p> -->
                                    </div>
                                </div>
                                <div class="grid md:grid-cols-2 gap-6 mt-5">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Tanggal yang Diinginkan *
                                        </label>
                                        <select
                                            v-model="form.preferredDate"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option value="">
                                                Pilih Tanggal
                                            </option>
                                            <option
                                                v-for="date in availableDates"
                                                :key="date.value"
                                                :value="date.value"
                                                :disabled="
                                                    date.status ===
                                                    'Penuh'
                                                "
                                                :class="
                                                    date.status ===
                                                    'Penuh'
                                                        ? 'text-red-500'
                                                        : date.status ===
                                                          'Terbatas'
                                                        ? 'text-yellow-600'
                                                        : ''
                                                "
                                            >
                                                {{ date.label }}
                                                {{
                                                    date.status
                                                        ? `(${date.status})`
                                                        : ""
                                                }}
                                            </option>
                                        </select>
                                        <p
                                            v-if="!form.doctorId"
                                            class="text-sm text-gray-500 mt-1"
                                        >
                                             Silakan pilih dokter terlebih dahulu untuk melihat
                                             tanggal yang tersedia
                                        </p>
                                        <p
                                            v-else-if="
                                                availableDates.length === 0
                                            "
                                            class="text-sm text-red-500 mt-1"
                                        >
                                             Tidak ada tanggal yang tersedia untuk dokter ini
                                        </p>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Waktu yang Diinginkan *
                                        </label>
                                        <select
                                            v-model="form.preferredTime"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option value="">
                                                Pilih Slot Waktu
                                            </option>
                                            <option
                                                v-for="time in availableTimeSlots"
                                                :key="time"
                                                :value="time"
                                            >
                                                {{ time }}
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Personal Information -->
                            <div>
                                <h3
                                    class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-3"
                                >
                                    <div
                                        class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center text-blue-600"
                                    >
                                        👤
                                    </div>
                                    Data Pribadi
                                </h3>
                                <div class="grid md:grid-cols-2 gap-6">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Nama Depan *
                                        </label>
                                        <input
                                            v-model="form.firstName"
                                            type="text"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 placeholder-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            placeholder="Masukkan nama depan Anda"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Nama Belakang *
                                        </label>
                                        <input
                                            v-model="form.lastName"
                                            type="text"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 placeholder-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            placeholder="Masukkan nama belakang Anda"
                                        />
                                    </div>
                                </div>
                                <div class="grid md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Alamat Email *
                                        </label>
                                        <input
                                            v-model="form.email"
                                            type="email"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 placeholder-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            placeholder="your@email.com"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Nomor Telepon *
                                        </label>
                                        <input
                                            v-model="form.phone"
                                            type="tel"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 placeholder-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            placeholder="+88 (013) 1235-4567"
                                        />
                                    </div>
                                </div>
                                <div class="grid md:grid-cols-2 gap-6 mt-6">
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Jenis Kelamin *
                                        </label>
                                        <select
                                            v-model="form.gender"
                                            required
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed"
                                        >
                                            <option value="">
                                                Pilih Jenis Kelamin
                                            </option>
                                            <option value="male">Laki-laki</option>
                                            <option value="female">
                                                Perempuan
                                            </option>
                                            <option value="other">Lainnya</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label
                                            class="block text-sm font-semibold text-gray-700 mb-1"
                                        >
                                            Usia *
                                        </label>
                                        <input
                                            v-model="form.age"
                                            type="number"
                                            required
                                            min="1"
                                            max="99"
                                            :disabled="submitting"
                                            class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 placeholder-gray-400 disabled:bg-gray-100 disabled:cursor-not-allowed"
                                            placeholder="Masukkan usia Anda"
                                        />
                                    </div>
                                </div>
                                <div class="mt-6">
                                    <label
                                        class="block text-sm font-semibold text-gray-700 mb-1"
                                    >
                                        Catatan Tambahan
                                    </label>
                                    <textarea
                                        v-model="form.additionalNotes"
                                        :disabled="submitting"
                                        rows="4"
                                        placeholder="Silakan jelaskan gejala, alasan kunjungan, riwayat medis, atau kebutuhan khusus Anda..."
                                        class="w-full px-4 py-3 rounded-l border border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 transition-all duration-200 placeholder-gray-400 resize-none"
                                    ></textarea>
                                    <p class="text-sm text-gray-500 mt-2">
                                        Memberikan informasi detail membantu kami
                                        melayani Anda lebih baik
                                    </p>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-8 border-t border-gray-200">
                                <button
                                    type="submit"
                                    :disabled="submitting"
                                    class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white py-4 rounded-l font-bold text-lg hover:shadow-2xl transition-all duration-300 shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {{
                                        submitting
                                            ? "📋 Memesan..."
                                            : "📋 Buat Janji Sekarang"
                                    }}
                                </button>
                                <p
                                    class="text-center text-sm text-gray-600 mt-4"
                                >
                                    Dengan membuat janji, Anda menyetujui
                                    <a
                                        href="#"
                                        class="text-blue-600 hover:underline"
                                        >syarat dan ketentuan</a
                                    >
                                    dan
                                    <a
                                        href="#"
                                        class="text-blue-600 hover:underline"
                                        >kebijakan privasi</a
                                    >.
                                </p>
                            </div>
                        </form>
                    </div>

                    <!-- Additional Info -->
                    <div class="mt-8 grid md:grid-cols-3 gap-6 text-center">
                        <div
                            class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm"
                        >
                            <div
                                class="w-12 h-12 bg-green-100 rounded-l flex items-center justify-center mx-auto mb-4"
                            >
                                <svg
                                    class="w-6 h-6 text-green-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                                    />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900">
                                Aman & Rahasia
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">
                                Informasi Anda dilindungi
                            </p>
                        </div>
                        <div
                            class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm"
                        >
                            <div
                                class="w-12 h-12 bg-blue-100 rounded-l flex items-center justify-center mx-auto mb-4"
                            >
                                <svg
                                    class="w-6 h-6 text-blue-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                    />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900">
                                Respon Cepat
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">
                                Konfirmasi dalam 2 jam
                            </p>
                        </div>
                        <div
                            class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm"
                        >
                            <div
                                class="w-12 h-12 bg-purple-100 rounded-l flex items-center justify-center mx-auto mb-4"
                            >
                                <svg
                                    class="w-6 h-6 text-purple-600"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
                                    />
                                </svg>
                            </div>
                            <h4 class="font-semibold text-gray-900">
                                Dokter Ahli
                            </h4>
                            <p class="text-gray-600 text-sm mt-2">
                                Perawatan medis spesialis
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <Footer />

    <!-- Success Modal -->
    <div
        v-if="showSuccessModal"
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
        @click="closeModal"
    >
        <div
            class="bg-white rounded-xl shadow-2xl max-w-md w-full p-8 text-center relative"
            @click.stop
        >
            <!-- Close Button -->
            <button
                @click="closeModal"
                class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
            >
                <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <!-- Success Icon -->
            <div
                class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6"
            >
                <svg
                    class="w-10 h-10 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M5 13l4 4L19 7"
                    />
                </svg>
            </div>

            <!-- Title -->
            <h2 class="text-2xl font-black text-gray-900 mb-4">
                Janji Berhasil Dibuat!
            </h2>

            <!-- Message -->
            <p class="text-gray-600 mb-6 leading-relaxed">
                Konfirmasi janji temu Anda telah dikirim ke email. Anda juga
                dapat mengunduh detail janji temu Anda di sini.
            </p>

            <!-- Appointment Details -->
            <div
                v-if="successAppointment"
                class="bg-blue-50 rounded-l p-4 mb-6 text-left"
            >
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-600">ID Booking:</span>
                        <span class="font-semibold text-gray-900">{{
                            successAppointment.booking_id
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tanggal:</span>
                        <span class="font-semibold text-gray-900">{{
                            new Date(
                                successAppointment.preferred_date
                            ).toLocaleDateString("id-ID")
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Waktu:</span>
                        <span class="font-semibold text-gray-900">{{
                            successAppointment.preferred_time
                        }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Spesialisasi:</span>
                        <span class="font-semibold text-gray-900">{{
                            successAppointment.speciality
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Download Button -->
            <button
                @click="downloadAppointmentDetails(successAppointment.id)"
                class="w-full bg-gradient-to-r from-blue-600 to-cyan-500 text-white py-3 rounded-l font-bold hover:shadow-lg transition-all duration-300 mb-4"
            >
                📄 Unduh Detail Janji
            </button>

            <!-- Close Button -->
            <button
                @click="closeModal"
                class="w-full bg-gray-100 text-gray-700 py-3 rounded-l font-semibold hover:bg-gray-200 transition-all duration-300"
            >
                Tutup
            </button>
        </div>
    </div>
</template>
