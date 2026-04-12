<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type StudentSyncLog } from '@/types';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ logs: PaginatedData<StudentSyncLog> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Student Sync', href: '/admin/sync' },
    { title: 'Logs', href: '/admin/sync/logs' },
];

const statusColors: Record<string, string> = {
    completed: 'bg-green-100 text-green-700',
    failed: 'bg-red-100 text-red-700',
    partial: 'bg-yellow-100 text-yellow-700',
    running: 'bg-blue-100 text-blue-700',
};
</script>

<template>
    <Head title="Sync Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-semibold">Sync Logs</h1>
                <Link href="/admin/sync" class="rounded-md border px-3 py-2 text-sm hover:bg-muted">← Back to Sync</Link>
            </div>

            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Started</th>
                                <th class="px-5 py-3 text-left">Trigger</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Fetched</th>
                                <th class="px-5 py-3 text-left">Created</th>
                                <th class="px-5 py-3 text-left">Updated</th>
                                <th class="px-5 py-3 text-left">Deactivated</th>
                                <th class="px-5 py-3 text-left">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="log in logs.data" :key="log.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground">{{ log.started_at }}</td>
                                <td class="px-5 py-3 capitalize">
                                    {{ log.triggered_by }}
                                    <span v-if="log.triggeredByUser" class="text-xs text-muted-foreground">({{ log.triggeredByUser.name }})</span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[log.status]">
                                        {{ log.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">{{ log.total_fetched }}</td>
                                <td class="px-5 py-3 text-green-600 font-medium">{{ log.total_created }}</td>
                                <td class="px-5 py-3 text-blue-600">{{ log.total_updated }}</td>
                                <td class="px-5 py-3 text-amber-600">{{ log.total_deactivated }}</td>
                                <td class="px-5 py-3 text-muted-foreground text-xs">
                                    <template v-if="log.finished_at">
                                        {{ Math.round((new Date(log.finished_at).getTime() - new Date(log.started_at).getTime()) / 1000) }}s
                                    </template>
                                    <template v-else>—</template>
                                </td>
                            </tr>
                            <tr v-if="!logs.data.length">
                                <td colspan="8" class="px-5 py-10 text-center text-muted-foreground">No sync runs recorded yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="logs.last_page > 1" class="flex items-center justify-between border-t px-5 py-3">
                    <p class="text-xs text-muted-foreground">{{ logs.from }}–{{ logs.to }} of {{ logs.total }}</p>
                    <div class="flex gap-1">
                        <component
                            v-for="link in logs.links"
                            :key="link.label"
                            :is="link.url ? Link : 'span'"
                            :href="link.url ?? undefined"
                            v-html="link.label"
                            class="rounded px-2.5 py-1 text-xs"
                            :class="link.active ? 'bg-primary text-primary-foreground' : link.url ? 'hover:bg-muted text-muted-foreground' : 'text-muted-foreground/40'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
