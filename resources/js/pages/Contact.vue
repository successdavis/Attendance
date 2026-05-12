<script setup lang="ts">
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';

const page = usePage<{ flash: { contactSuccess: boolean } }>();

const form = useForm({
    name:    '',
    email:   '',
    subject: '',
    message: '',
});

function submit() {
    form.post('/contact', {
        preserveScroll: true,
    });
}

const contactInfo = [
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />`,
        label: 'Email us',
        value: 'hello@attendms.com',
        href: 'mailto:hello@attendms.com',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />`,
        label: 'Call us',
        value: '+234 800 000 0000',
        href: 'tel:+2348000000000',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />`,
        label: 'Office',
        value: 'Lagos, Nigeria',
        href: '#',
    },
];
</script>

<template>
    <Head title="Contact Us" />

    <MarketingLayout>

        <!-- ─── Hero ──────────────────────────────────────────────────── -->
        <section class="bg-gradient-to-b from-gray-50 to-white py-20 dark:from-gray-900 dark:to-gray-950">
            <div class="mx-auto max-w-3xl px-6 text-center">
                <span class="mb-3 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                    Contact Us
                </span>
                <h1 class="mt-2 text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    We'd love to hear from you
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-gray-500 dark:text-gray-400">
                    Whether you have a question about pricing, need a demo, or just want to say hello — our team is ready to help.
                </p>
            </div>
        </section>

        <!-- ─── Contact section ───────────────────────────────────────── -->
        <section class="bg-white py-16 dark:bg-gray-950">
            <div class="mx-auto max-w-6xl px-6">
                <div class="grid gap-12 lg:grid-cols-5">

                    <!-- Left: Info cards -->
                    <div class="lg:col-span-2 space-y-6">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Get in touch</h2>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                                Typically we respond within one business day. For urgent matters, reach us by phone.
                            </p>
                        </div>

                        <div v-for="info in contactInfo" :key="info.label"
                            class="flex items-start gap-4 rounded-2xl border border-gray-100 bg-gray-50 p-5 dark:border-gray-800 dark:bg-gray-900">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="info.icon" />
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">{{ info.label }}</p>
                                <a :href="info.href" class="mt-1 block text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400 transition-colors">
                                    {{ info.value }}
                                </a>
                            </div>
                        </div>

                        <!-- Response time note -->
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5 dark:border-indigo-900/40 dark:bg-indigo-900/20">
                            <div class="flex items-center gap-2">
                                <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                <span class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">Online — Usually responds in &lt; 24 hours</span>
                            </div>
                            <p class="mt-2 text-xs text-indigo-600/80 dark:text-indigo-400/80">
                                For enterprise inquiries and demos, we typically respond within 4 business hours.
                            </p>
                        </div>
                    </div>

                    <!-- Right: Form -->
                    <div class="lg:col-span-3">
                        <!-- Success state -->
                        <div v-if="page.props.flash.contactSuccess || form.wasSuccessful"
                            class="flex flex-col items-center rounded-2xl border border-emerald-200 bg-emerald-50 p-12 text-center dark:border-emerald-800 dark:bg-emerald-900/20">
                            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                                <svg class="h-8 w-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">Message sent!</h3>
                            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                                Thanks for reaching out. We'll get back to you within one business day.
                            </p>
                            <button @click="form.reset()" class="mt-6 text-sm font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                                Send another message →
                            </button>
                        </div>

                        <!-- Form -->
                        <form v-else @submit.prevent="submit" class="rounded-2xl border border-gray-100 bg-gray-50 p-8 dark:border-gray-800 dark:bg-gray-900">
                            <h2 class="mb-6 text-xl font-bold text-gray-900 dark:text-white">Send us a message</h2>

                            <div class="grid gap-5 sm:grid-cols-2">
                                <!-- Name -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Your name <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Adaeze Okonkwo"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Email address <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="you@company.com"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.email }"
                                    />
                                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                                </div>

                                <!-- Subject (full width) -->
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Subject <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="form.subject"
                                        class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white"
                                        :class="{ 'border-red-500': form.errors.subject }"
                                    >
                                        <option value="">Select a topic…</option>
                                        <option value="demo">Request a demo</option>
                                        <option value="pricing">Pricing question</option>
                                        <option value="technical">Technical support</option>
                                        <option value="enterprise">Enterprise inquiry</option>
                                        <option value="partnership">Partnership</option>
                                        <option value="other">Something else</option>
                                    </select>
                                    <p v-if="form.errors.subject" class="mt-1 text-xs text-red-500">{{ form.errors.subject }}</p>
                                </div>

                                <!-- Message (full width) -->
                                <div class="sm:col-span-2">
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        Message <span class="text-red-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="form.message"
                                        rows="6"
                                        placeholder="Tell us about your organization and what you need…"
                                        class="w-full resize-none rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"
                                        :class="{ 'border-red-500 focus:border-red-500 focus:ring-red-500/20': form.errors.message }"
                                    />
                                    <p v-if="form.errors.message" class="mt-1 text-xs text-red-500">{{ form.errors.message }}</p>
                                </div>
                            </div>

                            <div class="mt-6 flex items-center justify-between">
                                <p class="text-xs text-gray-400">
                                    We'll never share your information.
                                </p>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-6 py-3 text-sm font-bold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-60 transition-colors"
                                >
                                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                    </svg>
                                    {{ form.processing ? 'Sending…' : 'Send Message →' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Bottom CTA ────────────────────────────────────────────── -->
        <section class="border-t border-gray-100 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-900">
            <div class="mx-auto max-w-3xl px-6 text-center">
                <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white">
                    Prefer to explore on your own?
                </h2>
                <p class="mt-4 text-gray-500 dark:text-gray-400">
                    Sign up free — no credit card, no commitments. See the full admin dashboard and start tracking in minutes.
                </p>
                <a href="/register"
                    class="mt-8 inline-block rounded-xl bg-indigo-600 px-8 py-4 text-sm font-bold text-white hover:bg-indigo-700 transition-colors">
                    Create a Free Account →
                </a>
            </div>
        </section>

    </MarketingLayout>
</template>
