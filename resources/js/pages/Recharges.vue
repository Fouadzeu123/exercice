<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    ArrowLeft,
    History,
    Globe,
    CheckCircle,
    Clock,
    XCircle
} from 'lucide-vue-next';
import { t, currentLocale, toggleLocale } from '@/utils/trans';

const props = defineProps<{
    deposits: Array<{
        id: number;
        amount: number;
        status: string;
        reference: string;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('Tableau de Bord', 'Dashboard'), href: '/dashboard' },
    { title: t('Historique des Dépôts', 'Deposits History'), href: '/recharges' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
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

const getStatusColor = (status: string) => {
    switch(status) {
        case 'completed': return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20 shadow-[0_0_10px_rgba(16,185,129,0.15)]';
        case 'pending': return 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20 shadow-[0_0_10px_rgba(234,179,8,0.15)]';
        case 'rejected': return 'text-rose-400 bg-rose-500/10 border-rose-500/20 shadow-[0_0_10px_rgba(244,63,94,0.15)]';
        default: return 'text-slate-400 bg-slate-500/10 border-slate-500/20';
    }
};

const getStatusLabel = (status: string) => {
    switch(status) {
        case 'completed': return t('Validé', 'Approved');
        case 'pending': return t('En attente', 'Pending');
        case 'rejected': return t('Rejeté', 'Rejected');
        default: return status;
    }
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head :title="t('Historique des dépôts', 'Deposits History')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-4 pt-3 pb-24 text-white">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-[#0c0f1d] p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <Link
                    href="/dashboard"
                    class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black transition-colors"
                >
                    <ArrowLeft class="h-4.5 w-4.5" :stroke-width="2.5" />
                </Link>

                <h2 class="text-base font-black text-white uppercase tracking-wider flex items-center gap-2">
                    <History class="h-4.5 w-4.5 text-purple-400" />
                    {{ t('Mes Dépôts', 'My Deposits') }}
                </h2>

                <div
                    @click="toggleLocale"
                    class="border border-white/10 bg-white/5 px-2.5 py-1 rounded-xl text-[9px] font-bold tracking-wide flex items-center gap-1.5 hover:border-purple-400/50 transition-colors cursor-pointer uppercase select-none"
                >
                    <Globe class="h-3.5 w-3.5 text-purple-400" :stroke-width="2.5" />
                    <span>{{ currentLocale }}</span>
                </div>
            </div>

            <!-- DEPOSITS TIMELINE / LIST -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0c0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-md">
                <div class="flex items-center justify-between mb-5 border-b border-purple-500/10 pb-3">
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">
                        {{ t('Historique des transactions', 'Transaction History') }}
                    </span>
                    <span class="text-[12px] font-mono text-purple-400 uppercase font-black bg-purple-500/10 border border-purple-500/20 px-2 py-0.5 rounded">
                        {{ deposits.length }} Dépôt(s)
                    </span>
                </div>

                <div v-if="!deposits || deposits.length === 0" class="py-12 text-center flex flex-col items-center gap-3">
                    <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center text-slate-500 border border-white/5 animate-pulse">
                        <History class="w-5 h-5" />
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                        {{ t('Aucun dépôt enregistré', 'No deposits registered') }}
                    </span>
                    <Link
                        href="/recharger"
                        class="mt-2 px-4 py-2 bg-purple-600 hover:bg-purple-500 text-black text-[9px] font-black uppercase tracking-wider rounded-xl transition-all shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                    >
                        {{ t('Faire un dépôt', 'Make a Deposit') }}
                    </Link>
                </div>

                <div v-else class="space-y-3.5 max-h-[500px] overflow-y-auto pr-1">
                    <div
                        v-for="dep in deposits" :key="dep.id"
                        class="bg-[#121625]/40 border border-white/5 rounded-2xl p-4 flex items-center justify-between hover:border-purple-500/20 transition-all duration-300 group relative overflow-hidden"
                    >
                        <!-- Left design subtle line -->
                        <div class="absolute left-0 top-0 bottom-0 w-[2px]" :class="dep.status === 'completed' ? 'bg-emerald-500' : (dep.status === 'pending' ? 'bg-yellow-500' : 'bg-rose-500')"></div>

                        <div class="flex items-center gap-3 pl-1.5">
                            <div class="w-9 h-9 rounded-xl bg-black/40 border border-white/5 flex items-center justify-center" :class="dep.status === 'completed' ? 'text-emerald-400' : (dep.status === 'pending' ? 'text-yellow-400' : 'text-rose-400')">
                                <CheckCircle v-if="dep.status === 'completed'" class="w-4.5 h-4.5" />
                                <Clock v-else-if="dep.status === 'pending'" class="w-4.5 h-4.5" />
                                <XCircle v-else class="w-4.5 h-4.5" />
                            </div>

                            <div>
                                <div class="text-[12.5px] font-black font-mono text-white leading-none">
                                    +{{ formatXAF(dep.amount) }}
                                </div>
                                <span class="text-[9px] text-slate-500 font-mono mt-1 block">
                                    {{ formatDate(dep.created_at) }}
                                </span>
                            </div>
                        </div>

                        <div class="text-right flex flex-col items-end gap-1.5">
                            <span
                                class="text-[8px] font-black px-2.5 py-0.5 rounded-full border uppercase tracking-wider font-mono"
                                :class="getStatusColor(dep.status)"
                            >
                                {{ getStatusLabel(dep.status) }}
                            </span>
                            <span class="text-[8px] font-mono text-slate-600 block tracking-tight uppercase leading-none">
                                Ref: {{ dep.reference }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FOOTER INFO BOX -->
            <div data-animate="fade-up" data-delay="120" class="p-4 rounded-2xl bg-purple-950/10 border border-purple-500/10 text-[15px] text-slate-400 leading-relaxed font-mono">
                <span class="font-bold text-white uppercase tracking-wider block mb-1">
                    {{ t('Information sur les Dépôts', 'Deposits Information') }}
                </span>
                <p>
                    Les dépôts sont traités instantanément par nos passerelles de co-calcul. Si votre solde n'a pas été crédité dans un délai de 30 minutes, veuillez contacter le service technique ARM Holding via le Support AI.
                </p>
            </div>

        </div>
    </AppLayout>
</template>
