<script setup lang="ts">
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage<{ name: string }>();
const appName = page.props.name;

const values = [
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />`,
        title: 'Reliability First',
        desc: 'Attendance data drives real decisions. We build with uptime, data integrity, and edge-case handling as first-class requirements — not afterthoughts.',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />`,
        title: 'Simplicity Over Complexity',
        desc: 'Every feature we add must justify itself. We believe the best enterprise software is the kind that doesn\'t feel like enterprise software.',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />`,
        title: 'People-Centred',
        desc: 'Attendance is ultimately about people. We design every workflow with both the student experience and the admin\'s daily reality in mind.',
    },
    {
        icon: `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />`,
        title: 'Privacy by Default',
        desc: 'Biometric data never leaves your system in raw form. We hash, encrypt, and protect identity data as if it were our own.',
    },
];

const team = [
    {
        name: 'Tunde Adewale',
        role: 'Co-founder & CEO',
        bio: 'Former ed-tech founder with 10+ years building software for Nigerian training institutions.',
        initials: 'TA',
        color: 'bg-indigo-100 text-indigo-700',
    },
    {
        name: 'Ngozi Okonkwo',
        role: 'Co-founder & CTO',
        bio: 'Backend engineer with deep experience in biometric integration, identity systems, and high-throughput APIs.',
        initials: 'NO',
        color: 'bg-purple-100 text-purple-700',
    },
    {
        name: 'Sola Babatunde',
        role: 'Head of Product',
        bio: 'Product designer who spent five years inside training centers watching coordinators fight with spreadsheets.',
        initials: 'SB',
        color: 'bg-emerald-100 text-emerald-700',
    },
    {
        name: 'Chidera Eze',
        role: 'Head of Customer Success',
        bio: 'Ensures every customer goes live smoothly and gets maximum value from the platform.',
        initials: 'CE',
        color: 'bg-orange-100 text-orange-700',
    },
];

const milestones = [
    { year: '2022', event: 'Founded after watching a training center lose weeks of attendance data to a crashed spreadsheet.' },
    { year: '2023', event: 'First 10 customers onboarded. Launched RFID scanning and the admin dashboard.' },
    { year: '2024', event: 'Added fingerprint and face recognition. Student sync feature shipped. Crossed 5,000 tracked students.' },
    { year: '2025', event: 'Enterprise tier launched. PDF/Excel exports. Expanded to multiple states across Nigeria.' },
];
</script>

<template>
    <Head title="About Us" />

    <MarketingLayout>

        <!-- ─── Hero ──────────────────────────────────────────────────── -->
        <section class="bg-gradient-to-b from-indigo-950 to-indigo-800 py-24 text-white">
            <div class="mx-auto max-w-4xl px-6 text-center">
                <span class="mb-4 inline-block rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest">
                    About Us
                </span>
                <h1 class="text-5xl font-extrabold leading-tight tracking-tight lg:text-6xl">
                    We're on a mission to make attendance<br />
                    <span class="text-indigo-300">invisible and reliable</span>
                </h1>
                <p class="mx-auto mt-6 max-w-2xl text-lg text-indigo-100/80 leading-relaxed">
                    {{ appName }} was built because we watched talented training coordinators spend hours
                    every week reconciling paper registers and fixing spreadsheet errors. We knew there had to be a better way.
                </p>
            </div>
        </section>

        <!-- ─── Story ─────────────────────────────────────────────────── -->
        <section class="bg-white py-24 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid gap-16 lg:grid-cols-2 lg:items-center">
                    <div>
                        <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                            The problem we set out to solve
                        </h2>
                        <div class="mt-6 space-y-5 text-base text-gray-600 dark:text-gray-400 leading-relaxed">
                            <p>
                                In Nigerian training centers, attendance is often still tracked with pen-and-paper sign-in sheets.
                                At the end of each week, a coordinator manually transcribes them into a spreadsheet.
                                Sheets get lost. Names are illegible. Formulas break. Reports take hours.
                            </p>
                            <p>
                                When we saw RFID readers sitting unused because there was no software to go with them,
                                we built {{ appName }}. The goal was simple: scan in, scan out, see the data instantly — with zero manual effort.
                            </p>
                            <p>
                                Today we support RFID, fingerprint, and face recognition, with full admin dashboards,
                                PDF reporting, external student sync, and a device API that any hardware vendor can integrate against.
                            </p>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <div class="space-y-0">
                        <div v-for="(m, i) in milestones" :key="m.year" class="flex gap-4">
                            <div class="flex flex-col items-center">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">
                                    {{ m.year.slice(2) }}
                                </div>
                                <div v-if="i < milestones.length - 1" class="mt-1 w-0.5 flex-1 bg-indigo-200 dark:bg-indigo-800"></div>
                            </div>
                            <div class="pb-10">
                                <div class="text-sm font-bold text-indigo-600 dark:text-indigo-400">{{ m.year }}</div>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ m.event }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Values ────────────────────────────────────────────────── -->
        <section class="bg-gray-50 py-24 dark:bg-gray-900">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-14 text-center">
                    <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">What we believe</h2>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">Our core values shape every feature we build and every decision we make.</p>
                </div>
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="v in values" :key="v.title"
                        class="rounded-2xl border border-gray-100 bg-white p-7 shadow-sm dark:border-gray-800 dark:bg-gray-950">
                        <div class="mb-4 inline-flex rounded-xl bg-indigo-100 p-3 text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" v-html="v.icon" />
                        </div>
                        <h3 class="mb-2 font-bold text-gray-900 dark:text-white">{{ v.title }}</h3>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ v.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Team ──────────────────────────────────────────────────── -->
        <section class="bg-white py-24 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6">
                <div class="mb-14 text-center">
                    <h2 class="text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white">Meet the team</h2>
                    <p class="mt-4 text-gray-500 dark:text-gray-400">
                        A small, focused team that has spent years inside the problem we're solving.
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div v-for="member in team" :key="member.name"
                        class="rounded-2xl border border-gray-100 bg-gray-50 p-8 text-center dark:border-gray-800 dark:bg-gray-900">
                        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full text-2xl font-extrabold" :class="member.color">
                            {{ member.initials }}
                        </div>
                        <h3 class="font-bold text-gray-900 dark:text-white">{{ member.name }}</h3>
                        <p class="mt-1 text-sm font-medium text-indigo-600 dark:text-indigo-400">{{ member.role }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ member.bio }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── CTA ───────────────────────────────────────────────────── -->
        <section class="bg-indigo-600 py-20 text-white">
            <div class="mx-auto max-w-3xl px-6 text-center">
                <h2 class="text-4xl font-extrabold">Want to work with us?</h2>
                <p class="mt-4 text-indigo-100/80">
                    Whether you're a potential customer or a developer who wants to join the mission, we'd love to hear from you.
                </p>
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    <Link href="/contact"
                        class="rounded-xl bg-white px-8 py-4 text-sm font-bold text-indigo-700 hover:bg-indigo-50 transition-colors">
                        Get in Touch →
                    </Link>
                    <Link href="/pricing"
                        class="rounded-xl border border-white/30 bg-white/10 px-8 py-4 text-sm font-semibold text-white hover:bg-white/20 transition-colors">
                        View Pricing
                    </Link>
                </div>
            </div>
        </section>

    </MarketingLayout>
</template>
