<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceDevice, type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

defineProps<{ devices: AttendanceDevice[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Devices', href: '/admin/devices' },
];

const page = usePage<{ flash: { newDeviceToken: string | null; newDeviceName: string | null } }>();
const newToken = computed(() => page.props.flash.newDeviceToken);
const newTokenName = computed(() => page.props.flash.newDeviceName);
const tokenCopied = ref(false);

function copyToken() {
    if (newToken.value) {
        navigator.clipboard.writeText(newToken.value);
        tokenCopied.value = true;
        setTimeout(() => (tokenCopied.value = false), 2000);
    }
}

function rotateToken(deviceId: number, deviceName: string) {
    if (confirm(`Rotate the API token for "${deviceName}"? The current token will stop working immediately.`)) {
        router.post(`/admin/devices/${deviceId}/token`, {}, { preserveScroll: true });
    }
}

function deleteDevice(deviceId: number, deviceName: string) {
    if (confirm(`Delete "${deviceName}"? This cannot be undone.`)) {
        router.delete(`/admin/devices/${deviceId}`);
    }
}

const statusColors: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-gray-100 text-gray-600',
    maintenance: 'bg-yellow-100 text-yellow-700',
};

const typeLabels: Record<string, string> = {
    rfid_reader: 'RFID Reader',
    fingerprint_scanner: 'Fingerprint Scanner',
    face_camera: 'Face Camera',
    multi: 'Multi-modal',
};

function timeAgo(dateStr: string | null): string {
    if (!dateStr) return 'Never';
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60) return 'Just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}
</script>

<template>
    <Head title="Devices" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4 space-y-4">
            <!-- Token flash banner -->
            <div v-if="newToken" class="rounded-xl border border-amber-200 bg-amber-50 p-5">
                <p class="mb-1 font-semibold text-amber-900">
                    API Token for "{{ newTokenName }}" — save this now, it will not be shown again.
                </p>
                <div class="flex items-center gap-3">
                    <code class="flex-1 rounded bg-amber-100 px-3 py-2 text-sm font-mono break-all text-amber-900">
                        {{ newToken }}
                    </code>
                    <button
                        @click="copyToken"
                        class="shrink-0 rounded-md border border-amber-300 bg-white px-3 py-2 text-xs font-medium hover:bg-amber-50"
                    >
                        {{ tokenCopied ? 'Copied!' : 'Copy' }}
                    </button>
                </div>
            </div>

            <!-- Toolbar -->
            <div class="flex items-center justify-between">
                <h1 class="text-lg font-semibold">Attendance Devices</h1>
                <Link href="/admin/devices/create" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                    Register Device
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Name</th>
                                <th class="px-5 py-3 text-left">Type</th>
                                <th class="px-5 py-3 text-left">Location</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Last Seen</th>
                                <th class="px-5 py-3 text-left">IP</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="device in devices" :key="device.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3">
                                    <div class="font-medium">{{ device.name }}</div>
                                    <div class="text-xs text-muted-foreground font-mono">{{ device.device_uid }}</div>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ typeLabels[device.type] }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ device.location ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[device.status]">
                                        {{ device.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ timeAgo(device.last_seen_at) }}</td>
                                <td class="px-5 py-3 font-mono text-xs text-muted-foreground">{{ device.last_ip ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <Link :href="`/admin/devices/${device.id}/edit`" class="text-xs text-primary hover:underline">Edit</Link>
                                        <button @click="rotateToken(device.id, device.name)" class="text-xs text-amber-600 hover:underline">Rotate Token</button>
                                        <button @click="deleteDevice(device.id, device.name)" class="text-xs text-red-500 hover:underline">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!devices.length">
                                <td colspan="7" class="px-5 py-10 text-center text-muted-foreground">
                                    No devices registered. <Link href="/admin/devices/create" class="text-primary hover:underline">Register your first device</Link>.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
