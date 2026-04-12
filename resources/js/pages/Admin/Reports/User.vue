<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem, type User } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ user: User; logs: AttendanceLog[]; from: string; to: string }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Reports', href: '/admin/reports/daily' },
    { title: props.user.name, href: `/admin/reports/user/${props.user.id}` },
];

const fromDate = ref(props.from);
const toDate = ref(props.to);

function load() {
    router.get(`/admin/reports/user/${props.user.id}`, { from: fromDate.value, to: toDate.value }, { preserveState: true, replace: true });
}

const signIns = computed(() => props.logs.filter(l => l.status === 'sign_in'));
const signOuts = computed(() => props.logs.filter(l => l.status === 'sign_out'));
</script>

<template>
    <Head :title="`${user.name} — Attendance Report`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6 space-y-5">
            <!-- User info bar -->
            <div class="flex items-center gap-4 rounded-xl border bg-card p-4 shadow-sm">
                <div class="flex-1">
                    <h1 class="font-semibold">{{ user.name }}</h1>
                    <p class="text-sm text-muted-foreground">{{ user.email }} · {{ user.branch ?? 'No branch' }}</p>
                </div>
                <a :href="`/admin/users/${user.id}`" class="text-sm text-primary hover:underline">View profile →</a>
            </div>

            <!-- Date controls -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">From</label>
                    <input type="date" v-model="fromDate" class="rounded-md border bg-background px-3 py-2 text-sm" />
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-sm font-medium">To</label>
                    <input type="date" v-model="toDate" class="rounded-md border bg-background px-3 py-2 text-sm" />
                </div>
                <button @click="load" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">Apply</button>
            </div>

            <!-- Summary -->
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-sm text-muted-foreground">Total Sign-ins</p>
                    <p class="text-3xl font-bold text-green-600">{{ signIns.length }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-sm text-muted-foreground">Total Sign-outs</p>
                    <p class="text-3xl font-bold text-slate-600">{{ signOuts.length }}</p>
                </div>
                <div class="rounded-xl border bg-card p-4 shadow-sm text-center">
                    <p class="text-sm text-muted-foreground">Total Records</p>
                    <p class="text-3xl font-bold">{{ logs.length }}</p>
                </div>
            </div>

            <!-- Log table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Date / Time</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Device</th>
                                <th class="px-5 py-3 text-left">Location</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs" :key="log.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground">{{ log.logged_at }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="log.status === 'sign_in' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ log.status === 'sign_in' ? 'Sign In' : 'Sign Out' }}
                                    </span>
                                    <span v-if="log.is_manual" class="ml-1 rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">Manual</span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ log.device?.name ?? '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ log.location ?? '—' }}</td>
                            </tr>
                            <tr v-if="!logs.length">
                                <td colspan="4" class="px-5 py-10 text-center text-muted-foreground">No records in this period.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
