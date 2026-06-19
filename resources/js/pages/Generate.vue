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
    Shield,
    Lock
} from 'lucide-vue-next';
import axios from 'axios';

// Props
const props = defineProps<{
    activeNodes?: Array<{
        id: number;
        node_id: number;
        node_name: string;
        generation_profit: number;
        node_amount: number;
        technology_level: number;
        activated_at: string;
        expires_at: string;
        status: 'ready' | 'running' | 'claimable' | 'cooldown';
        session?: {
            id: number;
            start_time: string;
            end_time: string;
            expected_profit: number;
            remaining_seconds: number;
        } | null;
        cooldown_seconds: number;
        cooldown_expires_at: string | null;
    }>;
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

// Reactive list of nodes to tick independent timers locally
const nodesList = ref<any[]>([]);
const showErrorModal = ref(false);
const errorMessage = ref('');

// Claim countdown state
const claimingSessionId = ref<number | null>(null);
const claimSecondsLeft = ref(8);
let claimTimer: ReturnType<typeof setInterval> | null = null;

// Global frame animation id for 60fps tickers
let animationFrameId: number | null = null;

// Synchronize nodesList when props update (Inertia reloads)
watch(() => props.activeNodes, (newNodes) => {
    nodesList.value = JSON.parse(JSON.stringify(newNodes || []));
}, { deep: true, immediate: true });

// Run the tick loop at 60 FPS to update all progress percentages and timers
const tick = () => {
    const now = Date.now();
    
    nodesList.value.forEach(node => {
        if (node.status === 'running' && node.session) {
            const startMs = new Date(node.session.start_time).getTime();
            const endMs = new Date(node.session.end_time).getTime();
            const total = endMs - startMs;
            const elapsed = now - startMs;
            
            if (elapsed >= total) {
                node.status = 'claimable';
                node.progressPercent = 100;
                node.liveProfit = node.generation_profit;
                node.session.remaining_seconds = 0;
            } else {
                const ratio = Math.min(1, elapsed / total);
                node.progressPercent = ratio * 100;
                node.liveProfit = node.generation_profit * ratio;
                node.session.remaining_seconds = Math.max(0, Math.ceil((endMs - now) / 1000));
            }
        } else if (node.status === 'cooldown' && node.cooldown_expires_at) {
            const expiresMs = new Date(node.cooldown_expires_at).getTime();
            const remaining = Math.max(0, Math.ceil((expiresMs - now) / 1000));
            node.cooldown_seconds = remaining;
            
            if (remaining <= 0) {
                node.status = 'ready';
                node.cooldown_seconds = 0;
            }
        }
    });
    
    animationFrameId = requestAnimationFrame(tick);
};

// Start generation on a specific rented node
const startGeneration = async (userNodeId: number) => {
    const targetNode = nodesList.value.find(n => n.id === userNodeId);
    if (!targetNode) return;
    
    try {
        const response = await axios.post('/generation/start', {
            user_node_id: userNodeId
        });
        const data = response.data;
        
        targetNode.status = 'running';
        targetNode.session = {
            id: data.id,
            start_time: data.start_time,
            end_time: data.end_time,
            expected_profit: parseFloat(data.expected_profit),
            remaining_seconds: 120,
        };
        targetNode.progressPercent = 0;
        targetNode.liveProfit = 0;
        
        router.reload({ only: ['activeNodes'] });
    } catch (error: any) {
        errorMessage.value = error.response?.data?.error || 'Erreur lors du lancement de la génération.';
        showErrorModal.value = true;
    }
};

// Claim profit from a completed session (with 8-second animated countdown)
const claimProfit = (sessionId: number, userNodeId: number) => {
    if (claimingSessionId.value !== null) return; // prevent double-trigger

    claimingSessionId.value = sessionId;
    claimSecondsLeft.value = 8;

    claimTimer = setInterval(() => {
        claimSecondsLeft.value--;
        if (claimSecondsLeft.value <= 0) {
            clearInterval(claimTimer!);
            claimTimer = null;
            // Actually post the claim after countdown
            axios.post(`/generation/${sessionId}/claim`)
                .then(response => {
                    if (response.data.success) {
                        const targetNode = nodesList.value.find(n => n.id === userNodeId);
                        if (targetNode) {
                            targetNode.status = 'cooldown';
                            targetNode.session = null;
                            const future = new Date(Date.now() + 24 * 60 * 60 * 1000);
                            targetNode.cooldown_expires_at = future.toISOString();
                            targetNode.cooldown_seconds = 24 * 60 * 60;
                        }
                        router.reload({ only: ['activeNodes', 'auth'] });
                    }
                })
                .catch((error: any) => {
                    errorMessage.value = error.response?.data?.error || 'Erreur lors de la réclamation des gains.';
                    showErrorModal.value = true;
                })
                .finally(() => {
                    claimingSessionId.value = null;
                    claimSecondsLeft.value = 8;
                });
        }
    }, 1000);
};

// Returns the number of remaining weekdays (Mon-Fri) until expiresAt
const getWeekdaysRemaining = (expiresAt: string): string => {
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

// UI Formatter
const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
};

// Formats seconds into HH:MM:SS
const formatCooldown = (seconds: number) => {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = seconds % 60;
    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
};

onMounted(() => {
    animationFrameId = requestAnimationFrame(tick);
});

onUnmounted(() => {
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (claimTimer) clearInterval(claimTimer);
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
</script>

<template>
    <Head title="Console de Co-traitement" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div>
                    <h2 class="text-xl md:text-2xl font-semibold text-white">Console AI Synchrone</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Pilotez vos serveurs actifs et réclamez vos dividendes de calcul (Disponible du lundi au vendredi).</p>
                </div>
                <div class="flex items-center gap-4 bg-white/[0.02] border border-white/5 px-4 py-2 rounded-xl text-xs font-semibold">
                    <span class="text-muted-foreground font-mono">SOLDE DU COMPTE:</span>
                    <span class="text-cyan-400 font-bold font-mono">{{ formatXAF(user?.balance || 0) }}</span>
                </div>
            </div>

            <!-- Case 1: No Active Nodes -->
            <div v-if="!nodesList || nodesList.length === 0" class="glass rounded-3xl py-16 px-6 flex flex-col items-center text-center max-w-lg mx-auto border border-white/5 mt-10">
                <div class="h-16 w-16 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 animate-pulse">
                    <Cpu class="h-8 w-8" :stroke-width="2.5" />
                </div>
                <h3 class="text-lg font-bold text-white">Aucun Serveur Actif Détecté</h3>
                <p class="text-xs text-muted-foreground mt-2 max-w-sm">
                    Vous devez d'abord louer un nœud de calcul ou un processeur d'infrastructure pour pouvoir démarrer des sessions de co-traitement AI.
                </p>
                <Link href="/dashboard" class="mt-6 py-3 px-8 rounded-xl bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all">
                    Louer des Serveurs sur le Marché
                </Link>
            </div>

            <!-- Case 2: Grid of Active Servers -->
            <div v-else class="space-y-4">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-cyan-400 font-mono tracking-widest uppercase">
                        VOS PARCS DE SERVEURS DÉPLOYÉS ({{ nodesList.length }})
                    </span>
                    <div class="flex items-center gap-1.5 text-[10px] text-muted-foreground">
                        <Activity class="h-3.5 w-3.5 text-emerald-400 animate-pulse" />
                        <span>TOUS LES CAPTEURS SONT OPÉRATIONNELS</span>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div 
                        v-for="node in nodesList" 
                        :key="node.id"
                        class="glass rounded-2xl p-5 border border-white/5 relative overflow-hidden flex flex-col justify-between min-h-[340px] transition-all duration-300 hover:border-cyan-500/30 hover:shadow-[0_0_20px_rgba(6,182,212,0.15)] group"
                    >
                        <!-- Top visual background grid scan lines -->
                        <div class="absolute inset-0 bg-grid opacity-[0.02] pointer-events-none"></div>
                        <div v-if="node.status === 'running'" class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-cyan-400 to-transparent shadow-[0_0_8px_rgba(6,182,212,0.8)] animate-scan pointer-events-none"></div>

                        <!-- Card Header -->
                        <div>
                            <div class="flex justify-between items-start gap-2">
                                <span class="text-[9px] font-black text-cyan-400 tracking-widest uppercase px-2 py-0.5 rounded bg-cyan-500/10 border border-cyan-500/20 font-mono">
                                    TECH LEVEL {{ node.technology_level }}
                                </span>
                                <div class="flex items-center gap-1.5">
                                    <span class="h-2 w-2 rounded-full" :class="{
                                        'bg-cyan-500 animate-ping': node.status === 'running',
                                        'bg-emerald-400 animate-pulse': node.status === 'ready',
                                        'bg-amber-500 animate-bounce': node.status === 'claimable',
                                        'bg-purple-600': node.status === 'cooldown'
                                    }"></span>
                                    <span class="text-[9px] font-bold font-mono tracking-wider" :class="{
                                        'text-cyan-400': node.status === 'running',
                                        'text-emerald-400': node.status === 'ready',
                                        'text-amber-400': node.status === 'claimable',
                                        'text-purple-400': node.status === 'cooldown'
                                    }">
                                        {{ node.status.toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            
                            <h3 class="text-base font-black text-white mt-3 group-hover:text-cyan-400 transition-colors">
                                {{ node.node_name }}
                            </h3>
                            <p class="text-[10px] text-muted-foreground mt-0.5">Clé cryptographique unique : <span class="font-mono text-gray-300">ARM-SEC-{{ node.id }}</span></p>

                            <!-- Node Technical Specifications -->
                            <div class="mt-4 space-y-2 pt-4 border-t border-white/5">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-muted-foreground font-semibold flex items-center gap-1">
                                        <Activity class="h-3.5 w-3.5" /> Rendement Journalier
                                    </span>
                                    <span class="font-black text-emerald-400 font-mono">{{ formatXAF(node.generation_profit) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-muted-foreground font-semibold flex items-center gap-1">
                                        <Server class="h-3.5 w-3.5" /> Valeur de location
                                    </span>
                                    <span class="font-bold text-gray-300 font-mono">{{ formatXAF(node.node_amount) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-muted-foreground font-semibold flex items-center gap-1">
                                        <Shield class="h-3.5 w-3.5" /> Jours Restants
                                    </span>
                                    <span class="font-medium text-rose-400 font-mono text-[10px] uppercase">
                                        {{ getWeekdaysRemaining(node.expires_at) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Status Section in the center -->
                        <div class="my-5 py-3 rounded-xl bg-white/[0.01] border border-white/5 px-4 min-h-[90px] flex flex-col justify-center">
                            
                            <!-- State: Ready to Generate -->
                            <div v-if="node.status === 'ready'" class="text-center">
                                <p class="text-[10px] text-muted-foreground font-semibold leading-relaxed">
                                    Serveur prêt pour la prochaine génération. Cliquez ci-dessous pour lancer la session de co-traitement AI.
                                </p>
                            </div>

                            <!-- State: Processing (Running) -->
                            <div v-if="node.status === 'running'" class="space-y-2">
                                <div class="flex justify-between items-center text-[10px] font-mono">
                                    <span class="text-cyan-400 font-bold animate-pulse">Sync GPU en cours...</span>
                                    <span class="text-white font-bold flex items-center gap-1">
                                        <Clock class="h-3 w-3 text-cyan-400 animate-spin" />
                                        {{ formatCooldown(node.session?.remaining_seconds || 0) }}
                                    </span>
                                </div>
                                <div class="h-2 w-full bg-white/5 rounded-full overflow-hidden border border-white/10 relative p-0.5 shadow-inner">
                                    <div 
                                        class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full transition-all duration-100 ease-out shadow-[0_0_10px_rgba(6,182,212,0.4)]"
                                        :style="{ width: (node.progressPercent || 0) + '%' }"
                                    ></div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] font-mono mt-1">
                                    <span class="text-muted-foreground">Progression : {{ Math.round(node.progressPercent || 0) }}%</span>
                                    <span class="text-cyan-400 font-bold">+{{ formatXAF((node.liveProfit || 0).toFixed(2)) }}</span>
                                </div>
                            </div>

                            <!-- State: Claimable -->
                            <div v-if="node.status === 'claimable'" class="text-center space-y-1 py-1">
                                <div class="flex items-center justify-center gap-1.5 text-amber-400">
                                    <CheckCircle2 class="h-4 w-4 animate-bounce" />
                                    <span class="text-[10px] font-black uppercase tracking-wider font-mono">Calcul Complété !</span>
                                </div>
                                <p class="text-[10px] text-muted-foreground">
                                    Les gains générés sont prêts à être injectés dans votre solde.
                                </p>
                            </div>

                            <!-- State: Cooldown -->
                            <div v-if="node.status === 'cooldown'" class="space-y-1.5 text-center">
                                <div class="flex items-center justify-center gap-1.5 text-purple-400">
                                    <Lock class="h-3.5 w-3.5" />
                                    <span class="text-[9px] font-black uppercase tracking-widest font-mono">Refroidissement Actif</span>
                                </div>
                                <div class="text-xs font-black font-mono text-white tracking-widest bg-black/40 border border-white/5 py-1.5 rounded-lg">
                                    {{ formatCooldown(node.cooldown_seconds) }}
                                </div>
                                <p class="text-[8px] text-slate-500 leading-tight">
                                    Prochaine session disponible 24h après le dernier calcul.
                                </p>
                            </div>

                        </div>

                        <!-- Card Action Button -->
                        <div>
                            <!-- Action: Start Generation -->
                            <button 
                                v-if="node.status === 'ready'"
                                @click="startGeneration(node.id)"
                                class="w-full py-3.5 rounded-xl bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-black font-extrabold text-xs uppercase tracking-wider shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all flex items-center justify-center gap-2"
                            >
                                <Zap class="h-4 w-4 fill-black" :stroke-width="2.5" />
                                Démarrer (2 Min)
                            </button>

                            <!-- Action: Running -->
                            <button 
                                v-if="node.status === 'running'"
                                disabled
                                class="w-full py-3.5 rounded-xl bg-cyan-950/20 text-cyan-400/50 border border-cyan-500/10 font-extrabold text-xs uppercase tracking-wider cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <RefreshCw class="h-4 w-4 animate-spin" />
                                Calcul en Cours
                            </button>

                            <!-- Action: Claim Profit (idle) -->
                            <button 
                                v-if="node.status === 'claimable' && node.session && claimingSessionId !== node.session.id"
                                @click="claimProfit(node.session.id, node.id)"
                                class="w-full py-3.5 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-500 hover:from-amber-400 hover:to-yellow-400 text-black font-black text-xs uppercase tracking-widest shadow-[0_0_15px_rgba(245,158,11,0.4)] animate-pulse transition-all flex items-center justify-center gap-2"
                            >
                                <Zap class="h-4 w-4 fill-black" :stroke-width="2.5" />
                                Réclamer {{ formatXAF(node.generation_profit) }}
                            </button>

                            <!-- Action: Claiming countdown (8 seconds) -->
                            <div
                                v-if="node.status === 'claimable' && node.session && claimingSessionId === node.session.id"
                                class="w-full rounded-xl overflow-hidden border border-amber-500/30 bg-amber-950/20"
                            >
                                <div class="flex items-center justify-between px-4 py-2 text-xs">
                                    <span class="text-amber-400 font-black font-mono tracking-wider animate-pulse">Collecte en cours...</span>
                                    <span class="text-white font-black font-mono text-sm">{{ claimSecondsLeft }}s</span>
                                </div>
                                <div class="h-1.5 w-full bg-amber-950/40">
                                    <div
                                        class="h-full bg-gradient-to-r from-amber-500 to-yellow-400 shadow-[0_0_8px_rgba(245,158,11,0.6)] transition-all duration-1000 ease-linear"
                                        :style="{ width: ((8 - claimSecondsLeft) / 8 * 100) + '%' }"
                                    ></div>
                                </div>
                            </div>

                            <!-- Action: Cooldown Locked -->
                            <button 
                                v-if="node.status === 'cooldown'"
                                disabled
                                class="w-full py-3.5 rounded-xl bg-purple-950/10 text-purple-400/40 border border-purple-500/10 font-bold text-xs uppercase tracking-wider cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                <Lock class="h-3.5 w-3.5" />
                                Bloqué (Cooldown)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Error Modal -->
            <Teleport to="body">
                <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden">
                    <div class="glass max-w-xl w-full rounded-2xl border border-rose-500/30 p-6 relative text-center shadow-[0_0_30px_rgba(244,63,94,0.2)]">
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
    background-size: 30px 30px;
    background-image: 
        linear-gradient(to right, rgba(6, 182, 212, 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(6, 182, 212, 0.04) 1px, transparent 1px);
}
@keyframes scan { 
    0% { top: -10%; opacity: 0; } 
    10% { opacity: 1; } 
    90% { opacity: 1; } 
    100% { top: 110%; opacity: 0; } 
}
.animate-scan { 
    animation: scan 3s linear infinite; 
}
</style>
