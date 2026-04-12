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

function updateCountdown() {
    const end = new Date(props.endTime).getTime()
    const now = Date.now()
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
