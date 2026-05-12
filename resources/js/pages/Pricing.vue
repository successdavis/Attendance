<script setup lang="ts">
import MarketingLayout from '@/layouts/MarketingLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const plans = [
    {
        name: 'Starter',
        price: 'Free',
        period: 'forever',
        desc: 'Perfect for small centres getting started with digital attendance.',
        cta: 'Get Started Free',
        ctaHref: '/register',
        featured: false,
        color: 'border-gray-200 dark:border-gray-700',
        features: [
            'Up to 50 students',
            '1 attendance device',
            'RFID scan support',
            'Daily & range reports',
            'Admin dashboard',
            '7-day audit log',
        ],
        missing: [
            'PDF / Excel export',
            'Fingerprint & face scan',
            'External student sync',
            'Custom policies',
            'Priority support',
        ],
    },
    {
        name: 'Professional',
        price: '₦25,000',
        period: '/month',
        desc: 'For growing training centers that need full biometric tracking and exports.',
        cta: 'Start Free Trial',
        ctaHref: '/register',
        featured: true,
        color: 'border-indigo-500',
        features: [
            'Up to 500 students',
            '10 attendance devices',
            'RFID + Fingerprint + Face',
            'PDF & Excel report exports',
            'External student sync',
            'Custom attendance policies',
            'Manual corrections + audit',
            '90-day audit log',
            'Email support',
        ],
        missing: [
            'Unlimited devices',
            'Custom API integration',
            'Dedicated account manager',
        ],
    },
    {
        name: 'Enterprise',
        price: 'Custom',
        period: 'bespoke pricing',
        desc: 'For large organizations with complex requirements and multiple branches.',
        cta: 'Talk to Sales',
        ctaHref: '/contact',
        featured: false,
        color: 'border-gray-200 dark:border-gray-700',
        features: [
            'Unlimited students',
            'Unlimited devices',
            'All biometric methods',
            'All export formats',
            'Full student sync + API',
            'Custom policy engine',
            'Unlimited audit log',
            'Custom integrations',
            'SLA guarantee',
            'Dedicated account manager',
            'On-site training',
            'Priority phone support',
        ],
        missing: [],
    },
];

const faqs = [
    {
        q: 'Do I need hardware to get started?',
        a: 'You can start with the web-based admin dashboard immediately. Physical scanners (RFID readers, fingerprint scanners) connect via our device API and can be added at any time.',
    },
    {
        q: 'Can I switch plans later?',
        a: 'Yes. You can upgrade or downgrade your plan at any time. Upgrades take effect immediately; downgrades at the end of your billing cycle.',
    },
    {
        q: 'How does the external student sync work?',
        a: 'We pull student records from your enrollment platform via a secure API. You configure the URL and API key in the admin settings — sync runs automatically on your chosen schedule.',
    },
    {
        q: 'Is my data safe?',
        a: 'All data is encrypted at rest and in transit. Biometric templates (fingerprint/face hashes) are stored as one-way hashes — we never store raw biometric images.',
    },
    {
        q: 'What happens when a student\'s enrollment expires?',
        a: 'With enrollment expiry enforcement enabled, the scanner will reject check-ins from expired students and display a clear denial message.',
    },
    {
        q: 'Do you offer a free trial for Professional?',
        a: 'Yes — 14 days free, no credit card required. All Professional features are fully available during the trial period.',
    },
];
</script>

<template>
    <Head title="Pricing" />

    <MarketingLayout>

        <!-- ─── Hero ──────────────────────────────────────────────────── -->
        <section class="bg-gradient-to-b from-gray-50 to-white py-20 dark:from-gray-900 dark:to-gray-950">
            <div class="mx-auto max-w-3xl px-6 text-center">
                <span class="mb-3 inline-block rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-widest text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                    Pricing
                </span>
                <h1 class="mt-2 text-5xl font-extrabold tracking-tight text-gray-900 dark:text-white">
                    Simple, transparent pricing
                </h1>
                <p class="mx-auto mt-5 max-w-xl text-lg text-gray-500 dark:text-gray-400">
                    Start free. Scale as you grow. No hidden fees, no complicated tiers.
                </p>
            </div>
        </section>

        <!-- ─── Plans ─────────────────────────────────────────────────── -->
        <section class="bg-white py-16 dark:bg-gray-950">
            <div class="mx-auto max-w-7xl px-6">
                <div class="grid gap-8 lg:grid-cols-3">
                    <div
                        v-for="plan in plans"
                        :key="plan.name"
                        class="relative flex flex-col rounded-2xl border-2 p-8 shadow-sm transition-shadow hover:shadow-md"
                        :class="[plan.color, plan.featured ? 'ring-2 ring-indigo-500 ring-offset-2 dark:ring-offset-gray-950' : '']"
                    >
                        <!-- Popular badge -->
                        <div v-if="plan.featured"
                            class="absolute -top-4 left-1/2 -translate-x-1/2 rounded-full bg-indigo-600 px-4 py-1 text-xs font-bold uppercase tracking-widest text-white shadow">
                            Most Popular
                        </div>

                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ plan.name }}</h2>
                            <div class="mt-4 flex items-end gap-1">
                                <span class="text-4xl font-extrabold text-gray-900 dark:text-white">{{ plan.price }}</span>
                                <span class="mb-1 text-sm text-gray-500 dark:text-gray-400">{{ plan.period }}</span>
                            </div>
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">{{ plan.desc }}</p>
                        </div>

                        <Link
                            :href="plan.ctaHref"
                            class="mb-8 block rounded-xl py-3 text-center text-sm font-bold transition-colors"
                            :class="plan.featured
                                ? 'bg-indigo-600 text-white hover:bg-indigo-700'
                                : 'border border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800'"
                        >
                            {{ plan.cta }} →
                        </Link>

                        <div class="flex-1">
                            <p class="mb-3 text-xs font-semibold uppercase tracking-widest text-gray-400">Included</p>
                            <ul class="space-y-2.5">
                                <li v-for="f in plan.features" :key="f" class="flex items-start gap-2.5 text-sm text-gray-700 dark:text-gray-300">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ f }}
                                </li>
                                <li v-for="f in plan.missing" :key="f" class="flex items-start gap-2.5 text-sm text-gray-400 dark:text-gray-600">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                    {{ f }}
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ─── Feature comparison strip ─────────────────────────────── -->
        <section class="bg-gray-50 py-20 dark:bg-gray-900">
            <div class="mx-auto max-w-5xl px-6">
                <h2 class="mb-12 text-center text-3xl font-extrabold text-gray-900 dark:text-white">Compare all features</h2>
                <div class="overflow-hidden rounded-2xl border border-gray-200 dark:border-gray-700">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-950">
                                <th class="px-6 py-4 text-left font-semibold text-gray-700 dark:text-gray-300 w-1/2">Feature</th>
                                <th class="px-4 py-4 text-center font-semibold text-gray-700 dark:text-gray-300">Starter</th>
                                <th class="px-4 py-4 text-center font-semibold text-indigo-600 dark:text-indigo-400">Professional</th>
                                <th class="px-4 py-4 text-center font-semibold text-gray-700 dark:text-gray-300">Enterprise</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr v-for="(row, i) in [
                                ['Students', '50', '500', 'Unlimited'],
                                ['Devices', '1', '10', 'Unlimited'],
                                ['RFID support', '✓', '✓', '✓'],
                                ['Fingerprint support', '—', '✓', '✓'],
                                ['Face recognition', '—', '✓', '✓'],
                                ['PDF report export', '—', '✓', '✓'],
                                ['Excel export', '—', '✓', '✓'],
                                ['External student sync', '—', '✓', '✓'],
                                ['Attendance policies', '—', '✓', '✓'],
                                ['Manual corrections + audit', '—', '✓', '✓'],
                                ['Custom API integration', '—', '—', '✓'],
                                ['SLA guarantee', '—', '—', '✓'],
                                ['Dedicated account manager', '—', '—', '✓'],
                            ]" :key="i"
                                :class="i % 2 === 0 ? 'bg-white dark:bg-gray-950' : 'bg-gray-50 dark:bg-gray-900'">
                                <td class="px-6 py-3.5 text-gray-700 dark:text-gray-300">{{ row[0] }}</td>
                                <td class="px-4 py-3.5 text-center text-gray-500 dark:text-gray-400">{{ row[1] }}</td>
                                <td class="px-4 py-3.5 text-center font-medium text-indigo-600 dark:text-indigo-400">{{ row[2] }}</td>
                                <td class="px-4 py-3.5 text-center text-gray-500 dark:text-gray-400">{{ row[3] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <!-- ─── FAQs ───────────────────────────────────────────────────── -->
        <section class="bg-white py-24 dark:bg-gray-950">
            <div class="mx-auto max-w-3xl px-6">
                <h2 class="mb-12 text-center text-3xl font-extrabold text-gray-900 dark:text-white">Frequently asked questions</h2>
                <div class="space-y-8">
                    <div v-for="faq in faqs" :key="faq.q" class="border-b border-gray-100 pb-8 dark:border-gray-800 last:border-0 last:pb-0">
                        <h3 class="mb-3 text-base font-bold text-gray-900 dark:text-white">{{ faq.q }}</h3>
                        <p class="text-sm leading-relaxed text-gray-500 dark:text-gray-400">{{ faq.a }}</p>
                    </div>
                </div>

                <div class="mt-14 rounded-2xl bg-indigo-50 p-8 text-center dark:bg-indigo-900/20">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Still have questions?</p>
                    <Link href="/contact" class="mt-3 inline-block text-sm font-bold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">
                        Chat with our team →
                    </Link>
                </div>
            </div>
        </section>

    </MarketingLayout>
</template>
