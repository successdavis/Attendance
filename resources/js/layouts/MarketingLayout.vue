<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

const page = usePage<{ name: string; auth: { user: any } }>();
const appName = page.props.name;

const mobileOpen = ref(false);

const navLinks = [
    { label: 'Home',    href: '/' },
    { label: 'Pricing', href: '/pricing' },
    { label: 'About',   href: '/about' },
    { label: 'Contact', href: '/contact' },
];
</script>

<template>
    <!-- ─── Navbar ─────────────────────────────────────────────────────── -->
    <header class="sticky top-0 z-50 border-b border-white/10 bg-white/90 backdrop-blur-md dark:bg-gray-950/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4">
            <!-- Logo -->
            <Link href="/" class="flex items-center gap-2.5 group">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white shadow-sm group-hover:bg-indigo-700 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight text-gray-900 dark:text-white">{{ appName }}</span>
            </Link>

            <!-- Desktop nav -->
            <nav class="hidden items-center gap-1 md:flex">
                <Link
                    v-for="link in navLinks"
                    :key="link.href"
                    :href="link.href"
                    class="rounded-md px-4 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white"
                    :class="{ 'text-indigo-600 dark:text-indigo-400': $page.url === link.href }"
                >
                    {{ link.label }}
                </Link>
            </nav>

            <!-- Auth CTA -->
            <div class="hidden items-center gap-3 md:flex">
                <template v-if="page.props.auth.user">
                    <Link href="/dashboard"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                        Dashboard →
                    </Link>
                </template>
                <template v-else>
                    <Link href="/login"
                        class="rounded-lg px-4 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                        Log in
                    </Link>
                    <Link href="/register"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 transition-colors">
                        Get Started Free
                    </Link>
                </template>
            </div>

            <!-- Mobile hamburger -->
            <button @click="mobileOpen = !mobileOpen"
                class="flex items-center justify-center rounded-md p-2 text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 md:hidden">
                <svg v-if="!mobileOpen" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile menu -->
        <div v-if="mobileOpen" class="border-t border-gray-100 bg-white px-6 pb-4 dark:border-gray-800 dark:bg-gray-950 md:hidden">
            <nav class="flex flex-col gap-1 pt-3">
                <Link v-for="link in navLinks" :key="link.href" :href="link.href"
                    @click="mobileOpen = false"
                    class="rounded-md px-3 py-2.5 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">
                    {{ link.label }}
                </Link>
            </nav>
            <div class="mt-4 flex flex-col gap-2 border-t border-gray-100 pt-4 dark:border-gray-800">
                <template v-if="page.props.auth.user">
                    <Link href="/dashboard" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white">
                        Dashboard →
                    </Link>
                </template>
                <template v-else>
                    <Link href="/login" class="rounded-lg border px-4 py-2.5 text-center text-sm font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300">
                        Log in
                    </Link>
                    <Link href="/register" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white">
                        Get Started Free
                    </Link>
                </template>
            </div>
        </div>
    </header>

    <!-- ─── Page content ───────────────────────────────────────────────── -->
    <slot />

    <!-- ─── Footer ─────────────────────────────────────────────────────── -->
    <footer class="border-t border-gray-200 bg-gray-50 dark:border-gray-800 dark:bg-gray-950">
        <div class="mx-auto max-w-7xl px-6 py-16">
            <div class="grid gap-10 md:grid-cols-4">
                <!-- Brand -->
                <div class="md:col-span-1">
                    <Link href="/" class="flex items-center gap-2.5">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">{{ appName }}</span>
                    </Link>
                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400 leading-relaxed">
                        Modern biometric attendance management for schools, training centers, and businesses.
                    </p>
                </div>

                <!-- Product -->
                <div>
                    <h3 class="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Product</h3>
                    <ul class="space-y-3">
                        <li><Link href="/#features" class="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">Features</Link></li>
                        <li><Link href="/pricing" class="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">Pricing</Link></li>
                        <li><Link href="/#how-it-works" class="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">How It Works</Link></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h3 class="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Company</h3>
                    <ul class="space-y-3">
                        <li><Link href="/about" class="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">About Us</Link></li>
                        <li><Link href="/contact" class="text-sm text-gray-600 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400 transition-colors">Contact</Link></li>
                    </ul>
                </div>

                <!-- CTA -->
                <div>
                    <h3 class="mb-4 text-xs font-semibold uppercase tracking-widest text-gray-500 dark:text-gray-400">Get Started</h3>
                    <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">Ready to streamline your attendance tracking?</p>
                    <Link href="/register"
                        class="inline-block rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                        Start for Free →
                    </Link>
                </div>
            </div>

            <div class="mt-12 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-8 dark:border-gray-800 sm:flex-row">
                <p class="text-xs text-gray-400">
                    © {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
                </p>
                <div class="flex gap-6">
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Privacy Policy</a>
                    <a href="#" class="text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</template>
