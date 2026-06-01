<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

// Import des icônes Heroicons (outline)
import {
  HomeIcon,
  MegaphoneIcon,
  UserGroupIcon,
  ShareIcon,
  UserIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const currentRoute = computed(() => page.url);

// Configuration réglementaire des 5 onglets : Accueil, News, Equipe, Partager, Profil
const items = [
    {
        title: 'Accueil',
        href: '/dashboard',
        icon: HomeIcon,
    },
    {
        title: 'News',
        href: '/announcements',
        icon: MegaphoneIcon,
    },
    {
        title: 'Équipe',
        href: '/team',
        icon: UserGroupIcon,
    },
    {
        title: 'Partager',
        href: '/share',
        icon: ShareIcon,
    },
    {
        title: 'Profil',
        href: '/settings/profile',
        icon: UserIcon,
    },
];

const isActive = (href: string) => {
    if (href === '/dashboard') {
        return currentRoute.value === '/dashboard';
    }
    return currentRoute.value.startsWith(href);
};
</script>

<template>
    <div class="fixed bottom-0 left-0 right-0 z-50 bg-[#05020c]/95 border-t border-white/5 px-2 pb-safe pt-2 backdrop-blur-md overflow-hidden shadow-[0_-5px_25px_rgba(0,0,0,0.8)]">
        <!-- Effet de lueur violette/cyan/émeraude défilante -->
        <div class="absolute top-0 left-0 w-full h-[2px] pointer-events-none">
            <div class="absolute top-0 h-full w-40 bg-gradient-to-r from-purple-500 via-cyan-400 to-emerald-400 animate-shimmer"></div>
        </div>

        <div class="flex justify-around items-center max-w-lg mx-auto h-14">
            <Link 
                v-for="item in items" 
                :key="item.href"
                :href="item.href"
                class="flex flex-col items-center justify-center gap-1 flex-1 py-1 group transition-all duration-300"
                :class="isActive(item.href) ? 'text-cyan-400 drop-shadow-[0_0_8px_rgba(6,182,212,0.6)]' : 'text-gray-500 hover:text-gray-300'"
            >
                <component 
                    :is="item.icon" 
                    class="w-6 h-6 transition-transform duration-200 group-active:scale-95 group-hover:scale-110"
                    :class="isActive(item.href) ? 'stroke-[3.2px]' : 'stroke-[2.6px]'"
                />
                <span class="text-[9px] font-mono tracking-wider font-bold">
                    {{ item.title }}
                </span>
            </Link>
        </div>
    </div>
</template>

<style scoped>
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 0px);
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}
.animate-shimmer {
    animation: shimmer 2.5s ease-in-out infinite;
}
</style>