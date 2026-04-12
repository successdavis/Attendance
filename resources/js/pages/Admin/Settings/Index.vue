<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Setting {
    key: string;
    value: string | null;
    type: 'string' | 'integer' | 'boolean' | 'time' | 'json' | 'array';
    group: string;
    label: string;
    description: string | null;
}

// getAllGrouped() returns { groupName: Setting[] }
interface Props {
    settings: Record<string, Setting[]>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Settings', href: '/admin/settings' },
];

// Build a flat form object keyed by each setting's own 'key' field
const form = ref<Record<string, string | boolean | number>>(
    Object.values(props.settings)
        .flat()
        .reduce(
            (acc, s: Setting) => {
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

const page = usePage<{ flash: { success: string | null; error: string | null } }>();
const flash = computed(() => page.props.flash);

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

            <!-- Flash messages -->
            <div v-if="flash.success" class="mb-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                ✓ {{ flash.success }}
            </div>
            <div v-if="flash.error" class="mb-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ flash.error }}
            </div>

            <div class="space-y-8">
                <!--
                    settings shape: { groupName: Setting[] }
                    Each Setting object has its own .key field (added by getAllGrouped()).
                -->
                <div v-for="(groupSettings, group) in settings" :key="group" class="rounded-xl border bg-card shadow-sm">
                    <div class="border-b px-5 py-4">
                        <h2 class="font-medium">{{ groupLabels[group] ?? group }}</h2>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="setting in groupSettings"
                            :key="setting.key"
                            class="flex items-center justify-between gap-4 px-5 py-4"
                        >
                            <div class="flex-1">
                                <label :for="setting.key" class="text-sm font-medium">{{ setting.label }}</label>
                                <p v-if="setting.description" class="mt-0.5 text-xs text-muted-foreground">
                                    {{ setting.description }}
                                </p>
                            </div>

                            <div class="shrink-0">
                                <!-- Boolean toggle — v-model uses setting.key which is now always correct -->
                                <input
                                    v-if="setting.type === 'boolean'"
                                    :id="setting.key"
                                    type="checkbox"
                                    v-model="form[setting.key]"
                                    class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                />

                                <!-- JSON / array — editable as raw JSON text -->
                                <textarea
                                    v-else-if="setting.type === 'json' || setting.type === 'array'"
                                    :id="setting.key"
                                    v-model="form[setting.key]"
                                    rows="2"
                                    class="w-64 rounded-md border bg-background px-3 py-1.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary"
                                />

                                <!-- Integer / string / time -->
                                <input
                                    v-else
                                    :id="setting.key"
                                    :type="
                                        setting.type === 'integer' ? 'number'
                                        : setting.type === 'time'    ? 'time'
                                        : 'text'
                                    "
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
