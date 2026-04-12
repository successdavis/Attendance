<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ logs: AttendanceLog[]; from: string; to: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Reports', href: '/admin/reports/daily' },
    { title: 'Date Range', href: '/admin/reports/range' },
];

const fromDate = ref(props.from);
const toDate = ref(props.to);

function load() {
    router.get('/admin/reports/range', { from: fromDate.value, to: toDate.value }, { preserveState: true, replace: true });
}

// Group logs by date for display
const grouped = computed(() => {
    const map: Record<string, AttendanceLog[]> = {};
    for (const log of props.logs) {
        const date = log.logged_at.substring(0, 10);
        if (!map[date]) map[date] = [];
        map[date].push(log);
    }
    return map;
});
</script>

<template>
    <Head title="Range Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5">
            <!-- Controls -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">From</label>
                    <input type="date" v-model="fromDate" class="rounded-md border bg-background px-3 py-2 text-sm" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">To</label>
                    <input type="date" v-model="toDate" class="rounded-md border bg-background px-3 py-2 text-sm" />
                </div>
                <button @click="load" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Apply
                </button>
                <span class="ml-auto text-sm text-muted-foreground">{{ logs.length }} records</span>
            </div>

            <!-- Grouped by date -->
            <div v-for="(dayLogs, date) in grouped" :key="date" class="rounded-xl border bg-card shadow-sm">
                <div class="border-b bg-muted/40 px-5 py-3">
                    <span class="font-medium">{{ date }}</span>
                    <span class="ml-3 text-xs text-muted-foreground">{{ dayLogs.length }} scans · {{ new Set(dayLogs.map(l => l.user_id)).size }} users</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <tbody>
                            <tr v-for="log in dayLogs" :key="log.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-2.5 font-mono text-xs text-muted-foreground w-32">{{ log.logged_at.substring(11, 19) }}</td>
                                <td class="px-5 py-2.5 font-medium">{{ log.user?.name ?? '—' }}</td>
                                <td class="px-5 py-2.5">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="log.status === 'sign_in' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'"
                                    >{{ log.status === 'sign_in' ? 'In' : 'Out' }}</span>
                                </td>
                                <td class="px-5 py-2.5 text-muted-foreground">{{ log.device?.name ?? '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div v-if="!logs.length" class="rounded-xl border bg-card px-5 py-12 text-center text-muted-foreground shadow-sm">
                No attendance records in this date range.
            </div>
        </div>
    </AppLayout>
</template>
