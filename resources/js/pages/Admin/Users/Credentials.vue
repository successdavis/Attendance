<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User, type UserCredential } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{ user: User; credentials: UserCredential[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
    { title: props.user.name, href: `/admin/users/${props.user.id}/edit` },
    { title: 'Credentials', href: `/admin/users/${props.user.id}/credentials` },
];

const showAdd = ref(false);

const form = useForm({
    type: 'rfid' as 'rfid' | 'fingerprint' | 'face',
    value: '',
    label: '',
});

function addCredential() {
    form.post(`/admin/users/${props.user.id}/credentials`, {
        onSuccess: () => {
            showAdd.value = false;
            form.reset();
        },
    });
}

function revokeCredential(credentialId: number) {
    if (confirm('Revoke this credential? The user will no longer be able to sign in with it.')) {
        router.delete(`/admin/users/${props.user.id}/credentials/${credentialId}`, { preserveScroll: true });
    }
}

const typeLabels: Record<string, string> = {
    rfid: 'RFID Card',
    fingerprint: 'Fingerprint',
    face: 'Face ID',
};

const typePlaceholders: Record<string, string> = {
    rfid: 'e.g. A1B2C3D4',
    fingerprint: 'Template hash',
    face: 'Face template hash',
};
</script>

<template>
    <Head :title="`${user.name} — Credentials`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">{{ user.name }} — Credentials</h1>
                    <p class="text-sm text-muted-foreground">Manage RFID cards and biometric identifiers for this user.</p>
                </div>
                <button
                    @click="showAdd = !showAdd"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    Add Credential
                </button>
            </div>

            <!-- Add form -->
            <div v-if="showAdd" class="mb-6 rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-medium">New Credential</h2>
                <form @submit.prevent="addCredential" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Type <span class="text-red-500">*</span></label>
                        <select v-model="form.type" class="w-full rounded-md border bg-background px-3 py-2 text-sm">
                            <option value="rfid">RFID Card</option>
                            <option value="fingerprint">Fingerprint</option>
                            <option value="face">Face ID</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Label</label>
                        <input type="text" v-model="form.label" placeholder="e.g. Main card" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                    </div>
                    <div class="sm:col-span-2">
                        <label class="mb-1 block text-sm font-medium">Value <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            v-model="form.value"
                            :placeholder="typePlaceholders[form.type]"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm font-mono"
                            required
                        />
                        <p v-if="form.errors.value" class="mt-1 text-xs text-red-500">{{ form.errors.value }}</p>
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ form.processing ? 'Adding…' : 'Add Credential' }}
                        </button>
                        <button type="button" @click="showAdd = false" class="rounded-md border px-4 py-2 text-sm hover:bg-muted">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Credentials list -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Type</th>
                                <th class="px-5 py-3 text-left">Label</th>
                                <th class="px-5 py-3 text-left">Value</th>
                                <th class="px-5 py-3 text-left">Enrolled</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="cred in credentials" :key="cred.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3">
                                    <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        {{ typeLabels[cred.type] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ cred.label ?? '—' }}</td>
                                <td class="px-5 py-3 font-mono text-xs">{{ cred.value }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ cred.enrolled_at }}</td>
                                <td class="px-5 py-3">
                                    <span
                                        class="rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="cred.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                                    >
                                        {{ cred.is_active ? 'Active' : 'Revoked' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <button
                                        v-if="cred.is_active"
                                        @click="revokeCredential(cred.id)"
                                        class="text-xs text-red-500 hover:underline"
                                    >
                                        Revoke
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!credentials.length">
                                <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">No credentials assigned.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
