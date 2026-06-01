<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    TrendingUp,
    Coins,
    Award,
    Calendar,
    Zap,
    Clock,
    DollarSign,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps<{
    todayEarnings: number;
    weekEarnings: number;
    totalEarnings: number;
    referralCommissions: number;
    generationEarnings: number;
    earningsHistory: Array<{
        id: number;
        amount: number;
        type: string;
        reference: string;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Mes Gains', href: '/gains' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const getTransactionLabel = (type: string, reference: string) => {
    if (reference.startsWith('COM-')) return 'Commission Réseau';
    if (reference.startsWith('CKI-')) return 'Synchronisation Quotidienne';
    if (reference.startsWith('GIFT-')) return 'Code Cadeau Promo';
    if (type === 'claim') return 'Réclamation Profit Nœud';
    if (type === 'earnings') return 'Bonus Réseau';
    return type;
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Mes Gains" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <TrendingUp class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">Mes Gains</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Rendements & commissions d'infrastructure</p>
                    </div>
                </div>
            </div>

            <!-- CORE STATS CARD (TOTAL) -->
            <div data-animate="scale-up" data-delay="100" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm text-center relative overflow-hidden">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(168,85,247,0.08),transparent_70%)] pointer-events-none"></div>
                <span class="text-[9px] text-slate-400 uppercase font-black tracking-widest block">Cumul des Gains Générés</span>
                <div class="text-3xl font-mono font-black text-purple-400 mt-2 filter drop-shadow-[0_0_15px_rgba(168,85,247,0.4)]">
                    {{ formatXAF(totalEarnings) }}
                </div>
            </div>

            <!-- SUB-STATS GRIDS -->
            <div data-animate="fade-up" data-delay="200" class="grid grid-cols-2 gap-3.5">
                <div class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 p-4 rounded-2xl text-center shadow-lg card-hover-lift">
                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest block">Gains du Jour</span>
                    <div class="text-base font-black text-white font-mono mt-1.5 truncate">
                        {{ formatXAF(todayEarnings) }}
                    </div>
                </div>
                <div class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 p-4 rounded-2xl text-center shadow-lg card-hover-lift">
                    <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest block">Gains Semaine</span>
                    <div class="text-base font-black text-white font-mono mt-1.5 truncate">
                        {{ formatXAF(weekEarnings) }}
                    </div>
                </div>
            </div>

            <!-- DISTRIBUTION OF EARNINGS -->
            <div data-animate="fade-up" data-delay="300" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-md">
                <h3 class="text-xs font-black text-white uppercase tracking-wider mb-4">Répartition des Revenus</h3>

                <div class="space-y-4">
                    <div class="bg-black/30 border border-purple-500/10 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 shrink-0">
                                <Zap class="h-4.5 w-4.5" :stroke-width="2.5" />
                            </div>
                            <div>
                                <div class="text-xs font-black text-white uppercase tracking-wider">Profits Nœuds (Calcul)</div>
                                <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Production journalière AI</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-mono font-black text-purple-400">{{ formatXAF(generationEarnings) }}</div>
                        </div>
                    </div>

                    <div class="bg-black/30 border border-purple-500/10 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-fuchsia-500/10 border border-fuchsia-500/20 flex items-center justify-center text-fuchsia-400 shrink-0">
                                <Award class="h-4.5 w-4.5" :stroke-width="2.5" />
                            </div>
                            <div>
                                <div class="text-xs font-black text-white uppercase tracking-wider">Commissions Réseau</div>
                                <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Parrainage multiniveau (3 Lvl)</span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs font-mono font-black text-fuchsia-400">{{ formatXAF(referralCommissions) }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DETAILED GAINS HISTORY -->
            <div data-animate="fade-up" data-delay="400" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm">
                <div class="flex items-center gap-2.5 mb-4 border-b border-purple-500/10 pb-4">
                    <div class="w-8 h-8 rounded-xl bg-purple-950/40 border border-purple-500/20 flex items-center justify-center text-purple-400">
                        <Clock class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </div>
                    <h3 class="text-sm font-black text-white uppercase tracking-wider">Détails des opérations</h3>
                </div>

                <div v-if="!earningsHistory || earningsHistory.length === 0" class="py-8 text-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                    Aucun gain généré actuellement
                </div>

                <div v-else class="space-y-3 max-h-[360px] overflow-y-auto pr-1">
                    <div 
                        v-for="hist in earningsHistory" :key="hist.id"
                        class="bg-black/30 border border-purple-500/10 rounded-2xl p-4 flex items-center justify-between hover:border-purple-400/20 transition-all duration-300"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center shrink-0">
                                <CheckCircle2 class="h-4 w-4 text-emerald-400" :stroke-width="2.5" />
                            </div>
                            <div>
                                <div class="text-xs font-black text-white font-mono">{{ getTransactionLabel(hist.type, hist.reference) }}</div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <Clock class="h-3 w-3 text-slate-500" :stroke-width="2.5" />
                                    <span class="text-[9px] text-slate-400 font-mono">{{ formatDate(hist.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                        <span class="text-xs font-black font-mono text-emerald-400 shrink-0">
                            +{{ formatXAF(hist.amount) }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
