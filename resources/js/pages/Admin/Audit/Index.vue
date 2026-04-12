<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Activity {
    id: number;
    log_name: string;
    description: string;
    subject_type: string | null;
    subject_id: number | null;
    causer_type: string | null;
    causer_id: number | null;
    properties: Record<string, unknown>;
    created_at: string;
    causer?: { name: string };
    subject?: { name?: string; key?: string };
}

defineProps<{ activities: PaginatedData<Activity> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Audit Log', href: '/admin/audit' },
];

const filters = ref({
    causer_id: '',
    subject_type: '',
    from: '',
    to: '',
});

function applyFilters() {
    router.get('/admin/audit', filters.value, { preserveState: true, replace: true });
}

function subjectLabel(type: string | null): string {
    if (!type) return '—';
    const parts = type.split('\\');
    return parts[parts.length - 1];
}
</script>

<template>
    <Head title="Audit Log" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5">
            <h1 class="text-xl font-semibold">Audit Log</h1>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 rounded-xl border bg-card p-4 shadow-sm">
                <input type="number" v-model="filters.causer_id" placeholder="Admin user ID" class="rounded-md border bg-background px-3 py-2 text-sm w-36" />
                <input type="text" v-model="filters.subject_type" placeholder="Model (e.g. User)" class="rounded-md border bg-background px-3 py-2 text-sm w-40" />
                <input type="date" v-model="filters.from" class="rounded-md border bg-background px-3 py-2 text-sm" />
                <input type="date" v-model="filters.to" class="rounded-md border bg-background px-3 py-2 text-sm" />
                <button @click="applyFilters" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Filter
                </button>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Time</th>
                                <th class="px-5 py-3 text-left">Action</th>
                                <th class="px-5 py-3 text-left">By</th>
                                <th class="px-5 py-3 text-left">Subject</th>
                                <th class="px-5 py-3 text-left">Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="activity in activities.data" :key="activity.id" class="border-b last:border-0 hover:bg-muted/20 align-top">
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground whitespace-nowrap">{{ activity.created_at }}</td>
                                <td class="px-5 py-3">{{ activity.description }}</td>
                                <td class="px-5 py-3">{{ activity.causer?.name ?? `#${activity.causer_id}` }}</td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {{ subjectLabel(activity.subject_type) }}
                                    <span v-if="activity.subject_id" class="text-xs">#{{ activity.subject_id }}</span>
                                </td>
                                <td class="px-5 py-3 max-w-xs">
                                    <pre v-if="Object.keys(activity.properties ?? {}).length" class="rounded bg-muted/40 px-2 py-1 text-xs overflow-x-auto whitespace-pre-wrap break-all">{{ JSON.stringify(activity.properties, null, 2) }}</pre>
                                </td>
                            </tr>
                            <tr v-if="!activities.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-muted-foreground">No audit events found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="activities.last_page > 1" class="flex items-center justify-between border-t px-5 py-3">
                    <p class="text-xs text-muted-foreground">{{ activities.from }}–{{ activities.to }} of {{ activities.total }}</p>
                    <div class="flex gap-1">
                        <component
                            v-for="link in activities.links"
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
