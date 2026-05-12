<script setup lang="ts">
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { usePage } from '@inertiajs/vue3';

const page = usePage<{ name: string; auth: { user: any } }>();

const features = [
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />`,
        title: 'Multi-Biometric Support',
        desc: 'RFID cards, fingerprint scanners, and face recognition — all in one platform. Students check in using whatever method your facility supports.',
        color: 'bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" />`,
        title: 'Device Management',
        desc: 'Register and monitor any number of physical scanners. Rotate API tokens, track firmware, and see each device\'s last heartbeat — all from the admin panel.',
        color: 'bg-purple-100 text-purple-600 dark:bg-purple-900/40 dark:text-purple-400',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />`,
        title: 'Flexible Schedules',
        desc: 'Configure sign-in windows, grace periods, and late thresholds. Override any day for holidays or special events without changing the global policy.',
        color: 'bg-orange-100 text-orange-600 dark:bg-orange-900/40 dark:text-orange-400',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />`,
        title: 'Rich Reports & PDF Exports',
        desc: 'Generate daily, range-based, or per-student reports in seconds. Download polished PDFs directly from any report page — no extra tools needed.',
        color: 'bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />`,
        title: 'External Student Sync',
        desc: 'Connect to your enrollment platform and automatically pull in active students. Expired enrollments are enforced at the scanner — no manual updates needed.',
        color: 'bg-indigo-100 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />`,
        title: 'Full Audit Trail',
        desc: 'Every login, setting change, and manual attendance correction is logged. See exactly who did what and when — giving you full accountability.',
        color: 'bg-rose-100 text-rose-600 dark:bg-rose-900/40 dark:text-rose-400',
    },
];

const steps = [
    {
        step: '01',
        title: 'Register Your Devices',
        desc: 'Add your RFID readers, fingerprint scanners, or face cameras to the platform. Each device gets a secure API token — ready in minutes.',
    },
    {
        step: '02',
        title: 'Students Scan In & Out',
        desc: 'When students present their credential at any registered device, the system logs the time, method, and location automatically.',
    },
    {
        step: '03',
        title: 'View Reports & Insights',
        desc: 'Open the admin dashboard to see real-time attendance, export PDF reports, and identify patterns — all without leaving the browser.',
    },
];

const stats = [
    { value: '10,000+', label: 'Students Tracked' },
    { value: '3',       label: 'Biometric Methods' },
    { value: '99.9%',   label: 'Uptime SLA' },
    { value: '<1s',     label: 'Scan Response Time' },
];

const testimonials = [
    {
        quote: 'We moved from paper roll-calls to fully automated RFID attendance in a week. The admin dashboard is excellent — our coordinators love it.',
        name: 'Amaka Obi',
        role: 'Operations Director, TechBridge Academy',
        initials: 'AO',
        color: 'bg-indigo-100 text-indigo-700',
    },
    {
        quote: 'The PDF export feature alone saves our admin team hours every month. We print daily reports for compliance in seconds.',
        name: 'Femi Adeyemi',
        role: 'Head of Training, Nexus Skills Hub',
        initials: 'FA',
        color: 'bg-purple-100 text-purple-700',
    },
    {
        quote: 'Setting up schedule policies for different cohorts was straightforward. The override system handles our holidays perfectly.',
        name: 'Grace Nwosu',
        role: 'Academic Registrar, ProCode Institute',
        initials: 'GN',
        color: 'bg-emerald-100 text-emerald-700',
    },
];
</script>

<template>
    <Head title="Smart Attendance Management Platform" />

    <MarketingLayout>

        <!-- ─── Hero ──────────────────────────────────────────────────── -->
        <section class="relative overflow-hidden bg-gradient-to-br from-indigo-950 via-indigo-900 to-violet-900 text-white">
            <!-- Subtle grid overlay -->
            <div class="pointer-events-none absolute inset-0 opacity-10"
                style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23ffffff\' fill-opacity=\'1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
            </div>

            <div class="relative mx-auto max-w-7xl px-6 py-28 text-center lg:py-36">
                <!-- Badge -->
                <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-sm font-medium backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Biometric Attendance Platform
                </div>

                <!-- Headline -->
                <h1 class="mx-auto max-w-4xl text-5xl font-extrabold leading-tight tracking-tight lg:text-7xl">
                    Track Attendance
                    <span class="relative">
                        <span class="relative z-10 text-indigo-300"> Smarter,</span>
                    </span>
                    <br />Not Harder
                </h1>

                <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-indigo-100/80 lg:text-xl">
                    From RFID to fingerprints and face recognition — one platform to manage every check-in,
                    every student, and every report. Built for training centers, schools, and enterprise teams.
                </p>

                <!-- CTAs -->
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <Link href="/register"
                        class="rounded-xl bg-white px-8 py-4 text-sm font-bold text-indigo-700 shadow-lg hover:bg-indigo-50 transition-colors">
                        Get Started Free →
                    </Link>
                    <Link href="/pricing"
                        class="rounded-xl border border-white/30 bg-white/10 px-8 py-4 text-sm font-semibold text-white backdrop-blur-sm hover:bg-white/20 transition-colors">
                        View Pricing
                    </Link>
                </div>

                <!-- Social proof -->
                <p class="mt-8 text-sm text-indigo-200/60">
                    No credit card required &nbsp;·&nbsp; Free plan available &nbsp;·&nbsp; Setup in under 10 minutes
                </p>
            </div>

            <!-- Bottom wave -->
            <div class="absolute bottom-0 left-0 right-0">
                <svg viewBox="0 0 1440 60" xmlns="http://www.w3.org/2000/svg" class="w-full fill-white dark:fill-gray-950">
                    <path d="M0,30 C360,60 1080,0 1440,30 L1440,60 L0,60 Z" />
                </svg>
            </div>
        </section>

        <!-- ─── Stats strip ────────────────────────────────────────────── -->
        <section class="border-b border-gray-100 bg-white dark:border-gray-800 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6 py-12">
                <div class="grid grid-cols-2 gap-8 lg:grid-cols-4">
                    <div v-for="stat in stats" :key="stat.label" class="text-center">
                        <div class="text-4xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ stat.value }}</div>
                        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Features ──────────────────────────────────────────────── -->
        <section id="features" class="bg-white py-24 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-16 text-center">
                    <span class="mb-3 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                        Everything You Need
                    </span>
                    <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white lg:text-5xl">
                        One platform, complete control
                    </h2>
                    <p class="mx-auto mt-4 max-w-2xl text-lg text-gray-500 dark:text-gray-400">
                        Every feature you need to run a professional attendance operation — from first scan to final report.
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="f in features"
                        :key="f.title"
                        class="group rounded-2xl border border-gray-100 bg-gray-50 p-7 transition-all hover:border-indigo-200 hover:shadow-lg dark:border-gray-800 dark:bg-gray-900 dark:hover:border-indigo-800"
                    >
                        <div class="mb-4 inline-flex rounded-xl p-3" :class="f.color">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="f.icon" />
                        </div>
                        <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">{{ f.title }}</h3>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ f.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── How it works ──────────────────────────────────────────── -->
        <section id="how-it-works" class="bg-gray-50 py-24 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-16 text-center">
                    <span class="mb-3 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                        Simple Setup
                    </span>
                    <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white lg:text-5xl">
                        Up and running in minutes
                    </h2>
                </div>

                <div class="grid gap-8 lg:grid-cols-3">
                    <div
                        v-for="(step, i) in steps"
                        :key="step.step"
                        class="relative rounded-2xl border border-gray-200 bg-white p-8 dark:border-gray-800 dark:bg-gray-950"
                    >
                        <!-- Connector line -->
                        <div v-if="i < steps.length - 1"
                            class="absolute top-12 -right-4 hidden h-0.5 w-8 bg-indigo-200 dark:bg-indigo-800 lg:block">
                        </div>
                        <div class="mb-5 text-5xl font-black text-indigo-100 dark:text-indigo-900/60">{{ step.step }}</div>
                        <h3 class="mb-3 text-xl font-bold text-gray-900 dark:text-white">{{ step.title }}</h3>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Full-width feature callout ────────────────────────────── -->
        <section class="bg-indigo-600 py-20 dark:bg-indigo-800">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <h2 class="text-4xl font-extrabold leading-tight text-white lg:text-5xl">
                            Built for the teams<br />who can't afford to guess
                        </h2>
                        <p class="mt-6 text-lg text-indigo-100/80 leading-relaxed">
                            Training centers, corporate offices, and educational institutions trust our platform
                            to give them accurate, real-time attendance data — with zero manual effort.
                        </p>
                        <ul class="mt-8 space-y-3">
                            <li v-for="item in ['Automatic check-in/check-out pairing', 'Manual correction with full audit log', 'Per-group attendance policies', 'Enrollment expiry enforcement at the scanner']"
                                :key="item"
                                class="flex items-start gap-3 text-sm text-indigo-100">
                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <!-- Mockup card -->
                    <div class="rounded-2xl bg-white/10 p-6 backdrop-blur-sm">
                        <div class="mb-4 flex items-center gap-2">
                            <div class="h-3 w-3 rounded-full bg-red-400"></div>
                            <div class="h-3 w-3 rounded-full bg-yellow-400"></div>
                            <div class="h-3 w-3 rounded-full bg-green-400"></div>
                            <span class="ml-2 text-xs text-indigo-200">Admin Dashboard — Today's Attendance</span>
                        </div>
                        <div class="space-y-2.5">
                            <div v-for="(row, i) in [
                                {name: 'Adaeze Chukwu', time: '08:02 AM', out: '04:58 PM', status: 'Present'},
                                {name: 'Emeka Okonkwo', time: '08:17 AM', out: '—', status: 'In'},
                                {name: 'Fatima Yusuf', time: '08:34 AM', out: '04:55 PM', status: 'Present'},
                                {name: 'James Okafor', time: '09:12 AM', out: '—', status: 'Late'},
                            ]" :key="i"
                                class="flex items-center justify-between rounded-lg bg-white/10 px-4 py-2.5 text-xs text-white">
                                <span class="font-medium">{{ row.name }}</span>
                                <span class="font-mono text-indigo-200">{{ row.time }}</span>
                                <span class="font-mono text-indigo-300">{{ row.out }}</span>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    :class="{
                                        'bg-emerald-400/20 text-emerald-300': row.status === 'Present',
                                        'bg-blue-400/20 text-blue-300':      row.status === 'In',
                                        'bg-yellow-400/20 text-yellow-300':  row.status === 'Late',
                                    }">
                                    {{ row.status }}
                                </span>
                            </div>
                        </div>
                        <div class="mt-4 flex items-center justify-between text-xs text-indigo-200/70">
                            <span>4 of 48 shown</span>
                            <span class="underline cursor-pointer">Export PDF →</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Testimonials ──────────────────────────────────────────── -->
        <section class="bg-white py-24 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-16 text-center">
                    <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                        Trusted by training teams across Nigeria
                    </h2>
                </div>

                <div class="grid gap-8 lg:grid-cols-3">
                    <div
                        v-for="t in testimonials"
                        :key="t.name"
                        class="rounded-2xl border border-gray-100 bg-gray-50 p-8 dark:border-gray-800 dark:bg-gray-900"
                    >
                        <!-- Stars -->
                        <div class="mb-5 flex gap-1">
                            <svg v-for="s in 5" :key="s" class="h-4 w-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                            </svg>
                        </div>
                        <blockquote class="mb-6 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                            "{{ t.quote }}"
                        </blockquote>
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold" :class="t.color">
                                {{ t.initials }}
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ t.name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ t.role }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Final CTA ─────────────────────────────────────────────── -->
        <section class="bg-gray-950 py-24 text-white dark:bg-black">
            <div class="mx-auto max-w-3xl px-6 text-center">
                <h2 class="text-4xl font-extrabold tracking-tight lg:text-5xl">
                    Ready to modernize<br />your attendance?
                </h2>
                <p class="mx-auto mt-5 max-w-xl text-lg text-gray-400">
                    Join hundreds of organizations that have ditched paper registers and spreadsheets for real-time biometric tracking.
                </p>
                <div class="mt-10 flex flex-wrap items-center justify-center gap-4">
                    <Link href="/register"
                        class="rounded-xl bg-indigo-600 px-8 py-4 text-sm font-bold text-white shadow-lg hover:bg-indigo-500 transition-colors">
                        Get Started Free →
                    </Link>
                    <Link href="/contact"
                        class="rounded-xl border border-gray-700 px-8 py-4 text-sm font-semibold text-gray-300 hover:border-gray-500 hover:text-white transition-colors">
                        Talk to Sales
                    </Link>
                </div>
            </div>
        </section>

    </MarketingLayout>
</template>
