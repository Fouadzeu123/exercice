<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText, MessageSquare, Megaphone, LayoutGrid, Cpu, Zap, Users, Wallet, Lock, Award, CreditCard } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
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
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { NavItem } from '@/types';

const page = usePage();
const isAdmin = computed(() => page.props.auth?.user?.role === 'admin');

const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Console Synchrone',
        href: '/generate',
        icon: Zap,
    },
    {
        title: 'ARM Vaults',
        href: '/vaults',
        icon: Lock,
    },
    {
        title: 'Mon Réseau',
        href: '/team',
        icon: Users,
    },
    {
        title: 'Privilèges VIP',
        href: '/vip',
        icon: Award,
    },
    {
        title: 'Dépôt (Recharger)',
        href: '/recharger',
        icon: Wallet,
    },
    {
        title: 'Retrait (Gains)',
        href: '/retirer',
        icon: CreditCard,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Livre Blanc',
        href: '#whitepaper',
        icon: FileText,
    },
    {
        title: 'Actualités Réseau',
        href: '/announcements',
        icon: Megaphone,
    },
    {
        title: 'Support AI',
        href: 'https://t.me/arm_holding_support',
        icon: MessageSquare,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/dashboard">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <div v-if="isAdmin" class="mt-4">
              <SidebarMenuItem>
                <SidebarMenuButton size="lg" as-child>
                  <Link href="/admin">
                    <LayoutGrid class="mr-2 h-4 w-4" />
                    <span>Admin</span>
                  </Link>
                </SidebarMenuButton>
              </SidebarMenuItem>
            </div>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
