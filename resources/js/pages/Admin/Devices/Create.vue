<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Devices', href: '/admin/devices' },
    { title: 'Register Device', href: '/admin/devices/create' },
];

const form = useForm({
    name: '',
    device_uid: '',
    type: 'rfid_reader',
    location: '',
    branch: '',
    ip_address: '',
});

function submit() {
    form.post('/admin/devices');
}
</script>

<template>
    <Head title="Register Device" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-6">
            <h1 class="mb-6 text-xl font-semibold">Register Device</h1>

            <form @submit.prevent="submit" class="space-y-5 rounded-xl border bg-card p-6 shadow-sm">
                <div>
                    <label class="mb-1 block text-sm font-medium">Device Name <span class="text-red-500">*</span></label>
                    <input type="text" v-model="form.name" placeholder="e.g. Front Door Reader" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Device UID <span class="text-red-500">*</span></label>
                    <input type="text" v-model="form.device_uid" placeholder="Unique hardware identifier" class="w-full rounded-md border bg-background px-3 py-2 text-sm font-mono" required />
                    <p class="mt-1 text-xs text-muted-foreground">This must match the UID reported by the physical device.</p>
                    <p v-if="form.errors.device_uid" class="mt-1 text-xs text-red-500">{{ form.errors.device_uid }}</p>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Type <span class="text-red-500">*</span></label>
                    <select v-model="form.type" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                        <option value="rfid_reader">RFID Reader</option>
                        <option value="fingerprint_scanner">Fingerprint Scanner</option>
                        <option value="face_camera">Face Camera</option>
                        <option value="multi">Multi-modal</option>
                    </select>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Location</label>
                        <input type="text" v-model="form.location" placeholder="e.g. Main Entrance" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Branch</label>
                        <input type="text" v-model="form.branch" placeholder="e.g. Main Campus" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">IP Address</label>
                        <input type="text" v-model="form.ip_address" placeholder="192.168.1.100" class="w-full rounded-md border bg-background px-3 py-2 text-sm font-mono" />
                        <p v-if="form.errors.ip_address" class="mt-1 text-xs text-red-500">{{ form.errors.ip_address }}</p>
                    </div>
                </div>

                <p class="rounded-md bg-blue-50 px-4 py-3 text-xs text-blue-700">
                    After registration, an API token will be displayed <strong>once</strong>. Copy it immediately — it cannot be recovered.
                </p>

                <div class="flex gap-3 pt-2">
                    <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                        {{ form.processing ? 'Registering…' : 'Register Device' }}
                    </button>
                    <a href="/admin/devices" class="rounded-md border px-5 py-2 text-sm hover:bg-muted">Cancel</a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
