<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type StudentSyncLog } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface SyncConfig {
    sync_api_url: string;
    sync_schedule_enabled: boolean;
    sync_schedule_cron: string;
    sync_deactivate_missing: boolean;
    program_expiry_enforcement: boolean;
}

defineProps<{ lastSync: StudentSyncLog | null; config: SyncConfig }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Student Sync', href: '/admin/sync' },
];

const running = ref(false);

function runSync() {
    if (!confirm('Start a manual student sync now?')) return;
    running.value = true;
    router.post('/admin/sync/run', {}, {
        onFinish: () => (running.value = false),
    });
}

const statusColors: Record<string, string> = {
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    partial: 'bg-yellow-100 text-yellow-700',
    running: 'bg-blue-100 text-blue-700',
};
</script>

<template>
    <Head title="Student Sync" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Student Sync</h1>
                    <p class="text-sm text-muted-foreground">Pull active students from the external training platform.</p>
                </div>
                <div class="flex gap-2">
                    <Link href="/admin/sync/logs" class="rounded-md border px-3 py-2 text-sm hover:bg-muted">Sync Logs</Link>
                    <button
                        @click="runSync"
                        :disabled="running || !config.sync_api_url"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ running ? 'Running…' : 'Run Sync Now' }}
                    </button>
                </div>
            </div>

            <!-- API not configured warning -->
            <div v-if="!config.sync_api_url" class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-medium text-amber-900">Sync API URL not configured.</p>
                <p class="mt-1 text-xs text-amber-700">
                    Go to <Link href="/admin/settings" class="underline">Settings</Link> and set <code>sync_api_url</code> and <code>sync_api_key</code>.
                </p>
            </div>

            <!-- Last sync status -->
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-medium">Last Sync</h2>
                <div v-if="lastSync" class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[lastSync.status]">
                            {{ lastSync.status }}
                        </span>
                        <span class="text-sm text-muted-foreground">{{ lastSync.started_at }}</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 text-sm sm:grid-cols-5">
                        <div class="rounded-lg border bg-muted/30 p-3 text-center">
                            <p class="text-xs text-muted-foreground">Fetched</p>
                            <p class="text-xl font-bold">{{ lastSync.total_fetched }}</p>
                        </div>
                        <div class="rounded-lg border bg-green-50 p-3 text-center">
                            <p class="text-xs text-muted-foreground">Created</p>
                            <p class="text-xl font-bold text-green-700">{{ lastSync.total_created }}</p>
                        </div>
                        <div class="rounded-lg border bg-blue-50 p-3 text-center">
                            <p class="text-xs text-muted-foreground">Updated</p>
                            <p class="text-xl font-bold text-blue-700">{{ lastSync.total_updated }}</p>
                        </div>
                        <div class="rounded-lg border bg-yellow-50 p-3 text-center">
                            <p class="text-xs text-muted-foreground">Deactivated</p>
                            <p class="text-xl font-bold text-yellow-700">{{ lastSync.total_deactivated }}</p>
                        </div>
                        <div class="rounded-lg border bg-muted/30 p-3 text-center">
                            <p class="text-xs text-muted-foreground">Skipped</p>
                            <p class="text-xl font-bold">{{ lastSync.total_skipped }}</p>
                        </div>
                    </div>
                    <p v-if="lastSync.error_message" class="rounded-md bg-red-50 px-3 py-2 text-xs text-red-700">
                        {{ lastSync.error_message }}
                    </p>
                </div>
                <p v-else class="text-sm text-muted-foreground">No sync has been run yet.</p>
            </div>

            <!-- Sync configuration summary -->
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="font-medium">Configuration</h2>
                    <Link href="/admin/settings" class="text-sm text-primary hover:underline">Edit in Settings →</Link>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">API URL</dt>
                        <dd class="font-mono text-xs">{{ config.sync_api_url || '(not set)' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Scheduled sync</dt>
                        <dd>
                            <span :class="config.sync_schedule_enabled ? 'text-green-600' : 'text-muted-foreground'">
                                {{ config.sync_schedule_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                            <span v-if="config.sync_schedule_enabled" class="ml-2 font-mono text-xs text-muted-foreground">{{ config.sync_schedule_cron }}</span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Deactivate missing students</dt>
                        <dd :class="config.sync_deactivate_missing ? 'text-amber-600' : 'text-muted-foreground'">
                            {{ config.sync_deactivate_missing ? 'Yes' : 'No' }}
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-muted-foreground">Enforce program expiry</dt>
                        <dd :class="config.program_expiry_enforcement ? 'text-amber-600' : 'text-muted-foreground'">
                            {{ config.program_expiry_enforcement ? 'Yes' : 'No' }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </AppLayout>
</template>
