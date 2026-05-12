<script setup>
import { ref, onMounted, onUnmounted } from 'vue'

/**
 * Props:
 *  - endTime: A string or Date the timer counts down to (e.g. "2025-09-19 08:30:00").
 *  - finishedText: Optional text to show when time is up.
 */
const props = defineProps({
    endTime: { type: [String, Date], required: true },
    finishedText: { type: String, default: 'Sign-in closed' },
})

// emits "finished" when timer hits 0
const emit = defineEmits(['finished'])

const countdown = ref('')
let intervalId = null

function parseEndTime(value) {
    // If it's already a Date object, use it directly
    if (value instanceof Date) return value.getTime()

    const str = String(value).trim()

    // Bare "HH:mm" or "HH:mm:ss" — combine with today's date
    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(str)) {
        const today = new Date()
        const [hh, mm, ss = '0'] = str.split(':')
        today.setHours(Number(hh), Number(mm), Number(ss), 0)
        return today.getTime()
    }

    // Fallback: let the browser parse it (ISO strings, etc.)
    return new Date(str).getTime()
}

function updateCountdown() {
    const end  = parseEndTime(props.endTime)
    const now  = Date.now()
    const diff = end - now

    if (diff <= 0) {
        countdown.value = props.finishedText
        emit('finished')
        clearInterval(intervalId)
        return
    }

    const hrs = Math.floor(diff / (1000 * 60 * 60))
    const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60))
    const secs = Math.floor((diff % (1000 * 60)) / 1000)

    countdown.value =
        (hrs > 0 ? `${hrs}h ` : '') + `${mins}m ${secs}s`
}

onMounted(() => {
    updateCountdown()
    intervalId = setInterval(updateCountdown, 1000)
})

onUnmounted(() => clearInterval(intervalId))
</script>

<template>
    <p class="text-xl font-semibold text-red-600">
        {{ countdown }}
    </p>
</template>
