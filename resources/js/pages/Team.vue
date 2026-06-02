<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Users, 
    CreditCard, 
    TrendingUp, 
    Coins, 
    Award,
    UsersRound,
    UserCheck,
    UserX,
    Calendar,
    ChevronDown
} from 'lucide-vue-next';

const props = defineProps<{
    referrals: Array<{
        id: number;
        phone: string;
        vip_level: number;
        joined_at: string;
        is_active: boolean;
        total_invested: number;
    }>;
    level2Referrals: Array<{
        id: number;
        phone: string;
        vip_level: number;
        joined_at: string;
        is_active: boolean;
    }>;
    level3Referrals: Array<{
        id: number;
        phone: string;
        vip_level: number;
        joined_at: string;
        is_active: boolean;
    }>;
    levelStats: {
        level1: { amount: number; count: number };
        level2: { amount: number; count: number };
        level3: { amount: number; count: number };
    };
    stats: {
        total_members: number;
        active_members: number;
        team_volume: number;
        total_commissions: number;
        referral_code: string;
        referral_link: string;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Équipe', href: '/team' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const activeTab = ref<1 | 2 | 3>(1);

const lvl1Amount = computed(() => props.levelStats?.level1?.amount || 0);
const lvl1Count = computed(() => props.levelStats?.level1?.count ?? 0);
const lvl2Amount = computed(() => props.levelStats?.level2?.amount || 0);
const lvl2Count = computed(() => props.levelStats?.level2?.count ?? 0);
const lvl3Amount = computed(() => props.levelStats?.level3?.amount || 0);
const lvl3Count = computed(() => props.levelStats?.level3?.count ?? 0);

const currentReferrals = computed(() => {
    if (activeTab.value === 1) return props.referrals || [];
    if (activeTab.value === 2) return props.level2Referrals || [];
    return props.level3Referrals || [];
});

const tabStats = computed(() => {
    if (activeTab.value === 1) return { amount: lvl1Amount.value, count: lvl1Count.value, label: 'Membres Directs' };
    if (activeTab.value === 2) return { amount: lvl2Amount.value, count: lvl2Count.value, label: 'Membres Niveau 2' };
    return { amount: lvl3Amount.value, count: lvl3Count.value, label: 'Membres Niveau 3' };
});

// Expand/collapse filleul list
const showFilleuls = ref(true);
const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Équipe" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">
            
            <!-- HEADER: Team Stats Banner -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg mx-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <UsersRound class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">Réseau d'Équipe</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Suivi de vos commissions multiniveaux</p>
                    </div>
                </div>
            </div>

            <!-- STATS ROW -->
            <div data-animate="fade-up" data-delay="100" class="grid grid-cols-2 gap-3.5 mx-4">
                <div class="bg-gradient-to-b from-[#0a0416] to-[#05020c] border border-purple-500/10 p-4 rounded-2xl text-center shadow-lg relative overflow-hidden">
                    <div class="text-[9px] text-slate-400 font-black uppercase tracking-widest block">Commissions</div>
                    <div class="text-[18px] font-black text-purple-400 font-mono mt-1.5 truncate">
                        {{ formatXAF(stats.total_commissions) }}
                    </div>
                </div>
                <div class="bg-gradient-to-b from-[#0a0416] to-[#05020c] border border-purple-500/10 p-4 rounded-2xl text-center shadow-lg relative overflow-hidden">
                    <div class="text-[9px] text-slate-400 font-black uppercase tracking-widest block">Volume Total</div>
                    <div class="text-[18px] font-black text-purple-400 font-mono mt-1.5 truncate">
                        {{ formatXAF(stats.team_volume) }}
                    </div>
                </div>
            </div>

            <!-- TABS: Niveau 1 / 2 / 3 -->
            <div data-animate="fade-up" data-delay="150" class="flex gap-2 mx-4">
                <button 
                    v-for="lvl in [1, 2, 3]" :key="lvl"
                    @click="activeTab = lvl as 1 | 2 | 3"
                    class="flex-1 py-2.5 rounded-xl text-[10px] font-black uppercase tracking-wider transition-all duration-300 border"
                    :class="activeTab === lvl 
                        ? 'bg-purple-500/15 border-purple-400 text-purple-400 shadow-[0_0_15px_rgba(168,85,247,0.2)]' 
                        : 'bg-black/30 border-purple-500/10 text-slate-400 hover:text-white hover:border-purple-500/30'"
                >
                    Niveau {{ lvl }}
                </button>
            </div>

            <!-- LEVEL STATS CARD -->
            <div data-animate="scale-up" data-delay="200" class="bg-gradient-to-b from-[#0a0416]/90 to-[#05020c]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative mx-4">
                
                <div class="flex items-center gap-2.5 mb-5 border-b border-purple-500/10 pb-4">
                    <div class="w-8 h-8 rounded-xl bg-purple-950/40 border border-purple-500/20 flex items-center justify-center text-purple-400">
                        <Users class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </div>
                    <h3 class="text-sm font-black text-white uppercase tracking-wider">{{ tabStats.label }}</h3>
                </div>

                <div class="bg-black/30 border border-purple-500/10 rounded-2xl p-4.5 flex flex-col gap-3 relative group hover:border-purple-400/40 transition-colors shadow">
                    <div class="flex justify-between items-center">
                        <div class="border border-purple-400/50 bg-purple-950/20 text-purple-400 font-extrabold text-[10px] px-3.5 py-0.5 rounded-full select-none">
                            Niveau {{ activeTab }}
                        </div>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">{{ tabStats.label }}</span>
                    </div>
                    
                    <div class="grid grid-cols-2 mt-1">
                        <div class="flex flex-col gap-1.5">
                            <span class="text-[10px] text-slate-400 font-bold flex items-center gap-1.5">
                                <CreditCard class="h-3.5 w-3.5 text-purple-400 shrink-0" :stroke-width="2.5" />
                                Montant rechargé
                            </span>
                            <span class="text-md font-black text-white font-mono leading-none">{{ formatXAF(tabStats.amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-1.5 pl-3 border-l border-white/5">
                            <span class="text-[10px] text-slate-400 font-bold flex items-center gap-1.5">
                                <Users class="h-3.5 w-3.5 text-purple-400 shrink-0" :stroke-width="2.5" />
                                Nombre de recharges
                            </span>
                            <span class="text-md font-black text-white font-mono leading-none">{{ tabStats.count }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILLEULS LIST -->
            <div class="bg-gradient-to-b from-[#0a0416]/90 to-[#05020c]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative mx-4">
                <button 
                    @click="showFilleuls = !showFilleuls"
                    class="flex items-center justify-between w-full mb-4 border-b border-purple-500/10 pb-4"
                >
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-purple-950/40 border border-purple-500/20 flex items-center justify-center text-purple-400">
                            <UsersRound class="h-4.5 w-4.5" :stroke-width="2.5" />
                        </div>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider">Filleuls Niveau {{ activeTab }}</h3>
                        <span class="bg-purple-500/15 text-purple-400 text-[9px] font-black px-2 py-0.5 rounded-full">{{ currentReferrals.length }}</span>
                    </div>
                    <ChevronDown 
                        class="h-4 w-4 text-purple-400 transition-transform duration-300"
                        :class="showFilleuls ? 'rotate-180' : ''"
                    />
                </button>

                <div v-if="showFilleuls" class="space-y-3">
                    <div v-if="currentReferrals.length === 0" class="py-8 text-center">
                        <UserX class="h-8 w-8 text-purple-400/20 mx-auto mb-3" :stroke-width="2.5" />
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Aucun filleul à ce niveau</p>
                    </div>

                    <div 
                        v-for="(ref, idx) in currentReferrals" :key="ref.id"
                        class="bg-black/30 border border-purple-500/10 rounded-2xl p-4 flex items-center gap-3 hover:border-purple-400/30 transition-all duration-300 group"
                        :style="{ animationDelay: (idx * 0.05) + 's' }"
                    >
                        <!-- Avatar placeholder -->
                        <div class="w-10 h-10 rounded-full border border-purple-500/20 bg-purple-950/20 flex items-center justify-center shrink-0">
                            <component 
                                :is="ref.is_active ? UserCheck : UserX" 
                                class="h-4.5 w-4.5"
                                :class="ref.is_active ? 'text-emerald-400' : 'text-slate-500'"
                                :stroke-width="2.5"
                            />
                        </div>

                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-black text-white font-mono truncate">{{ ref.phone }}</div>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-[9px] text-slate-400 font-bold flex items-center gap-1">
                                    <Calendar class="h-3 w-3" :stroke-width="2.5" />
                                    {{ ref.joined_at }}
                                </span>
                                <span 
                                    class="text-[8px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-wider"
                                    :class="ref.is_active ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/20' : 'bg-slate-500/10 text-slate-500 border border-slate-500/15'"
                                >
                                    {{ ref.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                        </div>

                        <!-- VIP Badge -->
                        <div class="text-right shrink-0">
                            <span class="text-[9px] text-purple-400 font-black bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded-full">
                                VIP {{ ref.vip_level || 1 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- COMMISSION SCHEME (Premium responsive cards grid) -->
            <div data-animate="fade-up" class="mx-4 flex flex-col gap-4">
                <div class="flex items-center gap-2 border-b border-purple-500/10 pb-2">
                    <Award class="h-5 w-5 text-purple-400" :stroke-width="2.5" />
                    <span class="text-xs font-black text-white uppercase tracking-wider">Grille de Commissions</span>
                </div>
                
                <div class="grid grid-cols-1 gap-3">
                    <!-- Niveau 1 Card (Purple Theme) -->
                    <div class="relative overflow-hidden rounded-2xl border border-purple-500/30 bg-gradient-to-r from-purple-950/30 via-purple-900/10 to-black/60 p-4 shadow-lg hover:border-purple-400/50 transition-all flex items-center justify-between">
                        <!-- Glow highlight -->
                        <div class="absolute -top-6 -right-6 w-16 h-16 rounded-full bg-purple-500/10 blur-md pointer-events-none"></div>
                        
                        <div class="flex items-center gap-3">
                            <!-- Icon badge -->
                            <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/25 flex items-center justify-center text-purple-400 shrink-0">
                                <Award class="h-5 w-5" :stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-purple-400 uppercase tracking-widest block font-mono">Niveau 1</span>
                                <span class="text-[12px] font-bold text-white block mt-0.5">Membres Directs</span>
                            </div>
                        </div>
                        
                        <div class="text-right flex flex-col items-end">
                            <span class="text-2xl font-black font-mono text-purple-400 leading-none">10%</span>
                            <span class="text-[8px] text-slate-400 uppercase font-black font-mono tracking-tighter mt-1">Commission</span>
                        </div>
                    </div>

                    <!-- Niveau 2 Card (Cyan Theme) -->
                    <div class="relative overflow-hidden rounded-2xl border border-cyan-500/30 bg-gradient-to-r from-cyan-950/30 via-cyan-900/10 to-black/60 p-4 shadow-lg hover:border-cyan-400/50 transition-all flex items-center justify-between">
                        <!-- Glow highlight -->
                        <div class="absolute -top-6 -right-6 w-16 h-16 rounded-full bg-cyan-500/10 blur-md pointer-events-none"></div>
                        
                        <div class="flex items-center gap-3">
                            <!-- Icon badge -->
                            <div class="w-10 h-10 rounded-xl bg-cyan-500/10 border border-cyan-500/25 flex items-center justify-center text-cyan-400 shrink-0">
                                <Award class="h-5 w-5" :stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-cyan-400 uppercase tracking-widest block font-mono">Niveau 2</span>
                                <span class="text-[12px] font-bold text-white block mt-0.5">Membres Indirects</span>
                            </div>
                        </div>
                        
                        <div class="text-right flex flex-col items-end">
                            <span class="text-2xl font-black font-mono text-cyan-400 leading-none">5%</span>
                            <span class="text-[8px] text-slate-400 uppercase font-black font-mono tracking-tighter mt-1">Commission</span>
                        </div>
                    </div>

                    <!-- Niveau 3 Card (Amber/Gold Theme) -->
                    <div class="relative overflow-hidden rounded-2xl border border-yellow-500/30 bg-gradient-to-r from-yellow-950/30 via-yellow-900/10 to-black/60 p-4 shadow-lg hover:border-yellow-400/50 transition-all flex items-center justify-between">
                        <!-- Glow highlight -->
                        <div class="absolute -top-6 -right-6 w-16 h-16 rounded-full bg-yellow-500/10 blur-md pointer-events-none"></div>
                        
                        <div class="flex items-center gap-3">
                            <!-- Icon badge -->
                            <div class="w-10 h-10 rounded-xl bg-yellow-500/10 border border-yellow-500/25 flex items-center justify-center text-yellow-400 shrink-0">
                                <Award class="h-5 w-5" :stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="text-[9px] font-black text-yellow-400 uppercase tracking-widest block font-mono">Niveau 3</span>
                                <span class="text-[12px] font-bold text-white block mt-0.5">Membres de l'Équipe</span>
                            </div>
                        </div>
                        
                        <div class="text-right flex flex-col items-end">
                            <span class="text-2xl font-black font-mono text-yellow-400 leading-none">2%</span>
                            <span class="text-[8px] text-slate-400 uppercase font-black font-mono tracking-tighter mt-1">Commission</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>