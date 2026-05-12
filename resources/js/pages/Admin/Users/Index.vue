<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Props {
    users: PaginatedData<User>;
    filters: { search?: string; role?: string; status?: string };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

const search = ref(props.filters.search ?? '');
const role = ref(props.filters.role ?? '');
const status = ref(props.filters.status ?? '');

let searchTimeout: ReturnType<typeof setTimeout>;
watch([search, role, status], () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        router.get('/admin/users', { search: search.value, role: role.value, status: status.value }, { preserveState: true, replace: true });
    }, 300);
});

function updateStatus(userId: number, newStatus: string) {
    router.post(`/admin/users/${userId}/status`, { status: newStatus }, { preserveScroll: true });
}

const statusColors: Record<string, string> = {
    active: 'bg-green-100 text-green-700',
    inactive: 'bg-gray-100 text-gray-600',
    suspended: 'bg-red-100 text-red-700',
};

const roleColors: Record<string, string> = {
    admin: 'bg-purple-100 text-purple-700',
    staff: 'bg-blue-100 text-blue-700',
    student: 'bg-amber-100 text-amber-700',
};
</script>

<template>
    <Head title="Users" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-4">
            <!-- Toolbar -->
            <div class="mb-4 flex flex-wrap items-center gap-3">
                <input
                    v-model="search"
                    type="search"
                    placeholder="Search name or email…"
                    class="w-64 rounded-md border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
                />
                <select v-model="role" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <option value="">All roles</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="student">Student</option>
                </select>
                <select v-model="status" class="rounded-md border bg-background px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                    <option value="suspended">Suspended</option>
                </select>
                <div class="ml-auto">
                    <Link
                        href="/admin/users/create"
                        class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                    >
                        Add User
                    </Link>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40 text-xs uppercase text-muted-foreground">
                            <tr>
                                <th class="px-5 py-3 text-left">Name</th>
                                <th class="px-5 py-3 text-left">Email</th>
                                <th class="px-5 py-3 text-left">Role</th>
                                <th class="px-5 py-3 text-left">Status</th>
                                <th class="px-5 py-3 text-left">Branch</th>
                                <th class="px-5 py-3 text-left">Expires</th>
                                <th class="px-5 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b last:border-0 hover:bg-muted/20">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 shrink-0 overflow-hidden rounded-full border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                                            <img v-if="user.profile_photo_url" :src="user.profile_photo_url" :alt="user.name" class="h-full w-full object-cover" />
                                            <div v-else class="flex h-full w-full items-center justify-center">
                                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                                </svg>
                                            </div>
                                        </div>
                                        <span class="font-medium">{{ user.name }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ user.email }}</td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="roleColors[user.role]">
                                        {{ user.role }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize" :class="statusColors[user.status]">
                                        {{ user.status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 text-muted-foreground">{{ user.branch ?? '—' }}</td>
                                <td class="px-5 py-3 text-muted-foreground">{{ user.program_expires_at ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <Link :href="`/admin/users/${user.id}/edit`" class="text-xs text-primary hover:underline">Edit</Link>
                                        <Link :href="`/admin/users/${user.id}/credentials`" class="text-xs text-muted-foreground hover:underline">Credentials</Link>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!users.data.length">
                                <td colspan="7" class="px-5 py-8 text-center text-muted-foreground">No users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="flex items-center justify-between border-t px-5 py-3">
                    <p class="text-xs text-muted-foreground">
                        Showing {{ users.from }}–{{ users.to }} of {{ users.total }}
                    </p>
                    <div class="flex gap-1">
                        <component
                            v-for="link in users.links"
                            :key="link.label"
                            :is="link.url ? Link : 'span'"
                            :href="link.url ?? undefined"
                            v-html="link.label"
                            class="rounded px-2.5 py-1 text-xs"
                            :class="link.active ? 'bg-primary text-primary-foreground' : link.url ? 'hover:bg-muted text-muted-foreground' : 'text-muted-foreground/40'"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
