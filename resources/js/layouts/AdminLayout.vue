<script setup lang="ts">
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { 
    ShieldAlert, 
    LayoutDashboard, 
    Users, 
    Clock, 
    Gift, 
    Cpu, 
    ArrowLeftRight,
    Menu,
    X,
    Activity,
    User
} from 'lucide-vue-next';

defineProps<{
    breadcrumbs?: Array<{ title: string; href: string }>;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const isMobileMenuOpen = ref(false);

const navItems = [
    { name: 'Contrôle Global', icon: LayoutDashboard, href: '/admin?tab=transactions' },
    { name: 'Flux de Retraits/Dépôts', icon: Clock, href: '/admin?tab=transactions' },
    { name: 'Registre Mineurs', icon: Users, href: '/admin?tab=users' },
    { name: 'Codes Cadeaux', icon: Gift, href: '/admin?tab=giftcodes' },
    { name: 'Catalogue Produits', icon: Cpu, href: '/admin?tab=products' },
];
</script>

<template>
    <div class="min-h-screen bg-[#05020c] text-gray-200 font-sans flex relative overflow-hidden">
        
        <!-- Background graphics -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <img src="https://images.unsplash.com/photo-1558494949-ef526b0042a0?q=80&w=2070&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover opacity-15" alt="background">
            <div class="absolute inset-0 bg-gradient-to-b from-[#05020c]/95 via-[#05020c]/90 to-[#0e061b]"></div>
            <div class="absolute top-20 left-1/4 w-96 h-96 bg-purple-500/5 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-1/4 w-[40rem] h-[40rem] bg-cyan-500/5 rounded-full blur-3xl"></div>
        </div>

        <!-- Sidebar for Desktop -->
        <aside class="hidden lg:flex flex-col w-64 bg-[#0a0518]/90 border-r border-white/5 relative z-20 shrink-0 backdrop-blur-md shadow-2xl">
            <!-- Sidebar Header with Logo -->
            <div class="p-6 border-b border-white/5 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.3)]">
                    <ShieldAlert class="h-5 w-5" :stroke-width="2.5" />
                </div>
                <div>
                    <h1 class="text-sm font-black text-white uppercase tracking-wider leading-none">Console Admin</h1>
                    <span class="text-[8px] font-bold uppercase tracking-widest text-cyan-400 font-mono mt-1 block">ARM HOLDING</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-2">
                <div class="text-[8px] font-black uppercase text-slate-500 tracking-widest px-3 mb-2 font-mono">Console de commandes</div>
                
                <Link 
                    v-for="item in navItems" 
                    :key="item.name" 
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-300 group hover:bg-white/[0.03] text-slate-400 hover:text-white"
                >
                    <component :is="item.icon" class="w-4.5 h-4.5 text-cyan-400 group-hover:scale-105 transition-transform" />
                    <span class="text-[11px] font-bold uppercase tracking-wider font-mono">{{ item.name }}</span>
                </Link>

                <div class="border-t border-white/5 my-4 pt-4">
                    <div class="text-[8px] font-black uppercase text-slate-500 tracking-widest px-3 mb-2 font-mono">Espace Client</div>
                    
                    <Link 
                        href="/dashboard"
                        class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-300 group hover:bg-white/[0.03] text-slate-400 hover:text-white"
                    >
                        <ArrowLeftRight class="w-4.5 h-4.5 text-purple-400" />
                        <span class="text-[11px] font-bold uppercase tracking-wider font-mono">Vue Client</span>
                    </Link>
                </div>
            </nav>

            <!-- Sidebar Footer with Administrator profile -->
            <div class="p-4 border-t border-white/5 bg-black/20 flex items-center justify-between">
                <div class="flex items-center gap-2.5 overflow-hidden w-full">
                    <div class="h-8 w-8 rounded-lg bg-cyan-950 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0">
                        <User class="w-4 h-4" />
                    </div>
                    <div class="min-w-0 flex-1 ml-1">
                        <div class="text-[10px] font-bold text-white font-mono truncate">{{ user?.phone }}</div>
                        <div class="text-[8px] font-black text-cyan-400 uppercase tracking-widest font-mono">SYSOP: ROOT</div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-w-0 z-30 relative">
            
            <!-- Navbar Header -->
            <header class="h-16 bg-[#0a0518]/50 border-b border-white/5 flex items-center justify-between px-4 lg:px-6 backdrop-blur-md">
                
                <!-- Burger menu for mobile -->
                <button 
                    @click="isMobileMenuOpen = true"
                    class="lg:hidden p-2 rounded-lg bg-white/5 border border-white/10 text-gray-300 hover:text-white shrink-0"
                >
                    <Menu class="w-5 h-5" />
                </button>

                <!-- Breadcrumbs -->
                <div class="hidden sm:flex items-center gap-2 font-mono text-[10px] uppercase tracking-wider text-slate-400">
                    <span class="text-cyan-400 font-bold">ARM HOLDING</span>
                    <span>/</span>
                    <span class="text-white font-bold">Administration</span>
                </div>

                <!-- Admin Status Panel -->
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2 bg-cyan-950/45 border border-cyan-500/25 px-3 py-1 rounded-full text-[9px] font-black uppercase text-cyan-400 tracking-wider">
                        <span class="h-2 w-2 rounded-full bg-cyan-400 animate-pulse"></span>
                        <Activity class="w-3 h-3 text-cyan-400 hidden" />
                        Infrastructure opérationnelle
                    </div>
                </div>
            </header>

            <!-- Page Content Scroll Area -->
            <main class="flex-1 overflow-y-auto no-scrollbar">
                <slot />
            </main>
        </div>

        <!-- Mobile Side Navigation Drawer Overlay -->
        <div v-if="isMobileMenuOpen" class="fixed inset-0 z-50 flex lg:hidden bg-black/85 backdrop-blur-sm">
            <div class="w-64 bg-[#0a0518] border-r border-white/10 h-full p-6 flex flex-col justify-between animate-fadeInRight relative">
                <!-- Close Button -->
                <button 
                    @click="isMobileMenuOpen = false"
                    class="absolute top-4 right-4 p-1.5 rounded-lg bg-white/5 border border-white/10 text-slate-400 hover:text-white"
                >
                    <X class="w-4 h-4" />
                </button>

                <div>
                    <!-- Logo -->
                    <div class="flex items-center gap-3 mb-8">
                        <div class="h-10 w-10 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                            <ShieldAlert class="h-5 w-5" />
                        </div>
                        <div>
                            <h1 class="text-sm font-black text-white uppercase tracking-wider">SYSOP</h1>
                            <span class="text-[8px] font-bold text-cyan-400 uppercase tracking-widest mt-1 block">ARM ADMIN</span>
                        </div>
                    </div>

                    <!-- Links -->
                    <nav class="space-y-2">
                        <Link 
                            v-for="item in navItems" 
                            :key="item.name" 
                            :href="item.href"
                            @click="isMobileMenuOpen = false"
                            class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-300 text-slate-400 hover:text-white"
                        >
                            <component :is="item.icon" class="w-4.5 h-4.5 text-cyan-400" />
                            <span class="text-[11px] font-bold uppercase tracking-wider font-mono">{{ item.name }}</span>
                        </Link>

                        <div class="border-t border-white/5 my-4 pt-4 space-y-2">
                            <Link 
                                href="/dashboard"
                                @click="isMobileMenuOpen = false"
                                class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-300 text-slate-400 hover:text-white"
                            >
                                <ArrowLeftRight class="w-4.5 h-4.5 text-purple-400" />
                                <span class="text-[11px] font-bold uppercase tracking-wider font-mono">Vue Client</span>
                            </Link>
                        </div>
                    </nav>
                </div>

                <div class="p-3 border-t border-white/5 bg-black/20 rounded-xl flex items-center gap-3">
                    <div class="h-8 w-8 rounded-lg bg-cyan-950 border border-cyan-500/30 flex items-center justify-center text-cyan-400 shrink-0">
                        <User class="w-4 h-4" />
                    </div>
                    <div class="min-w-0">
                        <div class="text-[10px] font-bold text-white font-mono truncate">{{ user?.phone }}</div>
                        <div class="text-[8px] font-black text-cyan-400 uppercase tracking-widest mt-1 block">ROOT</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</template>

<style scoped>
@keyframes fadeInRight {
    from { opacity: 0; transform: translateX(-50px); }
    to { opacity: 1; transform: translateX(0); }
}
.animate-fadeInRight {
    animation: fadeInRight 0.3s ease-out forwards;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
