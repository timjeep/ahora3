<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import adminRoutes from '@/routes/admin';
import policyRoutes from '@/routes/policies';
import { Users, BookUser, HardHat, KeyRound, MapPin, Package, Binoculars, Hammer, Send, View, Handshake, Cookie, Eraser, Copyright, BookOpen, Folder, LayoutGrid, ListFilter, Route } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

/*
const companyNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: companyRoutes.dashboard.url(),
        icon: LayoutGrid,
        routePattern: 'company.dashboard',
    },
    {
        title: 'Organization',
        href: companyRoutes.edit.url(),
        icon: Drone,
        routePattern: 'company.edit',
    },
    {
        title: 'Users',
        href: userRoutes.list.url(),
        icon: Users,
        routePattern: 'user',
    },
    {
        title: 'Sites',
        href: siteRoutes.list.url(),
        icon: MapPin,
        routePattern: ['site', 'building'],
    },
    {
        title: 'Projects',
        href: companyRoutes.project.list.url(),
        icon: Package,
        routePattern: 'company.project',
    },
    {
        title: 'Contractors',
        href: companyRoutes.contractor.list.url(),
        icon: HardHat,
        routePattern: 'company.contractor',
    },
    {
        title: 'Surveys',
        href: surveyRoutes.list.url(),
        icon: Binoculars,
        routePattern: 'survey',
    },
    {
        title: 'Jobs',
        href: jobRoutes.list.url(),
        icon: Hammer,
        routePattern: 'job',
    }
];

const clientNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: clientRoutes.dashboard.url(),
        icon: LayoutGrid,
        routePattern: 'client.dashboard',
    },
    {
        title: 'Organization',
        href: clientRoutes.edit.url(),
        icon: KeyRound,
        routePattern: 'client.edit',
    },
    {
        title: 'Users',
        href: userRoutes.list.url(),
        icon: Users,
        routePattern: 'user',
    },
    {
        title: 'Sites',
        href: siteRoutes.list.url(),
        icon: MapPin,
        routePattern: ['site', 'building'],
    },
    {
        title: 'Jobs',
        href: jobRoutes.list.url(),
        icon: Hammer,
        routePattern: 'job',
    },
];
*/
const adminNavItems: NavItem[] = [
    {
        title: 'Users',
        href: adminRoutes.user.list.url(),
        icon: Users,
        routePattern: 'admin.user',
    },
    {
        title: 'Roles',
        href: adminRoutes.role.list.url(),
        icon: BookUser,
        routePattern: 'admin.role',
    },
    {
        title: 'Companies',
        href: adminRoutes.company.list.url(),
        icon: HardHat,
        routePattern: 'admin.company',
    },
    {
        title: 'Clients',
        href: adminRoutes.client.list.url(),
        icon: KeyRound,
        routePattern: 'admin.client',
    },
    {
        title: 'Traces',
        href: adminRoutes.trace.list.url(),
        icon: Route,
        routePattern: ['admin.trace.*', 'admin.tracefilter.*'],
        children: [
            {
                title: 'Traces',
                href: adminRoutes.trace.list.url(),
                icon: Route,
                routePattern: 'admin.trace.*'
            },
            {
                title: 'Trace Filters',
                href: adminRoutes.tracefilter.list.url(),
                icon: ListFilter,
                routePattern: 'admin.tracefilter.*'
            },
        ]
    },
];
const footerNavItems: NavItem[] = [
    {
        title: 'Contact Us',
        href: policyRoutes.contact.url(),
        icon: Send,
        routePattern: 'policy.contact',
    },
    {
        title: 'Privacy Policy',
        href: policyRoutes.privacy.url(),
        icon: View,
        routePattern: 'policy.privacy',
    },
    {
        title: 'Terms of Service',
        href: policyRoutes.terms.url(),
        icon: Handshake,
        routePattern: 'policy.terms',
    },
    {
        title: 'Cookies Policy',
        href: policyRoutes.cookies.url(),
        icon: Cookie,
        routePattern: 'policy.cookies',
    },
    {
        title: 'Data Deletion Policy',
        href: policyRoutes.dataDeletion.url(),
        icon: Eraser,
        routePattern: 'policy.dataDeletion',
    },
    {
        title: `Copyright © ${new Date().getFullYear()}`,
        href: '#',
        icon: Copyright,
        routePattern: 'policy.copyright',
    },
];

const navItems = adminNavItems;
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
            <NavMain :items="navItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
