<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type AttendanceSetting, type BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

interface GroupedSettings {
    [group: string]: AttendanceSetting[];
}

interface Props {
    settings: GroupedSettings;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Settings', href: '/admin/settings' },
];

// Build a flat form object keyed by setting key
const form = ref<Record<string, string | boolean | number>>(
    Object.values(props.settings)
        .flat()
        .reduce(
            (acc, s) => {
                if (s.type === 'boolean') {
                    acc[s.key] = s.value === '1' || s.value === 'true';
                } else {
                    acc[s.key] = s.value ?? '';
                }
                return acc;
            },
            {} as Record<string, string | boolean | number>,
        ),
);

const saving = ref(false);

function save() {
    saving.value = true;
    router.patch('/admin/settings', form.value, {
        onFinish: () => (saving.value = false),
    });
}

const groupLabels: Record<string, string> = {
    general: 'General',
    schedule: 'Schedule & Time Windows',
    sync: 'Student Sync',
    security: 'Security',
};
</script>

<template>
    <Head title="Attendance Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-3xl p-6">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Attendance Settings</h1>
                    <p class="text-sm text-muted-foreground">Configure attendance business rules. Changes take effect immediately.</p>
                </div>
                <div class="flex gap-2">
                    <a href="/admin/settings/overrides" class="inline-flex items-center rounded-md border px-3 py-2 text-sm hover:bg-muted">
                        Daily Overrides
                    </a>
                    <button
                        @click="save"
                        :disabled="saving"
                        class="inline-flex items-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-50"
                    >
                        {{ saving ? 'Saving…' : 'Save Changes' }}
                    </button>
                </div>
            </div>

            <div class="space-y-8">
                <div v-for="(groupSettings, group) in settings" :key="group" class="rounded-xl border bg-card shadow-sm">
                    <div class="border-b px-5 py-4">
                        <h2 class="font-medium">{{ groupLabels[group] ?? group }}</h2>
                    </div>
                    <div class="divide-y">
                        <div v-for="setting in groupSettings" :key="setting.key" class="flex items-center justify-between gap-4 px-5 py-4">
                            <div class="flex-1">
                                <label :for="setting.key" class="text-sm font-medium">{{ setting.label }}</label>
                                <p v-if="setting.description" class="mt-0.5 text-xs text-muted-foreground">{{ setting.description }}</p>
                            </div>
                            <div class="shrink-0">
                                <!-- Boolean toggle -->
                                <input
                                    v-if="setting.type === 'boolean'"
                                    :id="setting.key"
                                    type="checkbox"
                                    v-model="form[setting.key]"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />
                                <!-- Integer / string / time -->
                                <input
                                    v-else
                                    :id="setting.key"
                                    :type="setting.type === 'integer' ? 'number' : setting.type === 'time' ? 'time' : 'text'"
                                    v-model="form[setting.key]"
                                    class="w-48 rounded-md border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
