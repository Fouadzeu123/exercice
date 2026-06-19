<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import axios from 'axios';
import { 
    Award, 
    ShieldCheck, 
    Lock,
    CheckCircle2,
    Coins,
    X
} from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        personal_invested: number;
        active_referrals: number;
        team_volume: number;
        vip_level: number;
        avip_level: number;
    };
}>();

// Page and Auth Context
const page = usePage();
const user = computed(() => page.props.auth?.user as any);

// State for salary claim
const claimProcessing = ref(false);
const showClaimSuccessModal = ref(false);
const claimedAmount = ref(0);
const showErrorModal = ref(false);
const errorMessage = ref('');

const dailySalaries = computed(() => {
    return (page.props.settings as any)?.vip_salaries || {
        0: 0,
        1: 100,
        2: 250,
        3: 500,
        4: 1000,
        5: 2000,
    };
});

const userDailyRate = computed(() => {
    const rate = dailySalaries.value[props.stats.vip_level as keyof typeof dailySalaries.value];
    return rate !== undefined ? rate : 0;
});

const isSalaryClaimedToday = computed(() => {
    if (!user.value?.last_salary_claim_date) return false;
    const lastClaim = new Date(user.value.last_salary_claim_date).getTime();
    const now = Date.now();
    return (now - lastClaim) < 24 * 60 * 60 * 1000;
});

const handleClaimSalary = async () => {
    if (claimProcessing.value || isSalaryClaimedToday.value || props.stats.vip_level < 1) return;
    claimProcessing.value = true;
    try {
        const res = await axios.post('/avip-products/claim-salary');
        
        // Update user properties locally so the UI updates instantly
        if (user.value) {
            user.value.balance = res.data.new_balance;
            user.value.last_salary_claim_date = new Date().toISOString();
        }
        
        claimedAmount.value = res.data.salary_amount;
        showClaimSuccessModal.value = true;
        claimProcessing.value = false;
        
        // Reload inertia props to sync in the background
        router.reload({
            only: ['auth', 'stats']
        });
    } catch (e: any) {
        claimProcessing.value = false;
        errorMessage.value = e.response?.data?.error || "Une erreur est survenue lors de la réclamation.";
        showErrorModal.value = true;
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de Bord',
        href: '/dashboard',
    },
    {
        title: 'Statut VIP',
        href: '/vip',
    },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
};

const vipLevels = [
    {
        level: 0,
        title: 'VIP 0 - Débutant Silicon',
        personal: 0,
        volume: 0,
        activeRefs: 0,
        salary: 0,
        description: 'Statut de base attribué automatiquement lors de votre inscription.'
    },
    {
        level: 1,
        title: 'VIP 1 - Pionnier Silicon',
        personal: 15000,
        volume: 0,
        activeRefs: 0,
        salary: 100,
        description: 'Activé dès 15 000 FCFA d\'investissement personnel. Donne droit au salaire journalier pendant 7 jours.'
    },
    {
        level: 2,
        title: 'VIP 2 - Silicon Avancé',
        personal: 15000,
        volume: 50000,
        activeRefs: 1,
        salary: 250,
        description: 'Débloquez plus de gains sur votre premier affilié actif. Donne droit au salaire journalier pendant 30 jours.'
    },
    {
        level: 3,
        title: 'VIP 3 - Quantum Master',
        personal: 50000,
        volume: 200000,
        activeRefs: 3,
        salary: 500,
        description: 'Vitesse de retrait augmentée et gains accrus. Donne droit au salaire journalier pendant 30 jours.'
    },
    {
        level: 4,
        title: 'VIP 4 - Neural Overlord',
        personal: 150000,
        volume: 1000000,
        activeRefs: 5,
        salary: 1000,
        description: 'Accès anticipé aux nœuds en édition limitée.'
    },
    {
        level: 5,
        title: 'VIP 5 - ARM Sovereign',
        personal: 500000,
        volume: 5000000,
        activeRefs: 10,
        salary: 2000,
        description: 'Le statut VIP ultime de notre écosystème.'
    }
];

// Let's adjust VIP 3 personal amount back to original (50000) so we don't change logic!
vipLevels[3].personal = 50000;

const dynamicVipLevels = computed(() => {
    const salaries = dailySalaries.value;
    return vipLevels.map(v => ({
        ...v,
        salary: salaries[v.level as keyof typeof salaries] !== undefined 
            ? parseFloat(salaries[v.level as keyof typeof salaries] as any) 
            : v.salary
    }));
});

const { containerRef } = useRevealAnimation();

const currentVipTitle = computed(() => {
    const levelObj = dynamicVipLevels.value.find(l => l.level === props.stats.vip_level);
    return levelObj ? levelObj.title.split(' - ')[1] : 'Débutant Silicon';
});
</script>

<template>
    <Head title="Privilèges VIP" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header -->
            <div data-animate="fade-down" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div>
                    <h2 class="text-xl md:text-2xl font-semibold text-white">Privilèges Technologiques VIP</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Augmentez votre puissance de calcul et optimisez vos retours en gravissant les échelons du réseau.</p>
                </div>
            </div>

            <!-- Current User Levels Overview -->
            <div data-animate="fade-up" data-delay="100" class="grid grid-cols-1 gap-6">
                <!-- VIP card -->
                <div class="glass relative overflow-hidden rounded-2xl p-6 border border-primary/20 bg-primary/[0.01]">
                    <div class="absolute right-[-10%] top-[-10%] w-32 h-32 bg-primary/10 rounded-full blur-3xl"></div>
                    <div class="flex items-center gap-4">
                        <div class="h-14 w-14 rounded-xl bg-primary/10 border border-primary/20 flex items-center justify-center text-primary shadow-[0_0_15px_rgba(168,85,247,0.25)] animate-pulse">
                            <Award class="h-7 w-7" :stroke-width="2.5" />
                        </div>
                        <div>
                            <span class="text-[10px] text-muted-foreground uppercase font-bold tracking-widest block">Votre Niveau Actuel</span>
                            <h3 class="text-lg font-black text-white mt-1">VIP {{ stats.vip_level }} - {{ currentVipTitle }}</h3>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-4 border-t border-white/5 pt-6 mt-6 text-xs">
                        <div>
                            <span class="text-muted-foreground block text-[10px] uppercase">Investi Personnel</span>
                            <span class="font-bold text-white font-mono mt-1 block">{{ formatXAF(stats.personal_invested) }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[10px] uppercase">Volume Réseau Collecté</span>
                            <span class="font-bold text-white font-mono mt-1 block">{{ formatXAF(stats.team_volume) }}</span>
                        </div>
                        <div>
                            <span class="text-muted-foreground block text-[10px] uppercase">Affiliés Actifs</span>
                            <span class="font-bold text-white font-mono mt-1 block">{{ stats.active_referrals }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Console de Réclamation de Salaire Journalier -->
            <div data-animate="fade-up" data-delay="120" class="glass relative overflow-hidden rounded-2xl p-5 border border-purple-500/30 bg-gradient-to-r from-purple-950/40 via-[#13072b]/80 to-purple-950/40 shadow-lg glow-border">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(168,85,247,0.1),transparent)] opacity-60"></div>
                <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-center sm:text-left">
                        <h4 class="text-sm font-black text-white uppercase tracking-wider">Réclamation de Salaire Journalier</h4>
                        <p class="text-[10px] text-slate-400 mt-1 leading-relaxed">
                            Réclamez votre salaire d'infrastructure calculé directement sur votre niveau VIP (Disponible du lundi au vendredi).
                        </p>
                    </div>
                    
                    <button
                        @click="handleClaimSalary"
                        :disabled="isSalaryClaimedToday || claimProcessing || stats.vip_level < 1"
                        class="w-full sm:w-auto px-6 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all duration-300 flex items-center justify-center gap-2"
                        :class="stats.vip_level < 1
                            ? 'bg-purple-950/20 text-slate-500 border border-white/5 cursor-not-allowed'
                            : (isSalaryClaimedToday
                                ? 'bg-emerald-950/40 text-emerald-400 border border-emerald-800/30 cursor-not-allowed'
                                : 'bg-purple-600 hover:bg-purple-500 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)] active:scale-[0.98]')"
                    >
                        <CheckCircle2 v-if="isSalaryClaimedToday" class="w-4 h-4" />
                        <Coins v-else class="w-4 h-4" :class="claimProcessing ? 'animate-spin' : ''" />
                        {{ stats.vip_level < 1 ? 'AUCUN SALAIRE DISPONIBLE (VIP 0)' : (isSalaryClaimedToday ? 'SALAIRE RÉCLAMÉ' : (claimProcessing ? 'SYNCHRONISATION...' : 'RÉCLAMER MON SALAIRE (' + formatXAF(userDailyRate) + ')')) }}
                    </button>
                </div>
            </div>

            <!-- VIP Levels Cards Grid -->
            <div data-animate="fade-up" data-delay="150" class="glass rounded-2xl p-6 border border-white/5">
                <div class="flex items-center justify-between mb-6 border-b border-white/5 pb-4">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2">
                        <ShieldCheck class="h-4.5 w-4.5 text-primary" :stroke-width="2.5" />
                        Grille des Rangs VIP Standard
                    </h3>
                    <span class="text-[9px] text-muted-foreground uppercase font-mono bg-white/5 px-2.5 py-1 rounded-lg">Réseau décentralisé</span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div 
                        v-for="v in dynamicVipLevels" 
                        :key="v.level"
                        class="group relative overflow-hidden rounded-2xl border p-5 transition-all duration-300 flex flex-col justify-between"
                        :class="stats.vip_level === v.level 
                            ? 'bg-gradient-to-b from-primary/10 via-primary/[0.02] to-transparent border-primary/50 shadow-[0_0_20px_rgba(168,85,247,0.2)]' 
                            : (stats.vip_level > v.level 
                                ? 'bg-emerald-950/[0.03] border-emerald-500/20' 
                                : 'bg-white/[0.01] border-white/5 opacity-70')"
                    >
                        <!-- Glow effect behind active card -->
                        <div v-if="stats.vip_level === v.level" class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(168,85,247,0.1),transparent_50%)] pointer-events-none"></div>

                        <div>
                            <!-- Card Header -->
                            <div class="flex items-center justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 rounded-xl flex items-center justify-center font-black text-sm transition-all"
                                        :class="stats.vip_level === v.level 
                                            ? 'bg-primary text-black shadow-[0_0_10px_rgba(168,85,247,0.4)]' 
                                            : (stats.vip_level > v.level ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-white/5 text-slate-400')"
                                    >
                                        V{{ v.level }}
                                    </div>
                                    <div>
                                        <h4 class="text-xs font-bold text-white group-hover:text-primary transition-colors">
                                            {{ v.title.split(' - ')[1] }}
                                        </h4>
                                        <span class="text-[9px] text-muted-foreground block mt-0.5">Rang {{ v.level }}</span>
                                    </div>
                                </div>

                                <!-- Status Badge -->
                                <span class="text-[8px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider font-mono border"
                                    :class="stats.vip_level === v.level
                                        ? 'bg-primary/10 text-primary border-primary/20 animate-pulse'
                                        : (stats.vip_level > v.level 
                                            ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' 
                                            : 'bg-white/5 text-slate-500 border-white/5')"
                                >
                                    {{ stats.vip_level === v.level ? 'ACTUEL' : (stats.vip_level > v.level ? 'DÉBLOQUÉ' : 'VERROUILLÉ') }}
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-400 leading-relaxed mb-4 min-h-[32px]">
                                {{ v.description }}
                            </p>

                            <!-- Daily Salary Highlight Box -->
                            <div class="bg-gradient-to-r rounded-xl p-3 border mb-5 flex items-center justify-between"
                                :class="stats.vip_level >= v.level
                                    ? 'from-purple-950/40 to-transparent border-purple-500/10'
                                    : 'from-black/40 to-transparent border-white/5'"
                            >
                                <div>
                                    <span class="text-[8px] text-slate-500 uppercase tracking-widest block font-bold font-mono">Dividende Quotidien</span>
                                    <span class="text-xs font-mono font-black mt-0.5 block"
                                        :class="v.salary > 0 ? 'text-primary' : 'text-slate-400'"
                                    >
                                        {{ v.salary > 0 ? '+' + formatXAF(v.salary) + ' / jour' : 'Aucun salaire' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Requirements Checklist -->
                        <div class="border-t border-white/5 pt-4 space-y-2.5 text-[10px]">
                            <span class="text-[8px] text-slate-500 uppercase tracking-wider block font-bold">Critères d'accès requis :</span>
                            
                            <!-- Criterion 1: Personal Investment -->
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Investissement Personnel</span>
                                <div class="flex items-center gap-1.5 font-mono font-bold">
                                    <span :class="stats.personal_invested >= v.personal ? 'text-emerald-400' : 'text-slate-500'">
                                        {{ formatXAF(stats.personal_invested) }}
                                    </span>
                                    <span class="text-slate-600">/</span>
                                    <span class="text-slate-300 font-mono">{{ formatXAF(v.personal) }}</span>
                                    
                                    <CheckCircle2 v-if="stats.personal_invested >= v.personal" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Lock v-else class="w-3 h-3 text-slate-500" />
                                </div>
                            </div>

                            <!-- Criterion 2: Team Volume -->
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Volume Réseau Collecté</span>
                                <div class="flex items-center gap-1.5 font-mono font-bold">
                                    <span :class="stats.team_volume >= v.volume ? 'text-emerald-400' : 'text-slate-500'">
                                        {{ formatXAF(stats.team_volume) }}
                                    </span>
                                    <span class="text-slate-600">/</span>
                                    <span class="text-slate-300 font-mono">{{ formatXAF(v.volume) }}</span>

                                    <CheckCircle2 v-if="stats.team_volume >= v.volume" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Lock v-else class="w-3 h-3 text-slate-500" />
                                </div>
                            </div>

                            <!-- Criterion 3: Active Referrals -->
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400">Affiliés Actifs Requis</span>
                                <div class="flex items-center gap-1.5 font-mono font-bold">
                                    <span :class="stats.active_referrals >= v.activeRefs ? 'text-emerald-400' : 'text-slate-500'">
                                        {{ stats.active_referrals }}
                                    </span>
                                    <span class="text-slate-600">/</span>
                                    <span class="text-slate-300 font-mono">{{ v.activeRefs }}</span>

                                    <CheckCircle2 v-if="stats.active_referrals >= v.activeRefs" class="w-3.5 h-3.5 text-emerald-400" />
                                    <Lock v-else class="w-3 h-3 text-slate-500" />
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- DIVIDEND SUCCESS CLAIM MODAL -->
        <Teleport to="body">
            <div v-if="showClaimSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0a0414] border border-emerald-500/30 rounded-3xl overflow-hidden shadow-2xl animate-scaleIn relative glow-border">
                    <div class="absolute -top-12 -right-12 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="p-6 text-center">
                        <div class="w-14 h-14 rounded-full bg-emerald-500/10 border border-emerald-500/25 flex items-center justify-center text-emerald-400 mx-auto mb-4 animate-bounce">
                            <CheckCircle2 class="h-7 w-7" :stroke-width="2.5" />
                        </div>
                        
                        <h3 class="text-sm font-black text-white uppercase tracking-wider mb-2">Salaire Réclamé</h3>
                        <p class="text-[10px] text-slate-400 leading-relaxed mb-6">
                            Votre dividende quotidien de co-traitement a été versé avec succès sur votre solde.
                        </p>
                        
                        <div class="bg-emerald-950/20 border border-emerald-500/10 rounded-2xl p-4.5 mb-6">
                            <span class="text-[8px] text-emerald-400 uppercase tracking-widest font-black block mb-1">Montant crédité</span>
                            <span class="text-xl font-mono font-black text-white block">
                                +{{ formatXAF(claimedAmount) }}
                            </span>
                        </div>
                        
                        <button @click="showClaimSuccessModal = false" class="w-full py-3.5 rounded-2xl bg-emerald-500 text-black font-black uppercase tracking-wider text-xs hover:bg-emerald-400 transition-all shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                            CONFIRMER
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ERROR MODAL -->
        <Teleport to="body">
            <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0a0414] border border-rose-500/30 rounded-3xl overflow-hidden shadow-2xl animate-scaleIn relative glow-border">
                    <div class="absolute -top-12 -right-12 w-28 h-28 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>
                    <div class="p-6 text-center">
                        <div class="w-14 h-14 rounded-full bg-rose-500/10 border border-rose-500/25 flex items-center justify-center text-rose-400 mx-auto mb-4">
                            <X class="h-6 w-6" :stroke-width="3" />
                        </div>
                        
                        <h3 class="text-sm font-black text-white uppercase tracking-wider mb-2">Opération Échouée</h3>
                        <p class="text-[10.5px] text-slate-400 leading-relaxed mb-6">
                            {{ errorMessage }}
                        </p>
                        
                        <button @click="showErrorModal = false" class="w-full py-3.5 rounded-2xl bg-rose-500 text-black font-black uppercase tracking-wider text-xs hover:bg-rose-400 transition-all shadow-[0_0_15px_rgba(244,63,94,0.4)]">
                            RETOUR
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>
