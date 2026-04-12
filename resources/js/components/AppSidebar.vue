<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    BarChart3,
    BookOpen,
    Building2,
    ClipboardList,
    Cog,
    FilePenLine,
    Fingerprint,
    LayoutGrid,
    MonitorSpeaker,
    RefreshCw,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const page = usePage<{ auth: { role: string | null } }>();

const isAdmin = page.props.auth.role === 'admin';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Mark Attendance',
        href: '/attendance/scan',
        icon: Fingerprint,
    },
];

const adminNavItems: NavItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin',
        icon: ShieldCheck,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Devices',
        href: '/admin/devices',
        icon: MonitorSpeaker,
    },
    {
        title: 'Settings',
        href: '/admin/settings',
        icon: Cog,
    },
    {
        title: 'Policies',
        href: '/admin/policies',
        icon: ClipboardList,
    },
    {
        title: 'Reports',
        href: '/admin/reports/daily',
        icon: BarChart3,
    },
    {
        title: 'Corrections',
        href: '/admin/corrections',
        icon: FilePenLine,
    },
    {
        title: 'Student Sync',
        href: '/admin/sync',
        icon: RefreshCw,
    },
    {
        title: 'Audit Log',
        href: '/admin/audit',
        icon: Activity,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Branches',
        href: '#',
        icon: Building2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Attendance" />
            <NavMain v-if="isAdmin" :items="adminNavItems" label="Administration" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
