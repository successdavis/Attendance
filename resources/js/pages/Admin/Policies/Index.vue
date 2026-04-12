<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendancePolicy, type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{ policies: AttendancePolicy[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Policies', href: '/admin/policies' },
];

const showAdd = ref(false);
const editingPolicy = ref<AttendancePolicy | null>(null);

const blankForm = () => ({
    name: '',
    description: '',
    sign_in_start: '',
    sign_in_end: '',
    sign_out_start: '',
    sign_out_end: '',
    grace_period_minutes: '',
    late_threshold_minutes: '',
    allow_multiple_daily: false,
    weekend_allowed: false,
    time_restriction_enabled: true,
    is_default: false,
});

const form = useForm(blankForm());

function openAdd() {
    editingPolicy.value = null;
    Object.assign(form, blankForm());
    showAdd.value = true;
}

function openEdit(policy: AttendancePolicy) {
    editingPolicy.value = policy;
    form.name = policy.name;
    form.description = policy.description ?? '';
    form.sign_in_start = policy.sign_in_start ?? '';
    form.sign_in_end = policy.sign_in_end ?? '';
    form.sign_out_start = policy.sign_out_start ?? '';
    form.sign_out_end = policy.sign_out_end ?? '';
    form.grace_period_minutes = policy.grace_period_minutes?.toString() ?? '';
    form.late_threshold_minutes = policy.late_threshold_minutes?.toString() ?? '';
    form.allow_multiple_daily = policy.allow_multiple_daily;
    form.weekend_allowed = policy.weekend_allowed;
    form.time_restriction_enabled = policy.time_restriction_enabled;
    form.is_default = policy.is_default;
    showAdd.value = true;
}

function submit() {
    if (editingPolicy.value) {
        form.patch(`/admin/policies/${editingPolicy.value.id}`, {
            onSuccess: () => { showAdd.value = false; editingPolicy.value = null; },
        });
    } else {
        form.post('/admin/policies', {
            onSuccess: () => { showAdd.value = false; form.reset(); },
        });
    }
}

function deletePolicy(policy: AttendancePolicy) {
    if (confirm(`Delete "${policy.name}"? Users assigned to it will revert to global settings.`)) {
        router.delete(`/admin/policies/${policy.id}`);
    }
}
</script>

<template>
    <Head title="Attendance Policies" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl p-6 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Attendance Policies</h1>
                    <p class="text-sm text-muted-foreground">Per-group schedule overrides that take precedence over global settings.</p>
                </div>
                <button @click="openAdd" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Add Policy
                </button>
            </div>

            <!-- Form panel -->
            <div v-if="showAdd" class="rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-medium">{{ editingPolicy ? 'Edit Policy' : 'New Policy' }}</h2>
                <form @submit.prevent="submit" class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.name" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Description</label>
                        <input type="text" v-model="form.description" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sign-in Start</label>
                        <input type="time" v-model="form.sign_in_start" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sign-in End</label>
                        <input type="time" v-model="form.sign_in_end" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sign-out Start</label>
                        <input type="time" v-model="form.sign_out_start" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sign-out End</label>
                        <input type="time" v-model="form.sign_out_end" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Grace Period (minutes)</label>
                        <input type="number" v-model="form.grace_period_minutes" min="0" max="120" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Late Threshold (minutes)</label>
                        <input type="number" v-model="form.late_threshold_minutes" min="0" max="480" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="flex flex-wrap gap-5 sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.time_restriction_enabled" class="rounded" /> Enable time restriction</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.allow_multiple_daily" class="rounded" /> Allow multiple daily sign-ins</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.weekend_allowed" class="rounded" /> Allow weekend attendance</label>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" v-model="form.is_default" class="rounded" /> Set as default policy</label>
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ form.processing ? 'Saving…' : editingPolicy ? 'Update Policy' : 'Create Policy' }}
                        </button>
                        <button type="button" @click="showAdd = false" class="rounded-md border px-4 py-2 text-sm hover:bg-muted">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Policies list -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Policy</th>
                                <th class="px-5 py-3 text-left">Sign-in</th>
                                <th class="px-5 py-3 text-left">Sign-out</th>
                                <th class="px-5 py-3 text-left">Grace</th>
                                <th class="px-5 py-3 text-left">Users</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="policy in policies" :key="policy.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-2 font-medium">
                                        {{ policy.name }}
                                        <span v-if="policy.is_default" class="rounded-full bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">Default</span>
                                        <span v-if="!policy.is_active" class="rounded-full bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500">Inactive</span>
                                    </div>
                                    <div v-if="policy.description" class="text-xs text-muted-foreground">{{ policy.description }}</div>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {{ policy.sign_in_start ?? '—' }} – {{ policy.sign_in_end ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {{ policy.sign_out_start ?? '—' }} – {{ policy.sign_out_end ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ policy.grace_period_minutes != null ? `${policy.grace_period_minutes}m` : '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ policy.users_count ?? 0 }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <button @click="openEdit(policy)" class="text-xs text-primary hover:underline">Edit</button>
                                        <button @click="deletePolicy(policy)" class="text-xs text-red-500 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!policies.length">
                                <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">No policies yet. Global settings apply to all users.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
