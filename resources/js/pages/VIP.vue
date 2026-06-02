<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Award, 
    ShieldCheck, 
    Lock,
    CheckCircle2
} from 'lucide-vue-next';

const props = defineProps<{
    stats: {
        personal_invested: number;
        active_referrals: number;
        team_volume: number;
        vip_level: number;
    };
}>();

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
        description: 'Débloquez plus de gains sur votre premier affilié actif.'
    },
    {
        level: 3,
        title: 'VIP 3 - Quantum Master',
        personal: 5000, // wait! In original DB, let's keep the original requirements: 50000, 200000, 3 refs
        volume: 200000,
        activeRefs: 3,
        salary: 500,
        description: 'Vitesse de retrait augmentée et gains accrus.'
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

const { containerRef } = useRevealAnimation();

const currentVipTitle = computed(() => {
    const levelObj = vipLevels.find(l => l.level === props.stats.vip_level);
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

            <!-- Link to VIP daily salary console -->
            <div data-animate="fade-up" data-delay="120" class="glass relative overflow-hidden rounded-2xl p-5 border border-purple-500/30 bg-gradient-to-r from-purple-950/40 via-[#13072b]/80 to-purple-950/40 shadow-lg glow-border">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(168,85,247,0.1),transparent)] opacity-60"></div>
                <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-center sm:text-left">
                        <h4 class="text-sm font-black text-white uppercase tracking-wider">Console Dividendes & Équipements VIP</h4>
                        <p class="text-[10px] text-slate-400 mt-1 leading-relaxed">
                            Réclamez votre salaire journalier de <span class="text-purple-400 font-bold font-mono">VIP {{ stats.vip_level }}</span> ou louez du matériel AVIP exclusif.
                        </p>
                    </div>
                    <Link href="/avip-products" class="w-full sm:w-auto px-5 py-2.5 bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-500 hover:to-fuchsia-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-[0_0_15px_rgba(168,85,247,0.4)] text-center">
                        ACCÉDER AU HUB AVIP
                    </Link>
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
                        v-for="v in vipLevels" 
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
    </AppLayout>
</template>
