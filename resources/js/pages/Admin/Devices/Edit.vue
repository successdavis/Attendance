<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceDevice, type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{ device: AttendanceDevice }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Devices', href: '/admin/devices' },
    { title: props.device.name, href: `/admin/devices/${props.device.id}/edit` },
];

const form = useForm({
    name: props.device.name,
    type: props.device.type,
    location: props.device.location ?? '',
    branch: props.device.branch ?? '',
    ip_address: props.device.ip_address ?? '',
    status: props.device.status,
});

function submit() {
    form.patch(`/admin/devices/${props.device.id}`);
}

function rotateToken() {
    if (confirm('Rotate the API token? The current token will stop working immediately.')) {
        router.post(`/admin/devices/${props.device.id}/token`, {}, { preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="`Edit ${device.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <h1 class="text-xl font-semibold">Edit {{ device.name }}</h1>
                <button @click="rotateToken" class="rounded-md border border-amber-200 px-3 py-2 text-sm text-amber-700 hover:bg-amber-50">
                    Rotate Token
                </button>
            </div>

            <!-- Device info -->
            <div class="mb-5 rounded-lg bg-muted/40 px-4 py-3 text-sm">
                <span class="font-medium">Device UID:</span>
                <span class="ml-2 font-mono">{{ device.device_uid }}</span>
                <span class="ml-4 text-muted-foreground">Last seen: {{ device.last_seen_at ?? 'Never' }}</span>
            </div>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border bg-card p-6 shadow-sm">
                <div>
                    <label class="mb-1 block text-sm font-medium">Device Name</label>
                    <input type="text" v-model="form.name" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type</label>
                        <select v-model="form.type" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="rfid_reader">RFID Reader</option>
                            <option value="fingerprint_scanner">Fingerprint Scanner</option>
                            <option value="face_camera">Face Camera</option>
                            <option value="multi">Multi-modal</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Status</label>
                        <select v-model="form.status" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Location</label>
                        <input type="text" v-model="form.location" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Branch</label>
                        <input type="text" v-model="form.branch" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">IP Address</label>
                        <input type="text" v-model="form.ip_address" class="w-full rounded-md border bg-background px-3 py-2 text-sm font-mono" />
                        <p v-if="form.errors.ip_address" class="mt-1 text-xs text-red-500">{{ form.errors.ip_address }}</p>
                    </div>
                </div>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </button>
                    <a href="/admin/devices" class="rounded-md border px-5 py-2 text-sm hover:bg-muted">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
