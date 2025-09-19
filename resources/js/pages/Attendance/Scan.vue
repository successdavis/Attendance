<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const identifier = ref('')
const message = ref('')
const lastScan = ref<null | {
    name: string
    role: string
    photo_url: string
    status: string
    logged_at: string
}>(null)

function submitScan() {
    if (!identifier.value) return

    router.post(
        '/attendance/scan',
        { identifier: identifier.value, method: 'rfid' },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                const res: any = page.props.flash?.data ?? page.props // inertia wraps response
                if (res.success) {
                    lastScan.value = {
                        name: res.user.name,
                        role: res.user.role,
                        photo_url: res.user.photo_url,
                        status: res.log.status,
                        logged_at: res.log.logged_at,
                    }
                    message.value = `✅ ${res.user.name} ${res.log.status.replace('_',' ')} at ${res.log.logged_at}`
                } else {
                    message.value = res.message || 'Scan failed.'
                }
                identifier.value = ''
            },
            onError: (errors) => {
                message.value = Object.values(errors).join(' ')
                identifier.value = ''
            }
        }
    )
}

// Keep hidden input focused so the USB RFID reader (which acts like a keyboard) can type directly
const hiddenInput = ref<HTMLInputElement>()
onMounted(() => {
    hiddenInput.value?.focus()
    window.addEventListener('click', () => hiddenInput.value?.focus())
})
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 p-6">
        <h1 class="text-3xl font-bold text-indigo-700 mb-6">Attendance Scanner</h1>

        <!-- Hidden input to capture the card UID -->
        <input
            ref="hiddenInput"
            v-model="identifier"
            @keyup.enter="submitScan"
            type="text"
            class="absolute opacity-0"
            autofocus
        />

        <!-- Live Feedback -->
        <div v-if="message" class="mb-6 text-center">
            <p class="text-lg font-semibold" :class="message.startsWith('✅') ? 'text-green-600' : 'text-red-600'">
                {{ message }}
            </p>
        </div>

        <!-- Show last scanned user -->
        <div v-if="lastScan" class="w-full max-w-sm bg-white shadow rounded-2xl p-6 text-center">
            <img
                :src="lastScan.photo_url"
                alt="Profile Photo"
                class="mx-auto mb-4 w-32 h-32 rounded-full object-cover border-4 border-indigo-300"
            />
            <h2 class="text-xl font-bold text-gray-800">{{ lastScan.name }}</h2>
            <p class="text-gray-500 capitalize">{{ lastScan.role }}</p>
            <p class="mt-2 text-sm text-gray-600">
                Status: <span class="font-semibold capitalize">{{ lastScan.status }}</span><br>
                Time: {{ lastScan.logged_at }}
            </p>
        </div>

        <!-- Hint for operators -->
        <p class="mt-10 text-gray-400 text-sm">Tap card or scan to register attendance</p>
    </div>
</template>
