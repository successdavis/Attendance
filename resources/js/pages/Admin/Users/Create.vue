<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendancePolicy, type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

defineProps<{ policies: AttendancePolicy[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: 'Create', href: '/admin/users/create' },
];

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: 'student',
    status: 'active',
    branch: '',
    notes: '',
    policy_id: '',
    external_student_id: '',
    program_expires_at: '',
});

function submit() {
    form.post('/admin/users');
}
</script>

<template>
    <Head title="Create User" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <h1 class="mb-6 text-xl font-semibold">Create User</h1>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border bg-card p-6 shadow-sm">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Full Name <span class="text-red-500">*</span></label>
                        <input type="text" v-model="form.name" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Email <span class="text-red-500">*</span></label>
                        <input type="email" v-model="form.email" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Password <span class="text-red-500">*</span></label>
                        <input type="password" v-model="form.password" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Role</label>
                        <select v-model="form.role" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="student">Student</option>
                            <option value="staff">Staff</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Status</label>
                        <select v-model="form.status" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Branch</label>
                        <input type="text" v-model="form.branch" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">External Student ID</label>
                        <input type="text" v-model="form.external_student_id" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                        <p v-if="form.errors.external_student_id" class="mt-1 text-xs text-red-500">{{ form.errors.external_student_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Program Expires At</label>
                        <input type="date" v-model="form.program_expires_at" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Attendance Policy</label>
                        <select v-model="form.policy_id" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="">Use global settings</option>
                            <option v-for="policy in policies" :key="policy.id" :value="policy.id">
                                {{ policy.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Notes</label>
                    <textarea v-model="form.notes" rows="3" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                        {{ form.processing ? 'Creating…' : 'Create User' }}
                    </button>
                    <a href="/admin/users" class="rounded-md border px-5 py-2 text-sm hover:bg-muted">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
