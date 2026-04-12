<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Summary {
    date: string;
    present: number;
    total: number;
}

defineProps<{ logs: AttendanceLog[]; summary: Summary; date: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Reports', href: '/admin/reports/daily' },
    { title: 'Daily', href: '/admin/reports/daily' },
];

const selectedDate = ref(new Date().toISOString().substring(0, 10));

function loadDate() {
    router.get('/admin/reports/daily', { date: selectedDate.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Daily Report" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5">
            <!-- Controls -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">Date</label>
                    <input type="date" v-model="selectedDate" @change="loadDate" class="rounded-md border bg-background px-3 py-2 text-sm" />
                </div>
                <div class="ml-auto flex gap-2">
                    <a :href="`/admin/reports/range?from=${date}&to=${date}`" class="rounded-md border px-3 py-2 text-sm hover:bg-muted">Range Report →</a>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-sm text-muted-foreground">Total Scans</p>
                    <p class="text-3xl font-bold">{{ summary.total }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-sm text-muted-foreground">Unique Present</p>
                    <p class="text-3xl font-bold text-green-600">{{ summary.present }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-sm text-muted-foreground">Date</p>
                    <p class="text-xl font-semibold">{{ summary.date }}</p>
                </div>
            </div>

            <!-- Logs table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Time</th>
                                <th class="px-5 py-3 text-left">User</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Device</th>
                                <th class="px-5 py-3 text-left">Location</th>
                                <th class="px-5 py-3 text-left">Method</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs" :key="log.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground">{{ log.logged_at }}</td>
                                <td class="px-5 py-3 font-medium">{{ log.user?.name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="log.status === 'sign_in' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ log.status === 'sign_in' ? 'Sign In' : 'Sign Out' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ log.device?.name ?? '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ log.location ?? '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    <span v-if="log.is_manual" class="rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">Manual</span>
                                </td>
                            </tr>
                            <tr v-if="!logs.length">
                                <td colspan="6" class="px-5 py-10 text-center text-muted-foreground">No attendance records for this date.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
