<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Override {
    date: string;
    sign_in_start: string | null;
    sign_in_end: string | null;
    sign_out_start: string | null;
    sign_out_end: string | null;
    limit_enabled: boolean;
    allow_multiple_daily: boolean;
    note: string | null;
    created_by: number | null;
}

defineProps<{ overrides: Override[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Settings', href: '/admin/settings' },
    { title: 'Daily Overrides', href: '/admin/settings/overrides' },
];

const showAdd = ref(false);

const form = useForm({
    date: '',
    sign_in_start: '',
    sign_in_end: '',
    sign_out_start: '',
    sign_out_end: '',
    limit_enabled: true,
    allow_multiple_daily: false,
    note: '',
});

function addOverride() {
    form.post('/admin/settings/overrides', {
        onSuccess: () => {
            showAdd.value = false;
            form.reset();
        },
    });
}

function deleteOverride(date: string) {
    if (confirm(`Remove override for ${date}?`)) {
        router.delete(`/admin/settings/overrides/${date}`);
    }
}
</script>

<template>
    <Head title="Daily Schedule Overrides" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Daily Schedule Overrides</h1>
                    <p class="text-sm text-muted-foreground">Override attendance time windows for specific dates (e.g., holidays, special events).</p>
                </div>
                <button
                    @click="showAdd = !showAdd"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                >
                    Add Override
                </button>
            </div>

            <!-- Add form -->
            <div v-if="showAdd" class="mb-6 rounded-xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-medium">New Override</h2>
                <form @submit.prevent="addOverride" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Date <span class="text-red-500">*</span></label>
                        <input type="date" v-model="form.date" class="w-full rounded-md border bg-background px-3 py-2 text-sm" required />
                        <p v-if="form.errors.date" class="mt-1 text-xs text-red-500">{{ form.errors.date }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Note</label>
                        <input type="text" v-model="form.note" placeholder="e.g. Public holiday" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
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
                    <div class="flex items-center gap-6 sm:col-span-2">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.limit_enabled" class="rounded" />
                            Enable time restriction
                        </label>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" v-model="form.allow_multiple_daily" class="rounded" />
                            Allow multiple sign-ins
                        </label>
                    </div>
                    <div class="flex gap-2 sm:col-span-2">
                        <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            {{ form.processing ? 'Adding…' : 'Add Override' }}
                        </button>
                        <button type="button" @click="showAdd = false" class="rounded-md border px-4 py-2 text-sm hover:bg-muted">Cancel</button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Date</th>
                                <th class="px-5 py-3 text-left">Sign-in Window</th>
                                <th class="px-5 py-3 text-left">Sign-out Window</th>
                                <th class="px-5 py-3 text-left">Flags</th>
                                <th class="px-5 py-3 text-left">Note</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="ov in overrides" :key="ov.date" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3 font-medium">{{ ov.date }}</td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {{ ov.sign_in_start ?? '—' }} – {{ ov.sign_in_end ?? '—' }}
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">
                                    {{ ov.sign_out_start ?? '—' }} – {{ ov.sign_out_end ?? '—' }}
                                </td>
                                <td class="px-5 py-3">
                                    <span v-if="!ov.limit_enabled" class="rounded bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700">No restriction</span>
                                    <span v-if="ov.allow_multiple_daily" class="rounded bg-yellow-100 px-1.5 py-0.5 text-xs text-yellow-700">Multi sign-in</span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ ov.note ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <button @click="deleteOverride(ov.date)" class="text-xs text-red-500 hover:underline">Remove</button>
                                </td>
                            </tr>
                            <tr v-if="!overrides.length">
                                <td colspan="6" class="px-5 py-8 text-center text-muted-foreground">No overrides configured.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
