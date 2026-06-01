<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Cpu, 
    Zap, 
    Clock, 
    CheckCircle2, 
    RefreshCw,
    Activity,
    Server,
    Shield
} from 'lucide-vue-next';
import axios from 'axios';

// Props
const props = defineProps<{
    activeUserNode?: {
        id: number;
        node_id: number;
        node_name: string;
        generation_profit: string;
        node_amount: string;
        technology_level: number;
    } | null;
    activeSession?: {
        id: number;
        start_time: string;
        end_time: string;
        expected_profit: string;
        remaining_seconds: number;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de Bord',
        href: '/dashboard',
    },
    {
        title: 'Génération en Temps Réel',
        href: '/generate',
    },
];

// User Info
const page = usePage();
const user = computed(() => page.props.auth.user);

// Generation Session States
const sessionRunning = ref(false);
const sessionClaimable = ref(false);
const currentSessionId = ref<number | null>(null);
const timeRemaining = ref(0);
const progressPercent = ref(0);
const liveProfit = ref(0.00);
const maxProfit = ref(0.00);
const showErrorModal = ref(false);
const errorMessage = ref('');

let timerInterval: number | null = null;
let animationFrameId: number | null = null;
const sessionStartTime = ref<number>(0);

const initializeLocalSession = (startTimeStr: string, endTimeStr: string, profitStr: string, id: number) => {
    maxProfit.value = parseFloat(profitStr);
    currentSessionId.value = id;
    
    const startMs = new Date(startTimeStr).getTime();
    const endMs = new Date(endTimeStr).getTime();
    const nowMs = Date.now();
    
    sessionStartTime.value = startMs;
    
    const totalDuration = endMs - startMs;
    const elapsed = nowMs - startMs;
    
    if (elapsed >= totalDuration) {
        sessionRunning.value = false;
        sessionClaimable.value = true;
        timeRemaining.value = 0;
        progressPercent.value = 100;
        liveProfit.value = maxProfit.value;
    } else {
        sessionRunning.value = true;
        sessionClaimable.value = false;
        timeRemaining.value = Math.max(0, Math.ceil((endMs - nowMs) / 1000));
        startTickers(startMs, endMs);
    }
};

const startTickers = (startMs: number, endMs: number) => {
    timerInterval = window.setInterval(() => {
        const remaining = Math.max(0, Math.ceil((endMs - Date.now()) / 1000));
        timeRemaining.value = remaining;
        
        if (remaining <= 0) {
            sessionRunning.value = false;
            sessionClaimable.value = true;
            if (timerInterval) clearInterval(timerInterval);
            if (animationFrameId) cancelAnimationFrame(animationFrameId);
            liveProfit.value = maxProfit.value;
            progressPercent.value = 100;
        }
    }, 1000);

    const tickProfit = () => {
        const now = Date.now();
        const elapsed = now - startMs;
        const total = endMs - startMs;
        
        if (elapsed >= total) {
            liveProfit.value = maxProfit.value;
            progressPercent.value = 100;
        } else {
            const ratio = Math.min(1, elapsed / total);
            liveProfit.value = maxProfit.value * ratio;
            progressPercent.value = ratio * 100;
            animationFrameId = requestAnimationFrame(tickProfit);
        }
    };
    
    animationFrameId = requestAnimationFrame(tickProfit);
};

const startGeneration = async () => {
    try {
        const response = await axios.post('/generation/start');
        const data = response.data;
        initializeLocalSession(data.start_time, data.end_time, data.expected_profit, data.id);
    } catch (error: any) {
        errorMessage.value = error.response?.data?.error || 'Erreur lors du lancement de la génération.';
        showErrorModal.value = true;
    }
};

const claimProfit = async () => {
    if (!currentSessionId.value) return;
    
    try {
        const response = await axios.post(`/generation/${currentSessionId.value}/claim`);
        if (response.data.success) {
            sessionRunning.value = false;
            sessionClaimable.value = false;
            currentSessionId.value = null;
            liveProfit.value = 0;
            progressPercent.value = 0;
            router.reload({ only: ['activeUserNode', 'activeSession'] });
        }
    } catch (error: any) {
        errorMessage.value = error.response?.data?.error || 'Erreur lors de la réclamation des gains.';
        showErrorModal.value = true;
    }
};

onMounted(() => {
    if (props.activeSession) {
        initializeLocalSession(
            props.activeSession.start_time,
            props.activeSession.end_time,
            props.activeSession.expected_profit,
            props.activeSession.id
        );
    }
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    document.body.style.overflow = '';
});

// Watch to freeze scroll on modal activation
watch(showErrorModal, (newError) => {
    if (newError) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
};
</script>

<template>
    <Head title="génération temps réel" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div>
                    <h2 class="text-xl md:text-2xl font-semibold text-white">Console AI Synchrone</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Pilotez et réclamez les gains issus de votre cœur de calcul semiconducteur.</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2 w-2 rounded-full" :class="sessionRunning ? 'bg-primary animate-ping' : 'bg-emerald-500 animate-pulse'"></span>
                    <span class="text-xs font-semibold" :class="sessionRunning ? 'text-primary' : 'text-emerald-400'">
                        {{ sessionRunning ? 'CALCUL EN COURS' : 'PRÊT POUR CO-TRAITEMENT' }}
                    </span>
                </div>
            </div>

            <!-- Case 1: No Active Node -->
            <div v-if="!activeUserNode" class="glass rounded-3xl py-16 px-6 flex flex-col items-center text-center max-w-lg mx-auto border border-white/5">
                <div class="h-16 w-16 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 animate-pulse">
                    <Cpu class="h-8 w-8" :stroke-width="2.5" />
                </div>
                <h3 class="text-lg font-bold text-white">Aucun Cœur de Calcul Actif</h3>
                <p class="text-xs text-muted-foreground mt-2 max-w-sm">
                    Vous devez d'abord louer un nœud de calcul ou un processeur d'infrastructure sur le marché pour pouvoir démarrer des sessions de co-traitement AI.
                </p>
                <Link href="/dashboard" class="mt-6 py-3 px-8 rounded-xl bg-primary text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-primary/95 transition-all">
                    Découvrir le Marché des Serveurs
                </Link>
            </div>

            <!-- Case 2: Node Active -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
                <!-- Left Details Box -->
                <div class="lg:col-span-4 glass rounded-2xl p-6 border border-white/5 flex flex-col justify-between">
                    <div>
                        <span class="text-[10px] font-bold text-primary tracking-widest uppercase px-2.5 py-1 rounded-md bg-primary/10 border border-primary/20">
                            Niveau Technologique {{ activeUserNode.technology_level }}
                        </span>
                        <h3 class="text-lg font-black text-white mt-4">{{ activeUserNode.node_name }}</h3>
                        <p class="text-xs text-muted-foreground mt-1">Cœur de traitement semi-conducteur opérationnel.</p>
                        
                        <div class="space-y-4 mt-6 pt-6 border-t border-white/5">
                            <div class="flex items-center gap-3">
                                <Server class="h-5 w-5 text-muted-foreground" :stroke-width="2.5" />
                                <div>
                                    <span class="text-[10px] text-muted-foreground block">Contrat d'infrastructure</span>
                                    <span class="text-xs font-semibold text-white">Actif & Sécurisé</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Activity class="h-5 w-5 text-emerald-400" :stroke-width="2.5" />
                                <div>
                                    <span class="text-[10px] text-muted-foreground block">Rendement de co-traitement</span>
                                    <span class="text-xs font-semibold text-emerald-400">{{ formatXAF(activeUserNode.generation_profit) }} / jour</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Shield class="h-5 w-5 text-secondary" :stroke-width="2.5" />
                                <div>
                                    <span class="text-[10px] text-muted-foreground block">Clef Cryptographique</span>
                                    <span class="text-xs font-semibold text-white font-mono">ARM-SEC-{{ activeUserNode.id }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="p-3.5 rounded-xl bg-white/[0.02] border border-white/5 text-[10px] text-muted-foreground mt-6 leading-relaxed">
                        Chaque session dure 2 minutes en direct. Ne fermez pas l'application pour apprécier le flux de gains en temps réel.
                    </div>
                </div>

                <!-- Right Live Console Panel -->
                <div class="lg:col-span-8 glass rounded-2xl p-6 border border-white/5 relative overflow-hidden flex flex-col justify-center min-h-[350px]">
                    <div class="absolute inset-0 bg-grid opacity-[0.03] pointer-events-none"></div>

                    <!-- Visual Tech Circles Animation if running -->
                    <div v-if="sessionRunning" class="absolute right-6 top-6 flex items-center gap-1.5 bg-black/40 border border-white/5 px-3 py-1.5 rounded-lg z-10">
                        <Activity class="h-4.5 w-4.5 text-primary animate-pulse" :stroke-width="2.5" />
                        <span class="text-[10px] font-mono text-primary font-bold">Hz Sync Level: 98.2%</span>
                    </div>

                    <!-- Not Started State -->
                    <div v-if="!sessionRunning && !sessionClaimable" class="text-center py-10 max-w-md mx-auto">
                        <div class="h-16 w-16 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center text-primary mb-6 mx-auto shadow-[0_0_20px_rgba(168,85,247,0.1)]">
                            <Zap class="h-8 w-8" :stroke-width="2.5" />
                        </div>
                        <h3 class="text-lg font-bold text-white">Génération Prête</h3>
                        <p class="text-xs text-muted-foreground mt-2">
                            Cliquez sur le bouton ci-dessous pour initier la synchronisation de 2 minutes avec le GPU principal et générer vos dividendes AI.
                        </p>
                        <button 
                            @click="startGeneration"
                            class="mt-6 py-3.5 px-8 rounded-xl bg-primary text-black font-extrabold text-xs uppercase shadow-[0_0_20px_rgba(168,85,247,0.4)] hover:bg-primary/95 transition-all duration-300 flex items-center justify-center gap-2 mx-auto"
                        >
                            <Zap class="h-4 w-4 fill-black" :stroke-width="2.5" />
                            Démarrer la Synchronisation (2 Min)
                        </button>
                    </div>

                    <!-- Running State -->
                    <div v-if="sessionRunning" class="w-full max-w-xl mx-auto space-y-6">
                        <div class="flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 rounded-full bg-primary animate-ping"></span>
                                <span class="text-xs font-bold text-primary uppercase tracking-widest font-mono">Synchronisation GPU encours...</span>
                            </div>
                            <span class="text-xs font-extrabold text-white font-mono flex items-center gap-1">
                                <Clock class="h-4 w-4 text-primary animate-spin" style="animation-duration: 4s;" :stroke-width="2.5" />
                                {{ Math.floor(timeRemaining / 60) }}:{{ String(timeRemaining % 60).padStart(2, '0') }}
                            </span>
                        </div>

                        <!-- Heavy Progress Bar -->
                        <div class="h-4 w-full bg-white/5 rounded-full overflow-hidden border border-white/10 relative p-0.5 shadow-inner">
                            <div 
                                class="h-full bg-gradient-to-r from-secondary via-primary to-accent rounded-full transition-all duration-100 ease-out shadow-[0_0_15px_rgba(168,85,247,0.4)]"
                                :style="{ width: progressPercent + '%' }"
                            ></div>
                        </div>

                        <!-- Real-time Counters Grid -->
                        <div class="grid grid-cols-2 gap-6 pt-4 border-t border-white/5">
                            <div>
                                <span class="text-[10px] text-muted-foreground uppercase font-semibold">Taux de Progression</span>
                                <h4 class="text-2xl font-black text-white font-mono mt-1">{{ Math.round(progressPercent) }}%</h4>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-primary uppercase font-bold tracking-widest block">Gains Récoltés en Direct</span>
                                <h4 class="text-2xl font-black text-primary font-mono mt-1 text-glow">
                                    +{{ formatXAF(liveProfit.toFixed(2)) }}
                                </h4>
                            </div>
                        </div>
                    </div>

                    <!-- Completed & Claimable State -->
                    <div v-if="sessionClaimable" class="text-center py-10 max-w-md mx-auto">
                        <div class="h-16 w-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6 mx-auto animate-bounce shadow-[0_0_20px_rgba(16,185,129,0.1)]">
                            <CheckCircle2 class="h-8 w-8" :stroke-width="2.5" />
                        </div>
                        <h3 class="text-lg font-bold text-white">Synchronisation Validée</h3>
                        <p class="text-xs text-muted-foreground mt-2">
                            La session s'est terminée avec succès. Vos récompenses cryptographiques de co-traitement sont prêtes à être réclamées.
                        </p>
                        <button 
                            @click="claimProfit"
                            class="mt-6 py-3.5 px-8 rounded-xl bg-emerald-500 text-black font-extrabold text-xs uppercase shadow-[0_0_20px_rgba(16,185,129,0.4)] hover:bg-emerald-400 transition-all duration-300 flex items-center justify-center gap-2 mx-auto animate-pulse"
                        >
                            <Zap class="h-4 w-4 fill-black" :stroke-width="2.5" />
                            Réclamer {{ formatXAF(maxProfit) }}
                        </button>
                    </div>

                </div>
            </div>

            <!-- Error Modal -->
            <Teleport to="body">
                <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden">
                    <div class="glass max-w-xl w-full rounded-2xl border border-rose-500/30 p-6 relative text-center">
                        <span class="text-rose-400 font-extrabold uppercase text-[10px] tracking-widest block mb-1">Alerte Système</span>
                        <p class="text-white text-xs leading-relaxed font-mono">
                            {{ errorMessage }}
                        </p>
                        <button @click="showErrorModal = false" class="w-full mt-6 py-3 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold uppercase tracking-wider text-xs hover:bg-rose-500 hover:text-black transition-all">
                            Fermer
                        </button>
                    </div>
                </div>
            </Teleport>

        </div>
    </AppLayout>
</template>

<style scoped>
.bg-grid {
    background-size: 40px 40px;
    background-image: 
        linear-gradient(to right, rgba(168, 85, 247, 0.03) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.03) 1px, transparent 1px);
}
</style>
