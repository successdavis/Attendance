<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Summary { date: string; present: number; total: number; }

const props = defineProps<{ logs: AttendanceLog[]; summary: Summary; date: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Reports', href: '/admin/reports/daily' },
    { title: 'Daily', href: '/admin/reports/daily' },
];

const selectedDate = ref(props.date);

function loadDate() {
    router.get('/admin/reports/daily', { date: selectedDate.value }, { preserveState: true, replace: true });
}

// ─── Group logs by user, pair check_in / check_out ────────────────────────────
interface UserRow {
    userId:   number;
    name:     string;
    pairs: { signIn: AttendanceLog | null; signOut: AttendanceLog | null }[];
}

const userRows = computed((): UserRow[] => {
    type Bucket = { name: string; ins: AttendanceLog[]; outs: AttendanceLog[] };
    const map = new Map<number, Bucket>();

    for (const log of props.logs) {
        const uid = log.user_id;
        if (!map.has(uid)) map.set(uid, { name: log.user?.name ?? `#${uid}`, ins: [], outs: [] });
        const b = map.get(uid)!;
        if (log.status === 'check_in')  b.ins.push(log);
        else                            b.outs.push(log);
    }

    return [...map.entries()]
        .sort(([, a], [, b]) => a.name.localeCompare(b.name))
        .map(([userId, { name, ins, outs }]) => ({
            userId,
            name,
            pairs: Array.from(
                { length: Math.max(ins.length, outs.length, 1) },
                (_, i) => ({ signIn: ins[i] ?? null, signOut: outs[i] ?? null }),
            ),
        }));
});

// ─── Helpers ──────────────────────────────────────────────────────────────────
function fmtDate(d: string): string {
    const [y, m, day] = d.split('-').map(Number);
    return new Date(y, m - 1, day).toLocaleDateString('en-GB', {
        weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
    });
}

function fmtTime(dt: string | null | undefined): string {
    if (!dt) return '—';
    const d = new Date(dt.includes('T') ? dt : dt.replace(' ', 'T'));
    return d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
}

function fmtDuration(signIn: AttendanceLog | null, signOut: AttendanceLog | null): string {
    if (!signIn || !signOut) return '—';
    const ms = new Date(signOut.logged_at.replace(' ', 'T')).getTime()
             - new Date(signIn.logged_at.replace(' ', 'T')).getTime();
    if (ms <= 0) return '—';
    const h = Math.floor(ms / 3_600_000);
    const m = Math.floor((ms % 3_600_000) / 60_000);
    return h > 0 ? `${h}h ${m}m` : `${m}m`;
}

const METHOD_LABEL: Record<string, string> = { rfid: 'RFID', fingerprint: 'Fingerprint', face: 'Face' };

function methodLabel(log: AttendanceLog | null): string {
    if (!log) return '—';
    if (log.is_manual) return 'Manual';
    return METHOD_LABEL[log.method ?? ''] ?? log.method ?? '—';
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
                    <input
                        type="date"
                        v-model="selectedDate"
                        @change="loadDate"
                        class="rounded-md border bg-background px-3 py-2 text-sm"
                    />
                </div>
                <div class="ml-auto flex items-center gap-2">
                    <a
                        :href="`/admin/reports/daily/export?date=${selectedDate}`"
                        class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h4a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z" />
                        </svg>
                        Export PDF
                    </a>
                    <a
                        :href="`/admin/reports/range?from=${date}&to=${date}`"
                        class="rounded-md border px-3 py-2 text-sm hover:bg-muted"
                    >
                        Range Report →
                    </a>
                </div>
            </div>

            <!-- Summary cards -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border bg-card p-5 shadow-sm text-center">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Total Scans</p>
                    <p class="mt-1 text-3xl font-bold">{{ summary.total }}</p>
                </div>
                <div class="rounded-xl border bg-card p-5 shadow-sm text-center">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">People Present</p>
                    <p class="mt-1 text-3xl font-bold text-green-600">{{ summary.present }}</p>
                </div>
                <div class="rounded-xl border bg-card p-5 shadow-sm text-center">
                    <p class="text-xs uppercase tracking-wide text-muted-foreground">Date</p>
                    <p class="mt-1 text-sm font-semibold leading-snug">{{ fmtDate(summary.date) }}</p>
                </div>
            </div>

            <!-- Attendance table grouped by user -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">Attendance Log</h2>
                    <span class="text-xs text-muted-foreground">{{ userRows.length }} people</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Name</th>
                                <th class="px-5 py-3 text-left">Check In</th>
                                <th class="px-5 py-3 text-left">Check Out</th>
                                <th class="px-5 py-3 text-left">Duration</th>
                                <th class="px-5 py-3 text-left">Method</th>
                                <th class="px-5 py-3 text-left">Device</th>
                                <th class="px-5 py-3 text-left">Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-if="userRows.length">
                                <template v-for="row in userRows" :key="row.userId">
                                    <tr
                                        v-for="(pair, pi) in row.pairs"
                                        :key="pi"
                                        class="border-b last:border-0 hover:bg-muted/20"
                                    >
                                        <!-- Name — only on first pair row -->
                                        <td class="px-5 py-3 align-top">
                                            <span v-if="pi === 0" class="font-medium">{{ row.name }}</span>
                                        </td>

                                        <!-- Check In -->
                                        <td class="px-5 py-3 align-top">
                                            <span v-if="pair.signIn" class="flex flex-col gap-0.5">
                                                <span class="font-mono font-semibold text-green-700 dark:text-green-400">
                                                    {{ fmtTime(pair.signIn.logged_at) }}
                                                </span>
                                                <span v-if="pair.signIn.is_manual"
                                                    class="w-fit rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">
                                                    Manual
                                                </span>
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>

                                        <!-- Check Out -->
                                        <td class="px-5 py-3 align-top">
                                            <span v-if="pair.signOut" class="flex flex-col gap-0.5">
                                                <span class="font-mono font-semibold text-slate-600 dark:text-slate-400">
                                                    {{ fmtTime(pair.signOut.logged_at) }}
                                                </span>
                                                <span v-if="pair.signOut.is_manual"
                                                    class="w-fit rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">
                                                    Manual
                                                </span>
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>

                                        <!-- Duration -->
                                        <td class="px-5 py-3 align-top text-muted-foreground">
                                            {{ fmtDuration(pair.signIn, pair.signOut) }}
                                        </td>

                                        <!-- Method -->
                                        <td class="px-5 py-3 align-top">
                                            <span v-if="(pair.signIn ?? pair.signOut)"
                                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                :class="(pair.signIn ?? pair.signOut)?.is_manual
                                                    ? 'bg-yellow-100 text-yellow-700'
                                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'"
                                            >
                                                {{ methodLabel(pair.signIn ?? pair.signOut) }}
                                            </span>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </td>

                                        <!-- Device -->
                                        <td class="px-5 py-3 align-top text-muted-foreground text-xs">
                                            {{ (pair.signIn ?? pair.signOut)?.device?.name ?? '—' }}
                                        </td>

                                        <!-- Location -->
                                        <td class="px-5 py-3 align-top text-muted-foreground text-xs">
                                            {{ (pair.signIn ?? pair.signOut)?.location ?? '—' }}
                                        </td>
                                    </tr>
                                </template>
                            </template>

                            <tr v-else>
                                <td colspan="7" class="px-5 py-12 text-center text-muted-foreground">
                                    No attendance records for this date.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
