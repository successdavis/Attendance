<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    user: User;
    logs: AttendanceLog[];
    summary: {
        total_days: number;
        days_this_month: number;
        last_seen: string | null;
        total_records: number;
    };
    from: string;
    to: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}` },
    { title: 'Attendance History', href: '' },
];

// ─── Date range controls ──────────────────────────────────────────────────────

const fromDate = ref(props.from);
const toDate   = ref(props.to);

function apply() {
    router.get(
        `/admin/reports/user/${props.user.id}`,
        { from: fromDate.value, to: toDate.value },
        { preserveState: true, replace: true },
    );
}

function setPreset(preset: 'week' | 'month' | 'last_month' | '3months' | 'all') {
    const today = new Date();
    const pad = (n: number) => String(n).padStart(2, '0');
    const fmt = (d: Date) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    const todayStr = fmt(today);

    const presets: Record<string, { from: string; to: string }> = {
        week: (() => {
            const mon = new Date(today);
            mon.setDate(today.getDate() - ((today.getDay() + 6) % 7)); // ISO Monday
            return { from: fmt(mon), to: todayStr };
        })(),
        month: (() => {
            return { from: `${today.getFullYear()}-${pad(today.getMonth() + 1)}-01`, to: todayStr };
        })(),
        last_month: (() => {
            const first = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const last  = new Date(today.getFullYear(), today.getMonth(), 0);
            return { from: fmt(first), to: fmt(last) };
        })(),
        '3months': (() => {
            const start = new Date(today);
            start.setMonth(today.getMonth() - 3);
            return { from: fmt(start), to: todayStr };
        })(),
        all: { from: '2000-01-01', to: todayStr },
    };

    fromDate.value = presets[preset].from;
    toDate.value   = presets[preset].to;
    apply();
}

// ─── Computed helpers ─────────────────────────────────────────────────────────

interface DayGroup {
    date: string;          // 'YYYY-MM-DD'
    entries: { signIn: AttendanceLog | null; signOut: AttendanceLog | null }[];
}

/**
 * Group logs by calendar date and pair sign_in / sign_out within each day.
 * Multiple check-in/check-out pairs in a single day each get their own row.
 */
const dayGroups = computed((): DayGroup[] => {
    const map: Record<string, AttendanceLog[]> = {};

    for (const log of props.logs) {
        const date = log.logged_at.substring(0, 10);
        (map[date] ??= []).push(log);
    }

    return Object.entries(map)
        .sort(([a], [b]) => b.localeCompare(a)) // newest first
        .map(([date, dayLogs]) => {
            const ins  = dayLogs.filter(l => l.status === 'sign_in');
            const outs = dayLogs.filter(l => l.status === 'sign_out');
            const maxRows = Math.max(ins.length, outs.length, 1);

            const entries = Array.from({ length: maxRows }, (_, i) => ({
                signIn:  ins[i]  ?? null,
                signOut: outs[i] ?? null,
            }));

            return { date, entries };
        });
});

const daysInRange = computed(() => dayGroups.value.length);

const signInsInRange = computed(() =>
    props.logs.filter(l => l.status === 'sign_in').length,
);

// ─── Format helpers ───────────────────────────────────────────────────────────

function fmtTime(dt: string | null | undefined): string {
    if (!dt) return '—';
    return new Date(dt).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function fmtDate(dateStr: string): string {
    // Parse as local date to avoid timezone shifting
    const [y, m, d] = dateStr.split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-GB', {
        weekday: 'short', day: 'numeric', month: 'short', year: 'numeric',
    });
}

function fmtDuration(signIn: AttendanceLog | null, signOut: AttendanceLog | null): string {
    if (!signIn || !signOut) return '—';
    const diff = new Date(signOut.logged_at).getTime() - new Date(signIn.logged_at).getTime();
    if (diff < 0) return '—';
    const h = Math.floor(diff / 3_600_000);
    const m = Math.floor((diff % 3_600_000) / 60_000);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

function fmtRelative(dt: string | null): string {
    if (!dt) return 'Never';
    const diff = Date.now() - new Date(dt).getTime();
    const mins  = Math.floor(diff / 60_000);
    const hours = Math.floor(mins / 60);
    const days  = Math.floor(hours / 24);
    if (mins < 1)   return 'Just now';
    if (mins < 60)  return `${mins}m ago`;
    if (hours < 24) return `${hours}h ago`;
    if (days === 1) return 'Yesterday';
    if (days < 30)  return `${days} days ago`;
    return new Date(dt).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
}

function isExpired(dateStr: string | null): boolean {
    return !!dateStr && new Date(dateStr) < new Date();
}

const statusColors: Record<string, string> = {
    active:    'bg-green-100 text-green-700',
    inactive:  'bg-gray-100 text-gray-500',
    suspended: 'bg-red-100 text-red-700',
};

const methodLabel: Record<string, string> = {
    rfid:        'RFID',
    fingerprint: 'Fingerprint',
    face:        'Face',
};
</script>

<template>
    <Head :title="`${user.name} — Attendance History`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl p-6 space-y-6">

            <!-- ─── Student profile header ──────────────────────────────── -->
            <div class="flex flex-col gap-4 rounded-xl border bg-card p-5 shadow-sm sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-semibold">{{ user.name }}</h1>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[user.status]">
                            {{ user.status }}
                        </span>
                        <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium capitalize text-purple-700">
                            {{ user.role }}
                        </span>
                    </div>
                    <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                    <div class="flex flex-wrap gap-x-5 gap-y-1 text-sm">
                        <span v-if="user.external_student_id" class="text-muted-foreground">
                            ID: <span class="font-mono text-foreground">{{ user.external_student_id }}</span>
                        </span>
                        <span v-if="user.branch" class="text-muted-foreground">
                            Branch: <span class="text-foreground">{{ user.branch }}</span>
                        </span>
                        <span v-if="user.program_expires_at" class="text-muted-foreground">
                            Program expires:
                            <span :class="isExpired(user.program_expires_at) ? 'text-red-600 font-medium' : 'text-foreground'">
                                {{ user.program_expires_at?.substring(0, 10) }}
                                <span v-if="isExpired(user.program_expires_at)">(expired)</span>
                            </span>
                        </span>
                        <span v-if="user.external_synced_at" class="text-muted-foreground">
                            Last synced: {{ fmtRelative(user.external_synced_at) }}
                        </span>
                    </div>
                </div>

                <div class="flex shrink-0 gap-2">
                    <Link :href="`/admin/users/${user.id}/edit`"
                        class="rounded-md border px-3 py-2 text-sm hover:bg-muted">
                        Edit
                    </Link>
                    <Link :href="`/admin/users/${user.id}/credentials`"
                        class="rounded-md border px-3 py-2 text-sm hover:bg-muted">
                        Credentials
                    </Link>
                </div>
            </div>

            <!-- ─── All-time stats ─────────────────────────────────────── -->
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Total Days Present</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">{{ summary.total_days }}</p>
                    <p class="text-xs text-muted-foreground">all time</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide">This Month</p>
                    <p class="mt-1 text-3xl font-bold">{{ summary.days_this_month }}</p>
                    <p class="text-xs text-muted-foreground">days attended</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Last Seen</p>
                    <p class="mt-1 text-sm font-semibold leading-tight">{{ fmtRelative(summary.last_seen) }}</p>
                    <p class="text-xs text-muted-foreground">{{ summary.last_seen?.substring(0, 10) ?? '—' }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-xs text-muted-foreground uppercase tracking-wide">Total Records</p>
                    <p class="mt-1 text-3xl font-bold">{{ summary.total_records }}</p>
                    <p class="text-xs text-muted-foreground">all time scans</p>
                </div>
            </div>

            <!-- ─── Date range controls ────────────────────────────────── -->
            <div class="rounded-xl border bg-card p-4 shadow-sm space-y-3">
                <!-- Presets -->
                <div class="flex flex-wrap gap-2">
                    <span class="text-sm font-medium text-muted-foreground self-center">Quick select:</span>
                    <button @click="setPreset('week')"
                        class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted">This Week</button>
                    <button @click="setPreset('month')"
                        class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted">This Month</button>
                    <button @click="setPreset('last_month')"
                        class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted">Last Month</button>
                    <button @click="setPreset('3months')"
                        class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted">Last 3 Months</button>
                    <button @click="setPreset('all')"
                        class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted">All Time</button>
                </div>

                <!-- Custom range -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium">From</label>
                        <input type="date" v-model="fromDate"
                            class="rounded-md border bg-background px-3 py-1.5 text-sm" />
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium">To</label>
                        <input type="date" v-model="toDate"
                            class="rounded-md border bg-background px-3 py-1.5 text-sm" />
                    </div>
                    <button @click="apply"
                        class="rounded-md bg-primary px-4 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                        Apply
                    </button>
                </div>
            </div>

            <!-- ─── Attendance log table ───────────────────────────────── -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">
                        Attendance History
                        <span class="ml-2 text-sm font-normal text-muted-foreground">
                            {{ daysInRange }} day{{ daysInRange !== 1 ? 's' : '' }},
                            {{ signInsInRange }} sign-in{{ signInsInRange !== 1 ? 's' : '' }}
                        </span>
                    </h2>
                    <span class="text-xs text-muted-foreground">{{ from }} → {{ to }}</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left w-44">Date</th>
                                <th class="px-4 py-3 text-left">Sign In</th>
                                <th class="px-4 py-3 text-left">Sign Out</th>
                                <th class="px-4 py-3 text-left">Duration</th>
                                <th class="px-4 py-3 text-left">Method</th>
                                <th class="px-4 py-3 text-left">Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="dayGroups.length">
                                <template v-for="group in dayGroups" :key="group.date">
                                    <tr v-for="(entry, ei) in group.entries" :key="ei"
                                        class="border-b last:border-0 hover:bg-muted/20">

                                        <!-- Date — only shown on the first row of each day -->
                                        <td class="px-5 py-3 align-top">
                                            <span v-if="ei === 0" class="font-medium text-foreground text-xs leading-snug">
                                                {{ fmtDate(group.date) }}
                                            </span>
                                        </td>

                                        <!-- Sign In -->
                                        <td class="px-4 py-3 align-top">
                                            <span v-if="entry.signIn" class="flex flex-col gap-0.5">
                                                <span class="font-mono font-medium text-green-700">
                                                    {{ fmtTime(entry.signIn.logged_at) }}
                                                </span>
                                                <span v-if="entry.signIn.is_manual"
                                                    class="w-fit rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">
                                                    Manual
                                                </span>
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>

                                        <!-- Sign Out -->
                                        <td class="px-4 py-3 align-top">
                                            <span v-if="entry.signOut" class="flex flex-col gap-0.5">
                                                <span class="font-mono font-medium text-slate-600">
                                                    {{ fmtTime(entry.signOut.logged_at) }}
                                                </span>
                                                <span v-if="entry.signOut.is_manual"
                                                    class="w-fit rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">
                                                    Manual
                                                </span>
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>

                                        <!-- Duration -->
                                        <td class="px-4 py-3 text-muted-foreground align-top">
                                            {{ fmtDuration(entry.signIn, entry.signOut) }}
                                        </td>

                                        <!-- Method (from whichever entry exists) -->
                                        <td class="px-4 py-3 text-muted-foreground align-top capitalize">
                                            {{ methodLabel[(entry.signIn ?? entry.signOut)?.method ?? ''] ?? '—' }}
                                        </td>

                                        <!-- Device -->
                                        <td class="px-4 py-3 text-muted-foreground align-top">
                                            {{ (entry.signIn ?? entry.signOut)?.device?.name ?? '—' }}
                                        </td>
                                    </tr>
                                </template>
                            </template>

                            <tr v-else>
                                <td colspan="6" class="px-5 py-12 text-center text-muted-foreground">
                                    No attendance records in this period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
