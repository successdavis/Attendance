<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import CountdownTimer from '@/Components/CountdownTimer.vue'


// ✅ Accept new props for totals
const props = defineProps({
    success: { type: Boolean, default: false },
    user: {
        type: Object,
        default: () => ({ id: null, name: '', role: '', photo_url: '' })
    },
    log: {
        type: Object,
        default: () => ({ status: '', logged_at: '' })
    },
    attendees: {
        type: Array,
        default: () => []
    },
    student_count: { type: Number, default: 0 },
    staff_count:   { type: Number, default: 0 },
    signInEndTime: String,
    timeRestrictionEnabled: Boolean
})

const identifier = ref('')
const message = ref('')
const lastScan = ref(null)

if (props.success && props.user && props.log) {
    lastScan.value = {
        name: props.user.name,
        role: props.user.role,
        photo_url: props.user.photo_url,
        status: props.log.status,
        logged_at: props.log.logged_at,
    }
    message.value = `✅ ${props.user.name} ${props.log.status.replace('_', ' ')} at ${props.log.logged_at}`
}

function submitScan() {
    if (!identifier.value) return
    router.post('/attendance/scan',
        { identifier: identifier.value, method: 'rfid' },
        {
            preserveScroll: true,
            onSuccess: (page) => {
                const res = page.props
                if (res.success) {
                    lastScan.value = {
                        name: res.user.name,
                        role: res.user.role,
                        photo_url: res.user.photo_url,
                        status: res.log.status,
                        logged_at: res.log.logged_at,
                    }
                    message.value = `✅ ${res.user.name} ${res.log.status.replace('_', ' ')} at ${res.log.logged_at}`
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

const hiddenInput = ref(null)
onMounted(() => {
    if (hiddenInput.value) hiddenInput.value.focus()
    window.addEventListener('click', () => hiddenInput.value && hiddenInput.value.focus())
})

const hasScan = computed(() => !!lastScan.value)
</script>

<template>
    <div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 p-6">
        <div class="grid grid-cols-2">
            <div class="flex flex-col items-center justify-center">
                <h1 class="text-3xl font-bold text-indigo-700 mb-6">Attendance Scanner</h1>

                <CountdownTimer
                    v-if="timeRestrictionEnabled"
                    :end-time="signInEndTime"
                    finished-text="Sign-in period ended"
                    @finished="() => console.log('Sign-in closed!')"
                    class="mb-6"
                />
                <!-- Hidden input for RFID -->
                <input
                    ref="hiddenInput"
                    v-model="identifier"
                    @keyup.enter="submitScan"
                    type="text"
                    class="absolute opacity-0"
                    autofocus
                />

                <!-- Feedback message -->
                <div v-if="message" class="mb-6 text-center">
                    <p class="text-lg font-semibold" :class="message.startsWith('✅') ? 'text-green-600' : 'text-red-600'">
                        {{ message }}
                    </p>
                </div>

                <!-- Profile Card -->
                <div class="w-full max-w-sm bg-white shadow rounded-2xl p-6 text-center mb-10">
                    <!-- Avatar -->
                    <div class="mx-auto mb-4 w-32 h-32 rounded-full overflow-hidden border-4 border-indigo-300 flex items-center justify-center bg-gray-200">
                        <img v-if="hasScan" :src="lastScan.photo_url" alt="Profile Photo" class="w-full h-full object-cover" />
                        <div v-else class="animate-pulse w-full h-full flex items-center justify-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M5.121 17.804A8 8 0 0112 15a8 8 0 016.879 2.804M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                    </div>

                    <h2 v-if="hasScan" class="text-xl font-bold text-gray-800">{{ lastScan.name }}</h2>
                    <div v-else class="animate-pulse h-6 w-40 bg-gray-200 rounded mx-auto mb-2"></div>

                    <p v-if="hasScan" class="text-gray-500 capitalize">{{ lastScan.role }}</p>
                    <div v-else class="animate-pulse h-4 w-24 bg-gray-200 rounded mx-auto"></div>

                    <div class="mt-4">
                        <template v-if="hasScan">
          <span
              class="inline-block px-4 py-2 rounded-xl font-semibold text-white"
              :class="lastScan.status === 'check_in' ? 'bg-green-500' : 'bg-red-500'">
            {{ lastScan.status.replace('_',' ') }}
          </span>
                            <p class="mt-2 text-sm text-gray-600">Time: {{ lastScan.logged_at }}</p>
                        </template>
                        <div v-else class="animate-pulse h-8 w-28 bg-gray-200 rounded mx-auto"></div>
                    </div>
                </div>

                <!-- ✅ Totals Summary -->
                <div class="w-full max-w-3xl grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white shadow rounded-xl p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-700">Total Students Signed In</h3>
                        <p class="mt-2 text-3xl font-bold text-indigo-600">{{ props.student_count }}</p>
                    </div>
                    <div class="bg-white shadow rounded-xl p-6 text-center">
                        <h3 class="text-lg font-semibold text-gray-700">Total Staff Signed In</h3>
                        <p class="mt-2 text-3xl font-bold text-indigo-600">{{ props.staff_count }}</p>
                    </div>
                </div>

            </div>

            <!-- Today's Attendees Table -->
            <div class="w-full max-w-3xl bg-white shadow rounded-2xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Today's Sign-ins</h2>
                <table class="w-full border-collapse">
                    <thead>
                    <tr class="bg-indigo-100 text-indigo-800 text-left">
                        <th class="p-3 border-b">Name</th>
                        <th class="p-3 border-b">Role</th>
                        <th class="p-3 border-b">Time In</th>
                        <th class="p-3 border-b">Time Out</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="person in props.attendees" :key="person.id" class="hover:bg-gray-50">
                        <td class="p-3 border-b font-medium text-gray-700">{{ person.name }}</td>
                        <td class="p-3 border-b text-gray-600 capitalize">{{ person.role }}</td>
                        <td class="p-3 border-b text-gray-500">{{ person.sign_in_at ?? '—' }}</td>
                        <td class="p-3 border-b text-gray-500">{{ person.sign_out_at ?? '—' }}</td>
                    </tr>
                    <tr v-if="!props.attendees.length">
                        <td colspan="3" class="p-4 text-center text-gray-400">No sign-ins yet today.</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <p class="mt-10 text-gray-400 text-sm">Tap card or scan to register attendance</p>
    </div>
</template>
