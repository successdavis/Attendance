<script setup lang="ts">
import PhotoCapture from '@/components/PhotoCapture.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendancePolicy, type BreadcrumbItem, type User } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ user: User; policies: AttendancePolicy[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}/edit` },
];

const form = useForm({
    name:                props.user.name,
    email:               props.user.email,
    password:            '',
    role:                props.user.role,
    status:              props.user.status,
    branch:              props.user.branch ?? '',
    notes:               props.user.notes ?? '',
    policy_id:           props.user.policy_id?.toString() ?? '',
    external_student_id: props.user.external_student_id ?? '',
    program_expires_at:  props.user.program_expires_at?.substring(0, 10) ?? '',
    profile_photo:       null as File | null,
    remove_photo:        false,
});

function submit() {
    // form.patch() automatically converts to POST + _method=PATCH when files are attached,
    // which is required for multipart/form-data uploads.
    form.patch(`/admin/users/${props.user.id}`);
}

function deleteUser() {
    if (confirm(`Permanently delete ${props.user.name}? This cannot be undone.`)) {
        router.delete(`/admin/users/${props.user.id}`);
    }
}

/** When the user clicks "Remove" in PhotoCapture, also tell the server to clear the path. */
function onPhotoUpdate(file: File | null) {
    form.profile_photo = file;
    form.remove_photo  = file === null && !!props.user.profile_photo_path;
}
</script>

<template>
    <Head :title="`Edit ${user.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold">Edit {{ user.name }}</h1>
                <div class="flex gap-2">
                    <a :href="`/admin/users/${user.id}`"       class="rounded-md border px-3 py-2 text-sm hover:bg-muted">View</a>
                    <a :href="`/admin/users/${user.id}/credentials`" class="rounded-md border px-3 py-2 text-sm hover:bg-muted">Credentials</a>
                    <button @click="deleteUser" class="rounded-md border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50">Delete</button>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6 rounded-xl border bg-card p-6 shadow-sm">

                <!-- ─── Photo section ──────────────────────────────────── -->
                <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50/50 p-5 dark:border-gray-700 dark:bg-gray-800/20">
                    <PhotoCapture
                        :upload-url="`/admin/users/${user.id}/photo`"
                        :current-photo-url="user.profile_photo_url || null"
                        :model-value="form.profile_photo"
                        @update:model-value="onPhotoUpdate"
                    />
                    <p v-if="form.errors.profile_photo" class="mt-2 text-xs text-red-500">
                        {{ form.errors.profile_photo }}
                    </p>
                </div>

                <!-- ─── Core fields ────────────────────────────────────── -->
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Full Name</label>
                        <input type="text" v-model="form.name"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            :class="{ 'border-red-500': form.errors.name }" />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Email</label>
                        <input type="email" v-model="form.email"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            :class="{ 'border-red-500': form.errors.email }" />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            New Password
                            <span class="font-normal text-muted-foreground">(leave blank to keep)</span>
                        </label>
                        <input type="password" v-model="form.password"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            :class="{ 'border-red-500': form.errors.password }" />
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
                        <input type="text" v-model="form.branch"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">External Student ID</label>
                        <input type="text" v-model="form.external_student_id"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                            :class="{ 'border-red-500': form.errors.external_student_id }" />
                        <p v-if="form.errors.external_student_id" class="mt-1 text-xs text-red-500">
                            {{ form.errors.external_student_id }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Program Expires At</label>
                        <input type="date" v-model="form.program_expires_at"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Attendance Policy</label>
                        <select v-model="form.policy_id" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="">Use global settings</option>
                            <option v-for="policy in policies" :key="policy.id" :value="policy.id.toString()">
                                {{ policy.name }}
                            </option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Notes</label>
                    <textarea v-model="form.notes" rows="3"
                        class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing"
                        class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                    <a href="/admin/users" class="rounded-md border px-5 py-2 text-sm hover:bg-muted">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
