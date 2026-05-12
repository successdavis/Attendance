<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type AttendanceLog, type User } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Activity, MonitorSpeaker, UserCheck, Users } from 'lucide-vue-next';

interface Stats {
    totalUsers: number;
    activeUsers: number;
    todayScans: number;
    todayPresent: number;
    activeDevices: number;
    lastSync: string | null;
}

interface Props {
    stats: Stats;
    recentLogs: AttendanceLog[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Dashboard', href: '/admin' },
];

function formatDateTime(value: string | null): string {
    if (!value) return 'Never';
    const d = new Date(value);
    if (isNaN(d.getTime())) return value;

    const now = new Date();
    const isToday =
        d.getDate() === now.getDate() &&
        d.getMonth() === now.getMonth() &&
        d.getFullYear() === now.getFullYear();

    const time = d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true });

    if (isToday) return `Today, ${time}`;

    const yesterday = new Date(now);
    yesterday.setDate(now.getDate() - 1);
    const isYesterday =
        d.getDate() === yesterday.getDate() &&
        d.getMonth() === yesterday.getMonth() &&
        d.getFullYear() === yesterday.getFullYear();

    if (isYesterday) return `Yesterday, ${time}`;

    return d.toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' }) + ', ' + time;
}
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4">
            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <Users class="h-8 w-8 text-primary" />
                        <div>
                            <p class="text-sm text-muted-foreground">Total Users</p>
                            <p class="text-2xl font-bold">{{ stats.totalUsers }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">{{ stats.activeUsers }} active</p>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <UserCheck class="h-8 w-8 text-green-500" />
                        <div>
                            <p class="text-sm text-muted-foreground">Today Present</p>
                            <p class="text-2xl font-bold">{{ stats.todayPresent }}</p>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">{{ stats.todayScans }} total scans today</p>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <MonitorSpeaker class="h-8 w-8 text-blue-500" />
                        <div>
                            <p class="text-sm text-muted-foreground">Active Devices</p>
                            <p class="text-2xl font-bold">{{ stats.activeDevices }}</p>
                        </div>
                    </div>
                    <Link href="/admin/devices" class="mt-2 block text-xs text-primary hover:underline">Manage devices →</Link>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <Activity class="h-8 w-8 text-purple-500" />
                        <div>
                            <p class="text-sm text-muted-foreground">Last Student Sync</p>
                            <p class="text-sm font-semibold">{{ formatDateTime(stats.lastSync) }}</p>
                        </div>
                    </div>
                    <Link href="/admin/sync" class="mt-2 block text-xs text-primary hover:underline">Run sync →</Link>
                </div>
            </div>

            <!-- Recent Logs -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-semibold">Recent Attendance</h2>
                    <Link href="/admin/reports/daily" class="text-sm text-primary hover:underline">View full report →</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">User</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Time</th>
                                <th class="px-5 py-3 text-left">Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in recentLogs" :key="log.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 font-medium">{{ log.user?.name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="log.status === 'sign_in' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ log.status === 'sign_in' ? 'Sign In' : 'Sign Out' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ formatDateTime(log.logged_at) }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ log.device?.name ?? '—' }}</td>
                            </tr>
                            <tr v-if="!recentLogs.length">
                                <td colspan="4" class="px-5 py-8 text-center text-muted-foreground">No attendance records today.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
