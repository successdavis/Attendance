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

interface Props {
    settings: Record<string, Setting[]>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Settings', href: '/admin/settings' },
];

// ── Tabs ──────────────────────────────────────────────────────────────────────
const TABS = [
    { key: 'general',  label: 'General',                 desc: 'Core scan behavior — debounce window, allowed methods, and daily attendance rules.' },
    { key: 'schedule', label: 'Schedule & Time Windows',  desc: 'Time windows for sign-in and sign-out, grace period, late threshold, and weekend rules.' },
    { key: 'security', label: 'Security',                 desc: 'Anti-passback and other security hardening controls.' },
    { key: 'sync',     label: 'Student Sync',             desc: 'External student data pull from the training website — schedule, credentials, and deactivation.' },
];

const activeKey = ref('general');

// Derived from activeKey so template stays simple
const currentTab      = computed(() => TABS.find(t => t.key === activeKey.value) ?? TABS[0]);
const currentSettings = computed(() => props.settings[activeKey.value] ?? []);

// ── Form state ────────────────────────────────────────────────────────────────
const form = ref<Record<string, string | boolean | number>>(
    Object.values(props.settings)
        .flat()
        .reduce((acc, s: Setting) => {
            acc[s.key] = s.type === 'boolean'
                ? (s.value === '1' || s.value === 'true')
                : (s.value ?? '');
            return acc;
        }, {} as Record<string, string | boolean | number>),
);

// ── Inertia state ─────────────────────────────────────────────────────────────
const page   = usePage<{ flash: { success?: string | null; error?: string | null }; errors: Record<string, string> }>();
const flash  = computed(() => page.props.flash  ?? {});
const errors = computed(() => page.props.errors ?? {});

// ── Save ──────────────────────────────────────────────────────────────────────
const saving = ref(false);

function save() {
    const payload: Record<string, unknown> = {};

    for (const s of currentSettings.value) {
        let value: unknown = form.value[s.key];
        if ((s.type === 'json' || s.type === 'array') && typeof value === 'string') {
            try { value = JSON.parse(value); } catch { /* send as-is; backend will reject */ }
        }
        payload[s.key] = value;
    }

    saving.value = true;
    router.patch('/admin/settings', payload, {
        preserveScroll: true,
        onFinish: () => { saving.value = false; },
    });
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function toggle(key: string) {
    form.value[key] = !form.value[key];
}

function tabHasErrors(key: string): boolean {
    return (props.settings[key] ?? []).some(s => !!errors.value[s.key]);
}

const currentTabHasErrors = computed(() => tabHasErrors(activeKey.value));
</script>

<template>
    <Head title="Attendance Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl p-6">

            <!-- Page header -->
            <div class="mb-6 flex items-start justify-between">
                <div>
                    <h1 class="text-xl font-semibold">Attendance Settings</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Configure attendance business rules. Each section saves independently.
                    </p>
                </div>
                <a href="/admin/settings/overrides"
                    class="inline-flex items-center gap-1.5 rounded-md border px-3 py-2 text-sm hover:bg-muted">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Daily Overrides
                </a>
            </div>

            <!-- Flash: success -->
            <div v-if="flash.success"
                class="mb-5 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
                {{ flash.success }}
            </div>

            <!-- Flash: generic error -->
            <div v-if="flash.error"
                class="mb-5 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                {{ flash.error }}
            </div>

            <!-- Validation banner -->
            <div v-if="currentTabHasErrors"
                class="mb-5 flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Please fix the highlighted errors before saving.
            </div>

            <!-- Two-column layout -->
            <div class="flex gap-6">

                <!-- Left nav -->
                <nav class="flex w-52 shrink-0 flex-col gap-1">
                    <button
                        v-for="tab in TABS"
                        :key="tab.key"
                        type="button"
                        @click="activeKey = tab.key"
                        class="flex items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors"
                        :class="activeKey === tab.key
                            ? 'bg-primary text-primary-foreground shadow-sm'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'"
                    >
                        <span>{{ tab.label }}</span>
                        <span v-if="tabHasErrors(tab.key)" class="h-2 w-2 shrink-0 rounded-full bg-red-500" />
                    </button>
                </nav>

                <!-- Right panel — single node, content driven by activeKey -->
                <div class="min-w-0 flex-1 rounded-xl border bg-card shadow-sm">

                    <!-- Panel header -->
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold">{{ currentTab.label }}</h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">{{ currentTab.desc }}</p>
                    </div>

                    <!-- Settings rows -->
                    <div class="divide-y">

                        <div v-if="currentSettings.length === 0"
                            class="px-6 py-10 text-center text-sm text-muted-foreground">
                            No settings configured for this section.
                        </div>

                        <div
                            v-for="setting in currentSettings"
                            :key="setting.key"
                            class="grid grid-cols-2 gap-6 px-6 py-4"
                            :class="{ 'bg-red-50/40': errors[setting.key] }"
                        >
                            <!-- Label + description -->
                            <div class="flex flex-col justify-center">
                                <label
                                    :for="setting.key"
                                    class="text-sm font-medium leading-snug"
                                    :class="errors[setting.key] ? 'text-red-700' : ''"
                                >{{ setting.label }}</label>
                                <p v-if="setting.description" class="mt-1 text-xs text-muted-foreground">
                                    {{ setting.description }}
                                </p>
                            </div>

                            <!-- Control + inline error -->
                            <div class="flex flex-col items-end justify-center gap-1.5">

                                <!-- Boolean toggle -->
                                <button
                                    v-if="setting.type === 'boolean'"
                                    :id="setting.key"
                                    type="button"
                                    role="switch"
                                    :aria-checked="!!form[setting.key]"
                                    @click="toggle(setting.key)"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2"
                                    :class="form[setting.key] ? 'bg-primary' : 'bg-gray-200 dark:bg-gray-700'"
                                >
                                    <span class="sr-only">{{ setting.label }}</span>
                                    <span
                                        aria-hidden="true"
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                                        :class="form[setting.key] ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>

                                <!-- JSON / array -->
                                <textarea
                                    v-else-if="setting.type === 'json' || setting.type === 'array'"
                                    :id="setting.key"
                                    v-model="form[setting.key]"
                                    rows="2"
                                    class="w-full rounded-md border px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2"
                                    :class="errors[setting.key]
                                        ? 'border-red-400 bg-red-50 text-red-900 focus:ring-red-400'
                                        : 'border-input bg-background focus:ring-primary'"
                                />

                                <!-- Time format: 12h / 24h segmented control -->
                                <div
                                    v-else-if="setting.key === 'time_format'"
                                    class="flex overflow-hidden rounded-md border border-input text-sm"
                                >
                                    <button
                                        v-for="opt in [{ value: '12h', label: '12h (AM/PM)' }, { value: '24h', label: '24h' }]"
                                        :key="opt.value"
                                        type="button"
                                        @click="form[setting.key] = opt.value"
                                        class="px-4 py-2 font-medium transition-colors focus:outline-none"
                                        :class="form[setting.key] === opt.value
                                            ? 'bg-primary text-primary-foreground'
                                            : 'bg-background text-muted-foreground hover:bg-muted'"
                                    >
                                        {{ opt.label }}
                                    </button>
                                </div>

                                <!-- Integer / string / time -->
                                <input
                                    v-else
                                    :id="setting.key"
                                    :type="setting.type === 'integer' ? 'number' : setting.type === 'time' ? 'time' : 'text'"
                                    :min="setting.type === 'integer' ? 0 : undefined"
                                    v-model="form[setting.key]"
                                    class="w-full rounded-md border px-3 py-2 text-sm focus:outline-none focus:ring-2"
                                    :class="errors[setting.key]
                                        ? 'border-red-400 bg-red-50 text-red-900 focus:ring-red-400'
                                        : 'border-input bg-background focus:ring-primary'"
                                />

                                <!-- Field error message -->
                                <p v-if="errors[setting.key]"
                                    class="self-start text-xs font-medium text-red-600">
                                    {{ errors[setting.key] }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer: save button -->
                    <div class="flex items-center justify-between border-t bg-muted/30 px-6 py-4">
                        <p class="text-xs text-muted-foreground">Only this section's settings will be saved.</p>
                        <button
                            type="button"
                            @click="save"
                            :disabled="saving"
                            class="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <svg v-if="saving" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
                            </svg>
                            <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ saving ? 'Saving…' : 'Save ' + currentTab.label }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>
