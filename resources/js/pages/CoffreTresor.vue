<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    Gift,
    Users,
    TrendingUp,
    ShieldAlert,
    CheckCircle2,
    Lock,
    Award,
    Sparkles,
    HelpCircle,
    ArrowRight
} from 'lucide-vue-next';

const props = defineProps<{
    todayInvitations: number;
    tiers: Array<{
        invitations: number;
        reward: number;
        label: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Coffre au Trésor', href: '/coffre-tresor' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

// Calculate progress percentage
const maxInvitations = computed(() => {
    if (!props.tiers || props.tiers.length === 0) return 20;
    return props.tiers[props.tiers.length - 1].invitations;
});

const progressPercent = computed(() => {
    return Math.min(100, (props.todayInvitations / maxInvitations.value) * 100);
});

const getTierStatus = (invitationsNeeded: number) => {
    if (props.todayInvitations >= invitationsNeeded) return 'completed';
    return 'locked';
};

const getChestImage = (label?: string) => {
    return '/images/golden_chest.png';
};

const getTierTheme = (label: string) => {
    switch (label) {
        case 'Bronze':
            return {
                border: 'border-purple-500/20 hover:border-purple-400',
                glow: 'shadow-[0_0_15px_rgba(168,85,247,0.05)] hover:shadow-[0_0_20px_rgba(168,85,247,0.15)]',
                text: 'text-purple-400',
                activeBorder: 'border-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.3)] bg-purple-950/20'
            };
        case 'Argent':
            return {
                border: 'border-cyan-500/20 hover:border-cyan-400',
                glow: 'shadow-[0_0_15px_rgba(6,182,212,0.05)] hover:shadow-[0_0_20px_rgba(6,182,212,0.15)]',
                text: 'text-cyan-400',
                activeBorder: 'border-cyan-500 shadow-[0_0_15px_rgba(6,182,212,0.3)] bg-cyan-950/20'
            };
        case 'Or':
            return {
                border: 'border-yellow-500/20 hover:border-yellow-400',
                glow: 'shadow-[0_0_15px_rgba(234,179,8,0.05)] hover:shadow-[0_0_20px_rgba(234,179,8,0.15)]',
                text: 'text-yellow-400',
                activeBorder: 'border-yellow-500 shadow-[0_0_15px_rgba(234,179,8,0.3)] bg-yellow-950/20'
            };
        case 'Platine':
            return {
                border: 'border-indigo-500/20 hover:border-indigo-400',
                glow: 'shadow-[0_0_15px_rgba(99,102,241,0.05)] hover:shadow-[0_0_20px_rgba(99,102,241,0.15)]',
                text: 'text-indigo-400',
                activeBorder: 'border-indigo-500 shadow-[0_0_15px_rgba(99,102,241,0.3)] bg-indigo-950/20'
            };
        case 'Diamant':
            return {
                border: 'border-emerald-500/20 hover:border-emerald-400',
                glow: 'shadow-[0_0_15px_rgba(16,185,129,0.05)] hover:shadow-[0_0_20px_rgba(16,185,129,0.15)]',
                text: 'text-emerald-400',
                activeBorder: 'border-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.3)] bg-emerald-950/20'
            };
        default:
            return {
                border: 'border-purple-500/20 hover:border-purple-400',
                glow: 'shadow-[0_0_15px_rgba(168,85,247,0.05)] hover:shadow-[0_0_20px_rgba(168,85,247,0.15)]',
                text: 'text-purple-400',
                activeBorder: 'border-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.3)] bg-purple-950/20'
            };
    }
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Coffre au Trésor" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-cyan-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-cyan-500/10 shadow-lg mx-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-cyan-500/20 bg-cyan-950/20 flex items-center justify-center text-cyan-400 shadow-[0_0_10px_rgba(6,182,212,0.15)]">
                        <Gift class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">Coffre au Trésor</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Bonus quotidiens d'invitations</p>
                    </div>
                </div>
            </div>

            <!-- STATUS BOX -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-cyan-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative mx-4">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <span class="text-[9px] text-slate-400 uppercase font-black tracking-widest block">Invitations du Jour</span>
                        <div class="text-2xl font-mono font-black text-cyan-400 mt-1">
                            {{ todayInvitations }}
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="text-[9px] text-slate-400 uppercase font-black tracking-widest block">Statut Actuel</span>
                        <div class="text-xs font-black text-white bg-cyan-500/10 border border-cyan-500/20 px-3 py-1 rounded-full uppercase tracking-wider mt-1.5 flex items-center gap-1.5 justify-end">
                            <Sparkles class="h-3.5 w-3.5 text-cyan-400 animate-spin-slow" :stroke-width="2.5" />
                            Synchronisé
                        </div>
                    </div>
                </div>

                <!-- PROGRESS BAR -->
                <div class="space-y-2 mt-5">
                    <div class="flex justify-between text-[9px] text-slate-400 font-bold uppercase tracking-wider">
                        <span>Objectif journalier</span>
                        <span>{{ todayInvitations }} / {{ maxInvitations }}</span>
                    </div>
                    <div class="w-full h-3.5 bg-black/60 rounded-full border border-cyan-500/20 p-0.5 overflow-hidden">
                        <div
                            class="h-full bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all duration-1000 shadow-[0_0_10px_rgba(6,182,212,0.5)]"
                            :style="{ width: progressPercent + '%' }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- EXPLAINER -->
            <div data-animate="scale-up" data-delay="150" class="p-4 rounded-2xl border border-cyan-500/10 bg-cyan-950/5 relative overflow-hidden shadow text-center mx-4">
                <HelpCircle class="h-5 w-5 text-cyan-400 mx-auto mb-2" :stroke-width="2.5" />
                <p class="text-[10px] text-slate-300 font-bold uppercase tracking-wider mb-1">Règlement du Coffre au Trésor</p>
                <p class="text-[12px] text-slate-400 leading-relaxed">
                    Invitez des partenaires à recharger et activer un nœud de calcul le même jour pour déverrouiller les paliers correspondants. Les récompenses sont automatiquement créditées sur votre balance.
                </p>
            </div>

            <!-- TIERS CARDS -->
            <div data-stagger="true" class="space-y-3.5 mx-4">
                <h3 class="text-xs font-black text-white uppercase tracking-wider mb-2">Paliers de Récompenses</h3>

                <div
                    v-for="tier in tiers" :key="tier.invitations"
                    data-animate="fade-up"
                    class="group relative bg-[#0a0416] border rounded-3xl overflow-hidden transition-all duration-300 shadow-lg flex flex-col sm:flex-row items-stretch min-h-[140px]"
                    :class="getTierStatus(tier.invitations) === 'completed'
                        ? getTierTheme(tier.label).activeBorder
                        : 'border-white/5 ' + getTierTheme(tier.label).border + ' ' + getTierTheme(tier.label).glow"
                >
                    <!-- Left Column: Chest Image Area -->
                    <div class="w-full sm:w-40 h-36 sm:h-auto overflow-hidden relative bg-black/20 shrink-0">
                        <img :src="getChestImage(tier.label)" :alt="tier.label" class="w-full h-full object-cover opacity-85 group-hover:opacity-100 transition-all duration-500 group-hover:scale-105" />
                        <!-- Neon gradient bar overlay -->
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-[#be46ff] to-transparent animate-pulse" :class="getTierStatus(tier.invitations) === 'completed' ? 'via-emerald-400' : ''"></div>
                    </div>

                    <!-- Right Column: Info & Rewards -->
                    <div class="p-4 flex-1 flex flex-col justify-between relative z-10 bg-gradient-to-r from-black/40 to-transparent">
                        <!-- Top details -->
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-1.5">
                                <h4 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-1.5">
                                    Coffre {{ tier.label }}
                                    <!-- Completed Badge -->
                                    <span
                                        v-if="getTierStatus(tier.invitations) === 'completed'"
                                        class="text-[8px] font-black bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 px-1.5 py-0.5 rounded-lg uppercase tracking-wider animate-pulse shrink-0 font-mono"
                                    >
                                        Débloqué
                                    </span>
                                    <span
                                        v-else
                                        class="text-[8px] font-black bg-white/5 border border-white/10 text-slate-400 px-1.5 py-0.5 rounded-lg uppercase tracking-wider shrink-0 font-mono"
                                    >
                                        Verrouillé
                                    </span>
                                </h4>

                                <!-- Premium Status Icon -->
                                <div class="shrink-0">
                                    <CheckCircle2 v-if="getTierStatus(tier.invitations) === 'completed'" class="h-4.5 w-4.5 text-emerald-400 drop-shadow-[0_0_6px_rgba(16,185,129,0.5)]" />
                                    <Lock v-else class="h-4 w-4 text-slate-500" />
                                </div>
                            </div>

                            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1.5 mt-2">
                                <Users class="h-3.5 w-3.5 text-cyan-400 shrink-0" :stroke-width="2.5" />
                                <span>Requis : {{ tier.invitations }} invitations</span>
                            </p>
                        </div>

                        <!-- Bottom Rewards details -->
                        <div class="flex items-end justify-between mt-4 pt-3 border-t border-white/5">
                            <div>
                                <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">Prime de palier</span>
                                <div class="text-xs font-mono font-black text-emerald-400 mt-0.5">+{{ formatXAF(tier.reward) }}</div>
                            </div>

                            <!-- Invite progress helper -->
                            <div class="text-[9px] font-mono font-black text-slate-500" :class="getTierStatus(tier.invitations) === 'completed' ? 'text-emerald-400 animate-pulse' : ''">
                                {{ todayInvitations }} / {{ tier.invitations }}
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- SHARE LINK SHORTCUT -->
            <div data-animate="fade-up" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-cyan-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm text-center mx-4">
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-3">Besoin de plus d'invitations ?</p>
                <Link
                    href="/share"
                    class="inline-flex items-center gap-1.5 py-3 px-6 rounded-xl bg-cyan-500 text-black font-extrabold text-xs uppercase tracking-wider shadow-[0_0_15px_rgba(6,182,212,0.3)] hover:bg-cyan-400 transition-all duration-300 w-full justify-center"
                >
                    Partager mon lien
                    <ArrowRight class="h-4 w-4" :stroke-width="2.5" />
                </Link>
            </div>

        </div>
    </AppLayout>
</template>

<style>
@keyframes spin-slow {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spin-slow 8s linear infinite;
}
</style>
