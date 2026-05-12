<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem, type User, type UserCredential } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';

const props = defineProps<{
    user: User;
    recentLogs: AttendanceLog[];
    credentials: UserCredential[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}` },
];

const statusColors: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-gray-100 text-gray-600',
    suspended: 'bg-red-100 text-red-700',
};

function updateStatus(newStatus: string) {
    router.post(`/admin/users/${props.user.id}/status`, { status: newStatus }, { preserveScroll: true });
}
</script>

<template>
    <Head :title="user.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6 space-y-6">
            <!-- User header -->
            <div class="flex flex-col gap-4 rounded-xl border bg-card p-5 shadow-sm sm:flex-row sm:items-start sm:justify-between">
                <!-- Avatar + name block -->
                <div class="flex items-center gap-4">
                    <!-- Profile photo -->
                    <div class="relative shrink-0">
                        <div class="h-20 w-20 overflow-hidden rounded-full border-2 border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                            <img
                                v-if="user.profile_photo_url"
                                :src="user.profile_photo_url"
                                :alt="user.name"
                                class="h-full w-full object-cover"
                            />
                            <div v-else class="flex h-full w-full items-center justify-center">
                                <svg class="h-9 w-9 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                        </div>
                        <!-- Status dot -->
                        <span
                            class="absolute bottom-0.5 right-0.5 h-3.5 w-3.5 rounded-full border-2 border-white dark:border-gray-900"
                            :class="{
                                'bg-emerald-500': user.status === 'active',
                                'bg-gray-400':    user.status === 'inactive',
                                'bg-red-500':     user.status === 'suspended',
                            }"
                            :title="user.status"
                        />
                    </div>

                    <!-- Name / email / badges -->
                    <div>
                        <h1 class="text-xl font-semibold">{{ user.name }}</h1>
                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[user.status]">
                                {{ user.status }}
                            </span>
                            <span class="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium capitalize text-purple-700">
                                {{ user.role }}
                            </span>
                            <span v-if="user.branch" class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600 dark:bg-slate-800 dark:text-slate-400">
                                {{ user.branch }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 gap-2">
                    <Link :href="`/admin/users/${user.id}/edit`" class="rounded-md border px-3 py-2 text-sm hover:bg-muted">Edit</Link>
                    <Link :href="`/admin/users/${user.id}/credentials`" class="rounded-md border px-3 py-2 text-sm hover:bg-muted">Credentials</Link>
                </div>
            </div>

            <!-- Details & status actions -->
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl border bg-card p-5 shadow-sm space-y-3">
                    <h2 class="font-medium">Profile Details</h2>
                    <div class="text-sm space-y-1.5">
                        <div class="flex justify-between"><span class="text-muted-foreground">External ID</span><span>{{ user.external_student_id ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Program Expires</span><span>{{ user.program_expires_at ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Last Sync</span><span>{{ user.external_synced_at ?? '—' }}</span></div>
                        <div class="flex justify-between"><span class="text-muted-foreground">Joined</span><span>{{ user.created_at?.substring(0, 10) }}</span></div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-5 shadow-sm space-y-3">
                    <h2 class="font-medium">Quick Actions</h2>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-if="user.status !== 'active'"
                            @click="updateStatus('active')"
                            class="rounded-md bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                        >Activate</button>
                        <button
                            v-if="user.status !== 'suspended'"
                            @click="updateStatus('suspended')"
                            class="rounded-md bg-red-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-red-700"
                        >Suspend</button>
                        <button
                            v-if="user.status !== 'inactive'"
                            @click="updateStatus('inactive')"
                            class="rounded-md border px-3 py-1.5 text-xs hover:bg-muted"
                        >Deactivate</button>
                    </div>
                    <p v-if="user.notes" class="mt-2 text-xs text-muted-foreground">{{ user.notes }}</p>
                </div>
            </div>

            <!-- Active credentials summary -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">Credentials</h2>
                    <Link :href="`/admin/users/${user.id}/credentials`" class="text-sm text-primary hover:underline">Manage →</Link>
                </div>
                <div class="px-5 py-4">
                    <div v-if="credentials.length" class="flex flex-wrap gap-2">
                        <span v-for="cred in credentials" :key="cred.id"
                            class="rounded border px-2.5 py-1 text-xs"
                            :class="cred.is_active ? 'bg-muted' : 'bg-red-50 text-red-500 line-through'"
                        >
                            {{ cred.type }}: {{ cred.label ?? cred.value.substring(0, 8) + '…' }}
                        </span>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No credentials assigned.</p>
                </div>
            </div>

            <!-- Recent logs -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <h2 class="font-medium">Recent Attendance</h2>
                    <Link :href="`/admin/reports/user/${user.id}`" class="text-sm text-primary hover:underline">Full report →</Link>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Date/Time</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Device</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in recentLogs" :key="log.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 text-muted-foreground">{{ log.logged_at }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="log.status === 'sign_in' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'"
                                    >
                                        {{ log.status === 'sign_in' ? 'Sign In' : 'Sign Out' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ log.device?.name ?? '—' }}</td>
                            </tr>
                            <tr v-if="!recentLogs.length">
                                <td colspan="3" class="px-5 py-6 text-center text-muted-foreground">No recent attendance.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
