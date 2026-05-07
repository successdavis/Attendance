<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import CountdownTimer from '@/components/CountdownTimer.vue'

// ─── Props (server-rendered by showScan()) ────────────────────────────────────
const props = defineProps({
    attendees:               { type: Array,   default: () => [] },
    student_count:           { type: Number,  default: 0 },
    staff_count:             { type: Number,  default: 0 },
    signInEndTime:           { type: String,  default: null },
    timeRestrictionEnabled:  { type: Boolean, default: false },
})

// ─── State ────────────────────────────────────────────────────────────────────
const identifier  = ref('')
const scanning    = ref(false)
const feedback    = ref(null)   // null | { type: 'success'|'error', message, user, log }
const attendees   = ref([...props.attendees])
const studentCount = ref(props.student_count)
const staffCount   = ref(props.staff_count)

let feedbackTimer = null

// ─── Scan submission (plain fetch — NOT Inertia router) ───────────────────────
async function submitScan() {
    const id = identifier.value.trim()
    if (!id || scanning.value) return

    scanning.value    = true
    identifier.value  = ''
    clearFeedback()

    try {
        const res = await fetch('/attendance/scan', {
            method:  'POST',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     getCsrf(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ identifier: id, method: 'rfid' }),
        })

        const data = await res.json()

        if (res.ok && data.success) {
            feedback.value = {
                type:    'success',
                message: data.message,
                user:    data.user,
                log:     data.log,
            }
            updateAttendeeList(data.user, data.log)
        } else {
            feedback.value = {
                type:    'error',
                message: data.message || 'Scan failed. Please try again.',
                user:    null,
                log:     null,
            }
        }
    } catch {
        feedback.value = {
            type:    'error',
            message: 'Network error. Please check the connection.',
            user:    null,
            log:     null,
        }
    } finally {
        scanning.value = false
        refocusInput()
        // Auto-dismiss feedback after 6 seconds
        feedbackTimer = setTimeout(clearFeedback, 6000)
    }
}

// ─── Attendee list — update client-side so we don't need a full page reload ──
function updateAttendeeList(user, log) {
    const existing = attendees.value.findIndex(a => a.id === user.id)
    const isCheckIn = log.status === 'check_in'

    if (existing >= 0) {
        const entry = { ...attendees.value[existing] }
        entry.status = log.status
        if (isCheckIn && !entry.sign_in_at) {
            entry.sign_in_at = log.logged_at
        }
        if (!isCheckIn) {
            entry.sign_out_at = log.logged_at
        }
        attendees.value.splice(existing, 1, entry)
    } else {
        // New attendee for today
        attendees.value.unshift({
            id:          user.id,
            name:        user.name,
            role:        user.role,
            photo_url:   user.photo_url,
            sign_in_at:  isCheckIn ? log.logged_at : null,
            sign_out_at: isCheckIn ? null : log.logged_at,
            status:      log.status,
        })
        if (user.role === 'student') studentCount.value++
        else staffCount.value++
    }

    // Re-sort: checked-in first, then alphabetical
    attendees.value.sort((a, b) =>
        (b.status === 'check_in' ? 1 : 0) - (a.status === 'check_in' ? 1 : 0)
        || a.name.localeCompare(b.name)
    )
}

// ─── Helpers ──────────────────────────────────────────────────────────────────
function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
}

function clearFeedback() {
    clearTimeout(feedbackTimer)
    feedback.value = null
}

const hiddenInput = ref(null)

function refocusInput() {
    hiddenInput.value?.focus()
}

onMounted(() => {
    refocusInput()
    // Any click on the page re-focuses the hidden RFID input
    window.addEventListener('click', refocusInput)
})

onUnmounted(() => {
    window.removeEventListener('click', refocusInput)
    clearTimeout(feedbackTimer)
})

// ─── Computed ─────────────────────────────────────────────────────────────────
const currentTime = ref(new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' }))
let clockTimer = null

onMounted(() => {
    clockTimer = setInterval(() => {
        currentTime.value = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' })
    }, 1000)
})
onUnmounted(() => clearInterval(clockTimer))

const totalPresent = computed(() => studentCount.value + staffCount.value)
</script>

<template>
    <div class="min-h-screen flex flex-col bg-slate-950 text-white" @click="refocusInput">

        <!-- Hidden RFID capture input — always focused -->
        <input
            ref="hiddenInput"
            v-model="identifier"
            @keyup.enter="submitScan"
            type="text"
            class="absolute opacity-0 pointer-events-none"
            autocomplete="off"
            autofocus
        />

        <!-- ─── Top bar ─────────────────────────────────────────────────── -->
        <header class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
            <div>
                <h1 class="text-lg font-semibold tracking-wide text-white">Attendance Scanner</h1>
                <p class="text-xs text-slate-400">Tap RFID card to mark attendance</p>
            </div>
            <div class="text-right">
                <p class="text-2xl font-mono font-bold text-white">{{ currentTime }}</p>
                <p class="text-xs text-slate-400">{{ new Date().toLocaleDateString('en-GB', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</p>
            </div>
        </header>

        <!-- ─── Main content ────────────────────────────────────────────── -->
        <div class="flex flex-1 overflow-hidden">

            <!-- Left panel: scan result + stats -->
            <div class="flex w-80 shrink-0 flex-col gap-4 border-r border-slate-800 bg-slate-900 p-5">

                <!-- Countdown timer -->
                <div v-if="timeRestrictionEnabled && signInEndTime" class="rounded-lg border border-amber-500/30 bg-amber-500/10 p-3 text-center">
                    <p class="mb-1 text-xs font-medium text-amber-400 uppercase tracking-wide">Sign-in closes in</p>
                    <CountdownTimer
                        :end-time="signInEndTime"
                        finished-text="Sign-in period closed"
                    />
                </div>

                <!-- Scan feedback card -->
                <div class="flex-1 flex items-center justify-center">
                    <!-- Idle state -->
                    <div v-if="!scanning && !feedback" class="flex flex-col items-center gap-4 text-center">
                        <!-- Pulse ring -->
                        <div class="relative">
                            <div class="h-28 w-28 rounded-full border-2 border-slate-700 bg-slate-800 flex items-center justify-center">
                                <svg class="h-14 w-14 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                                        d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/>
                                </svg>
                            </div>
                            <span class="absolute inset-0 rounded-full animate-ping border border-slate-600 opacity-30"></span>
                        </div>
                        <p class="text-sm text-slate-400">Waiting for scan…</p>
                    </div>

                    <!-- Scanning spinner -->
                    <div v-else-if="scanning" class="flex flex-col items-center gap-4 text-center">
                        <div class="h-28 w-28 rounded-full border-2 border-blue-500 bg-slate-800 flex items-center justify-center">
                            <svg class="h-10 w-10 animate-spin text-blue-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                        </div>
                        <p class="text-sm text-blue-300 animate-pulse">Processing…</p>
                    </div>

                    <!-- Result card -->
                    <div v-else-if="feedback" class="w-full rounded-xl border p-5 text-center transition-all"
                        :class="feedback.type === 'success'
                            ? 'border-green-500/40 bg-green-500/10'
                            : 'border-red-500/40 bg-red-500/10'">

                        <!-- Success: show user avatar + name -->
                        <template v-if="feedback.type === 'success' && feedback.user">
                            <!-- Avatar -->
                            <div class="mx-auto mb-3 h-24 w-24 overflow-hidden rounded-full border-4"
                                :class="feedback.log.status === 'check_in' ? 'border-green-500' : 'border-slate-400'">
                                <img v-if="feedback.user.photo_url"
                                    :src="feedback.user.photo_url"
                                    :alt="feedback.user.name"
                                    class="h-full w-full object-cover" />
                                <div v-else class="h-full w-full bg-slate-700 flex items-center justify-center text-3xl font-bold text-slate-300">
                                    {{ feedback.user.name.charAt(0).toUpperCase() }}
                                </div>
                            </div>

                            <h2 class="text-base font-bold text-white">{{ feedback.user.name }}</h2>
                            <p class="mb-3 text-xs capitalize text-slate-400">{{ feedback.user.role }}</p>

                            <!-- Status badge -->
                            <span class="inline-block rounded-full px-4 py-1.5 text-sm font-semibold"
                                :class="feedback.log.status === 'check_in'
                                    ? 'bg-green-500 text-white'
                                    : 'bg-slate-500 text-white'">
                                {{ feedback.log.status === 'check_in' ? '✓ Checked In' : '✓ Checked Out' }}
                            </span>

                            <p class="mt-2 text-xs text-slate-400">at {{ feedback.log.logged_at }}</p>
                        </template>

                        <!-- Error -->
                        <template v-else>
                            <div class="mx-auto mb-3 h-16 w-16 rounded-full bg-red-500/20 flex items-center justify-center">
                                <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-red-300">{{ feedback.message }}</p>
                        </template>

                        <!-- Dismiss -->
                        <button @click.stop="clearFeedback"
                            class="mt-4 text-xs text-slate-500 hover:text-slate-300 underline">
                            Dismiss
                        </button>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-3 gap-2">
                    <div class="rounded-lg bg-slate-800 p-3 text-center">
                        <p class="text-xl font-bold text-white">{{ totalPresent }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Present</p>
                    </div>
                    <div class="rounded-lg bg-slate-800 p-3 text-center">
                        <p class="text-xl font-bold text-indigo-400">{{ studentCount }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Students</p>
                    </div>
                    <div class="rounded-lg bg-slate-800 p-3 text-center">
                        <p class="text-xl font-bold text-amber-400">{{ staffCount }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">Staff</p>
                    </div>
                </div>
            </div>

            <!-- Right panel: today's attendee list -->
            <div class="flex flex-1 flex-col overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-800 px-5 py-3">
                    <h2 class="text-sm font-medium text-slate-300">Today's Attendance</h2>
                    <button
                        @click.stop="router.reload({ only: ['attendees', 'student_count', 'staff_count'] })"
                        class="rounded-md px-3 py-1.5 text-xs text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-colors"
                    >
                        ↻ Refresh
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-slate-900 border-b border-slate-800 text-xs uppercase text-slate-500">
                            <tr>
                                <th class="px-5 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Role</th>
                                <th class="px-4 py-3 text-left">Check In</th>
                                <th class="px-4 py-3 text-left">Check Out</th>
                                <th class="px-4 py-3 text-left">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="person in attendees"
                                :key="person.id"
                                class="border-b border-slate-800/60 hover:bg-slate-800/40 transition-colors"
                                :class="{ 'opacity-50': person.status === 'check_out' }"
                            >
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 shrink-0 rounded-full overflow-hidden bg-slate-700 flex items-center justify-center text-xs font-bold text-slate-300">
                                            <img v-if="person.photo_url" :src="person.photo_url" :alt="person.name" class="h-full w-full object-cover" />
                                            <span v-else>{{ person.name.charAt(0).toUpperCase() }}</span>
                                        </div>
                                        <span class="font-medium text-slate-200">{{ person.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 capitalize text-slate-400 text-xs">{{ person.role }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-green-400">{{ person.sign_in_at ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-400">{{ person.sign_out_at ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="person.status === 'check_in'
                                            ? 'bg-green-500/20 text-green-400'
                                            : 'bg-slate-700 text-slate-400'">
                                        {{ person.status === 'check_in' ? 'In' : 'Out' }}
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="!attendees.length">
                                <td colspan="5" class="px-5 py-16 text-center text-slate-500">
                                    No sign-ins yet today.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ─── Bottom bar ──────────────────────────────────────────────── -->
        <footer class="border-t border-slate-800 px-6 py-3 flex items-center justify-between">
            <p class="text-xs text-slate-500">Tap card or scan RFID to mark attendance · Click anywhere to refocus</p>
            <div class="flex items-center gap-2">
                <span class="h-2 w-2 rounded-full" :class="scanning ? 'bg-blue-400 animate-pulse' : 'bg-green-500'"></span>
                <span class="text-xs text-slate-500">{{ scanning ? 'Processing' : 'Ready' }}</span>
            </div>
        </footer>

    </div>
</template>
