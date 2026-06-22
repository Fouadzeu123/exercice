<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Cpu,
    Calendar,
    Coins,
    TrendingUp,
    Shield,
    CheckCircle2,
    Clock,
    ShoppingBag
} from 'lucide-vue-next';

const props = defineProps<{
    orders: Array<{
        id: number;
        node_id: number;
        node_name: string;
        node_amount: number;
        generation_profit: number;
        duration: number;
        technology_level: number;
        image_url: string;
        active: boolean;
        created_at: string;
        expires_at: string | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Mes Commandes', href: '/commandes' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Convert relative /uploads/ paths to absolute URLs (needed for mobile WebViews)
const resolveImageUrl = (url: string | null | undefined): string => {
    if (!url) return '/images/cyber_server_hero.png';
    if (url.startsWith('http://') || url.startsWith('https://')) return url;
    if (url.startsWith('/uploads/')) return 'https://armicm.com' + url;
    if (url.startsWith('/')) return window.location.origin + url;
    return url;
};

const getDaysRemaining = (expiresAt: string | null): string => {
    if (!expiresAt) return 'Illimité';
    const now = new Date();
    const end = new Date(expiresAt);
    if (end <= now) return 'Expiré';
    let count = 0;
    const cur = new Date(now);
    cur.setHours(0, 0, 0, 0);
    const endDay = new Date(end);
    endDay.setHours(0, 0, 0, 0);
    while (cur < endDay) {
        const dow = cur.getDay();
        if (dow !== 0 && dow !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count > 0 ? `${count} jours restants` : 'Expiré';
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Mes Commandes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <ShoppingBag class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">Mes Serveurs</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Historique de vos acquisitions</p>
                    </div>
                </div>
                <span class="bg-purple-500/15 text-purple-400 text-[10px] font-black px-2.5 py-1 rounded-full border border-purple-500/20">
                    {{ orders.length }} Actifs
                </span>
            </div>

            <!-- ORDERS LIST -->
            <div class="space-y-4" data-stagger>
                <div v-if="orders.length === 0" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-10 text-center shadow-2xl backdrop-blur-sm">
                    <Cpu class="h-10 w-10 text-purple-400/20 mx-auto mb-3.5 animate-pulse" :stroke-width="2.5" />
                    <p class="text-xs font-black text-white uppercase tracking-wider mb-2">Aucun serveur actif</p>
                    <p class="text-[10px] text-slate-400 leading-relaxed max-w-xs mx-auto mb-5">Visitez la console de calcul ou le marché des nœuds pour activer votre premier serveur technologique.</p>
                    <Link 
                        href="/dashboard"
                        class="inline-flex py-2.5 px-6 rounded-xl bg-purple-500 text-black font-extrabold text-[10px] uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300"
                    >
                        Acheter un Serveur
                    </Link>
                </div>

                <div 
                    v-for="(order, idx) in orders" :key="order.id"
                    data-animate="fade-up" :data-delay="String(idx * 100)"
                    class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative overflow-hidden group hover:border-purple-400/30 transition-all duration-300 card-hover-lift"
                >
                    <!-- Server image background indicator -->
                    <div class="absolute right-0 top-0 w-32 h-32 opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-110">
                        <img 
                            :src="resolveImageUrl(order.image_url)" 
                            @error="(e: Event) => ((e.target as HTMLImageElement).src = '/images/cyber_server_hero.png')"
                            class="w-full h-full object-contain" 
                            alt=""
                        />
                    </div>

                    <div class="flex items-center gap-3 pb-3.5 border-b border-purple-500/10">
                        <div class="w-12 h-12 rounded-2xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center shrink-0">
                            <Cpu class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                        </div>
                        <div>
                            <div class="text-xs font-black text-white uppercase tracking-wider">{{ order.node_name }}</div>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[8px] font-black bg-purple-500/10 border border-purple-500/20 text-purple-400 px-1.5 py-0.5 rounded uppercase">
                                    Niveau {{ order.technology_level }}
                                </span>
                                <span class="text-[8px] font-black bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded uppercase">
                                    Actif
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4 text-xs font-mono">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Coins class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Valeur
                            </span>
                            <span class="text-xs font-black text-white">{{ formatXAF(order.node_amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <TrendingUp class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Gain Quotidien
                            </span>
                            <span class="text-xs font-black text-emerald-400">+{{ formatXAF(order.generation_profit) }}/j</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Calendar class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Date d'Acquisition
                            </span>
                            <span class="text-xs font-black text-white">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Clock class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Durée Restante
                            </span>
                            <span class="text-xs font-black text-purple-400 font-bold">{{ getDaysRemaining(order.expires_at) }}</span>
                        </div>
                    </div>

                    <!-- Footnote security -->
                    <div class="mt-4 p-2.5 rounded-xl bg-purple-950/20 border border-purple-500/10 flex items-center gap-2">
                        <Shield class="h-3.5 w-3.5 text-purple-400 shrink-0" :stroke-width="2.5" />
                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Algorithme de calcul sécurisé ARM Holdings PLC</span>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>
