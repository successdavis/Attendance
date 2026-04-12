<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceLog, type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Correction {
    id: number;
    attendance_log_id: number | null;
    user_id: number;
    action: 'create' | 'edit' | 'delete';
    original_data: Record<string, unknown> | null;
    corrected_data: Record<string, unknown>;
    reason: string;
    corrected_by: number;
    corrected_at: string;
    user?: { name: string };
    correctedBy?: { name: string };
    attendanceLog?: AttendanceLog;
}

const props = defineProps<{ corrections: PaginatedData<Correction> }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Corrections', href: '/admin/corrections' },
];

const showCreate = ref(false);

const createForm = useForm({
    user_id: '',
    logged_at: '',
    status: 'sign_in',
    location: '',
    reason: '',
});

function submitCreate() {
    createForm.post('/admin/corrections', {
        onSuccess: () => {
            showCreate.value = false;
            createForm.reset();
        },
    });
}

const actionColors: Record<string, string> = {
    create: 'bg-green-100 text-green-700',
    edit: 'bg-blue-100 text-blue-700',
    delete: 'bg-red-100 text-red-700',
};
</script>

<template>
    <Head title="Attendance Corrections" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-5">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Attendance Corrections</h1>
                    <p class="text-sm text-muted-foreground">Manual attendance edits, additions, and deletions with full audit trail.</p>
                </div>
                <button @click="showCreate = !showCreate" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Add Manual Record
                </button>
            </div>

            <!-- Create form -->
            <div v-if="showCreate" class="rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-medium">Manual Attendance Record</h2>
                <form @submit.prevent="submitCreate" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">User ID <span class="text-red-500">*</span></label>
                        <input type="number" v-model="createForm.user_id" placeholder="User ID" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="createForm.errors.user_id" class="mt-1 text-xs text-red-500">{{ createForm.errors.user_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date/Time <span class="text-red-500">*</span></label>
                        <input type="datetime-local" v-model="createForm.logged_at" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="createForm.errors.logged_at" class="mt-1 text-xs text-red-500">{{ createForm.errors.logged_at }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type</label>
                        <select v-model="createForm.status" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="sign_in">Sign In</option>
                            <option value="sign_out">Sign Out</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Location</label>
                        <input type="text" v-model="createForm.location" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Reason <span class="text-red-500">*</span></label>
                        <input type="text" v-model="createForm.reason" placeholder="Why is this record being added?" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="createForm.errors.reason" class="mt-1 text-xs text-red-500">{{ createForm.errors.reason }}</p>
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" :disabled="createForm.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ createForm.processing ? 'Adding…' : 'Add Record' }}
                        </button>
                        <button type="button" @click="showCreate = false" class="rounded-md border px-4 py-2 text-sm hover:bg-muted">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Corrections table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">When</th>
                                <th class="px-5 py-3 text-left">Action</th>
                                <th class="px-5 py-3 text-left">User Affected</th>
                                <th class="px-5 py-3 text-left">Corrected By</th>
                                <th class="px-5 py-3 text-left">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="c in corrections.data" :key="c.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground">{{ c.corrected_at }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="actionColors[c.action]">
                                        {{ c.action }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">{{ c.user?.name ?? `User #${c.user_id}` }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ c.correctedBy?.name ?? '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground max-w-xs truncate">{{ c.reason }}</td>
                            </tr>
                            <tr v-if="!corrections.data.length">
                                <td colspan="5" class="px-5 py-10 text-center text-muted-foreground">No corrections recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="corrections.last_page > 1" class="flex items-center justify-between border-t px-5 py-3">
                    <p class="text-xs text-muted-foreground">{{ corrections.from }}–{{ corrections.to }} of {{ corrections.total }}</p>
                    <div class="flex gap-1">
                        <component
                            v-for="link in corrections.links"
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
