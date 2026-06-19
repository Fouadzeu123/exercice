<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Trophy,
    Coins,
    Award,
    Zap,
    Clock,
    Play,
    AlertCircle,
    Volume2,
    X,
    RotateCw,
    Activity,
    ArrowLeft
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    userBalance: number;
    drawSpins: number;
    myWinnings: Array<{
        id: number;
        amount: number;
        reference: string;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Tirage Cosmique', href: '/tirage' },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const balance = ref(props.userBalance);
const spinsLeft = ref(props.drawSpins);
const isSpinning = ref(false);
const wheelRotation = ref(0);
const spinCount = ref(0);
const showSuccessModal = ref(false);
const showLimitErrorModal = ref(false);
const limitError = ref('');
const wonPrize = ref(0);
const avatarError = ref(false);

const activeTab = ref<'winners' | 'myWinnings'>('winners');
const localMyWinnings = ref([...props.myWinnings]);

const prizes = [777, 1777, 7777, 77777, 177777, 777777, 1777777];

// Premium charcoal obsidian palette
const colors = [
    '#0e071e', // Darkest space
    '#170c2f', // Sleek dark indigo
    '#0a0416', // Pitch obsidian
    '#200e3d', // Neural charcoal
    '#06020f', // Pure carbon
    '#1a0c33', // Deep violet
    '#230f45'  // Cyber purple
];

const mockWinners = ref([
    { phone: '****04405', amount: 777 },
    { phone: '****19556', amount: 1777 },
    { phone: '****02294', amount: 777 },
    { phone: '****67779', amount: 1777 },
    { phone: '****38829', amount: 77777 },
    { phone: '****54120', amount: 777 },
    { phone: '****99302', amount: 177777 },
    { phone: '****11244', amount: 777 }
]);

const polarToCartesian = (centerX: number, centerY: number, radius: number, angleInDegrees: number) => {
  const angleInRadians = (angleInDegrees - 90) * Math.PI / 180.0;
  return {
    x: centerX + (radius * Math.cos(angleInRadians)),
    y: centerY + (radius * Math.sin(angleInRadians))
  };
};

const drawSectorPath = (x: number, y: number, radius: number, startAngle: number, endAngle: number) => {
  const start = polarToCartesian(x, y, radius, endAngle);
  const end = polarToCartesian(x, y, radius, startAngle);
  const largeArcFlag = endAngle - startAngle <= 180 ? "0" : "1";
  return [
    "M", x, y,
    "L", start.x, start.y,
    "A", radius, radius, 0, largeArcFlag, 0, end.x, end.y,
    "Z"
  ].join(" ");
};

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const startSpin = async () => {
    if (isSpinning.value) return;
    
    if (spinsLeft.value < 1) {
        limitError.value = "Vous n'avez plus de lancers disponibles. Les lancers sont attribués par le service RH.";
        showLimitErrorModal.value = true;
        return;
    }
    
    isSpinning.value = true;
    try {
        const res = await axios.post('/tirage/spin');
        if (res.data.success) {
            const winnerIndex = res.data.winner_index;
            const segmentAngle = 360 / 7;
            
            spinCount.value++;
            const targetRotation = (360 * 8 * spinCount.value) - (winnerIndex * segmentAngle + segmentAngle / 2);
            
            wheelRotation.value = targetRotation;
            
            setTimeout(() => {
                wonPrize.value = res.data.won_amount;
                balance.value = res.data.new_balance;
                spinsLeft.value = res.data.draw_spins;
                
                // Add new winning to top of local list
                localMyWinnings.value.unshift({
                    id: Date.now(),
                    amount: res.data.won_amount,
                    reference: 'DRAW-' + Math.random().toString(36).substr(2, 9).toUpperCase(),
                    created_at: new Date().toISOString()
                });
                
                showSuccessModal.value = true;
                isSpinning.value = false;
                router.reload({ only: ['auth'] });
            }, 5200);
        }
    } catch (e: any) {
        isSpinning.value = false;
        limitError.value = e.response?.data?.error || "Erreur système lors du lancement du tirage.";
        showLimitErrorModal.value = true;
    }
};

const { containerRef } = useRevealAnimation();

// Body scroll lock mechanism when modals open
watch([showSuccessModal, showLimitErrorModal], ([newSuccess, newLimit]) => {
    if (newSuccess || newLimit) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
});
</script>

<template>
    <Head title="Roue de la fortune" />
    <AppLayout :breadcrumbs="breadcrumbs" class="bg-gradient-to-b from-[#05020c] to-[#0e061b]">
        <!-- Ambient space glow effects -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,rgba(168,85,247,0.06),transparent_75%)]"></div>
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-purple-500/5 rounded-full blur-[120px]"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-fuchsia-500/5 rounded-full blur-[120px]"></div>
        </div>

        <div ref="containerRef" class="relative z-10 w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24 px-4">

            <!-- HIGH TECH APP HEADER (BACK BUTTON & TITLE & AVATAR) -->
            <div data-animate="fade-down" class="flex items-center justify-between py-2 border-b border-white/5">
                <Link 
                    href="/dashboard" 
                    class="w-10 h-10 rounded-full bg-black/40 border border-white/10 flex items-center justify-center text-white hover:bg-black transition-all hover:scale-105 shadow-md shadow-purple-950/20"
                >
                    <ArrowLeft class="h-5 w-5" :stroke-width="2.5" />
                </Link>
                
                <h2 class="text-base font-bold text-white uppercase tracking-wider font-mono">
                    Roue de la fortune
                </h2>
                
                <div class="w-10 h-10 rounded-full border border-purple-500/40 p-0.5 bg-black/40 shadow-[0_0_12px_rgba(168,85,247,0.3)] shrink-0 overflow-hidden">
                    <img 
                        v-if="user?.profile_photo_url && !avatarError"
                        :src="user.profile_photo_url" 
                        @error="avatarError = true"
                        class="w-full h-full rounded-full object-cover" 
                        alt="Profile Avatar"
                    />
                    <div v-else class="w-full h-full rounded-full bg-gradient-to-tr from-purple-600 to-fuchsia-600 flex items-center justify-center text-white text-xs font-black font-mono">
                        {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                </div>
            </div>

            <!-- HIGH-TECH WHEEL MACHINE BOX -->
            <div data-animate="scale-up" data-delay="100" class="relative py-4 flex flex-col items-center justify-center">
                <!-- Glossy orange triangle Pointer at 12 o'clock -->
                <div class="absolute top-[4px] z-30 flex flex-col items-center">
                    <!-- Laser energy pin -->
                    <div class="w-1.5 h-6 bg-amber-500 shadow-[0_0_12px_rgba(245,158,11,1)] rounded-full"></div>
                    <!-- Solid glossy arrow -->
                    <div class="w-0 h-0 border-l-[10px] border-l-transparent border-r-[10px] border-r-transparent border-t-[14px] border-t-amber-500 -mt-1 drop-shadow-[0_2px_8px_rgba(245,158,11,0.5)]"></div>
                </div>

                <!-- Centered container hosting BOTH the wheel and concentric background rings -->
                <div class="relative w-68 h-68 flex items-center justify-center shrink-0 mt-6 select-none">
                    <!-- Concentric Integrated circuit grids centered mathematically with the wheel center -->
                    <div class="absolute w-84 h-84 rounded-full border border-purple-500/10 blur-[2px] pointer-events-none"></div>
                    <div class="absolute w-78 h-78 rounded-full border-2 border-purple-500/20 pointer-events-none animate-pulse"></div>
                    <div class="absolute w-74 h-74 rounded-full border border-dashed border-fuchsia-500/35 pointer-events-none animate-spin" style="animation-duration: 40s"></div>

                    <!-- Circular Tech Wheel Structure -->
                    <div class="relative w-full h-full rounded-full border-[8px] border-[#140b28] shadow-[0_0_35px_rgba(168,85,247,0.35)] bg-[#05020c] overflow-hidden select-none">
                    <svg 
                        viewBox="0 0 300 300" 
                        class="w-full h-full transform transition-transform" 
                        :style="{ transform: `rotate(${wheelRotation}deg)`, transitionDuration: isSpinning ? '5200ms' : '0ms', transitionTimingFunction: 'cubic-bezier(0.1, 0.8, 0.1, 1)' }"
                    >
                        <defs>
                            <filter id="segment-glow" x="-20%" y="-20%" width="140%" height="140%">
                                <feGaussianBlur stdDeviation="2.5" result="blur" />
                                <feMerge>
                                    <feMergeNode in="blur" />
                                    <feMergeNode in="SourceGraphic" />
                                </feMerge>
                            </filter>
                            <pattern id="circuits" width="10" height="10" patternUnits="userSpaceOnUse">
                                <circle cx="3" cy="3" r="0.75" fill="rgba(168, 85, 247, 0.12)" />
                            </pattern>
                        </defs>

                        <!-- Draw Segment Sectors -->
                        <g v-for="(prize, i) in prizes" :key="i">
                            <path 
                                :d="drawSectorPath(150, 150, 140, i * (360/7), (i + 1) * (360/7))" 
                                :fill="colors[i]"
                                stroke="#be46ff"
                                stroke-width="0.75"
                                stroke-opacity="0.25"
                            />
                            <path 
                                :d="drawSectorPath(150, 150, 140, i * (360/7), (i + 1) * (360/7))" 
                                fill="url(#circuits)"
                                class="pointer-events-none"
                            />
                            <path 
                                :d="`M 150 150 L ${polarToCartesian(150, 150, 140, i * (360/7)).x} ${polarToCartesian(150, 150, 140, i * (360/7)).y}`"
                                stroke="rgba(217, 70, 239, 0.3)"
                                stroke-width="1"
                            />
                            <!-- Value text -->
                            <text 
                                :x="polarToCartesian(150, 150, 96, i * (360/7) + (180/7)).x"
                                :y="polarToCartesian(150, 150, 96, i * (360/7) + (180/7)).y"
                                fill="#ffffff"
                                font-size="11"
                                font-weight="900"
                                font-family="monospace"
                                text-anchor="middle"
                                filter="url(#segment-glow)"
                                :transform="`rotate(${i * (360/7) + (180/7)}, ${polarToCartesian(150, 150, 96, i * (360/7) + (180/7)).x}, ${polarToCartesian(150, 150, 96, i * (360/7) + (180/7)).y})`"
                            >
                                {{ prize }}
                            </text>
                            
                            <!-- Gold Coin Icon in sector -->
                            <circle 
                                :cx="polarToCartesian(150, 150, 68, i * (360/7) + (180/7)).x"
                                :cy="polarToCartesian(150, 150, 68, i * (360/7) + (180/7)).y"
                                r="7.5"
                                fill="#eab308"
                                stroke="#facc15"
                                stroke-width="1"
                                class="filter drop-shadow-[0_1px_3px_rgba(234,179,8,0.6)]"
                            />
                            <!-- Inner coin symbol C -->
                            <text
                                :x="polarToCartesian(150, 150, 68, i * (360/7) + (180/7)).x"
                                :y="polarToCartesian(150, 150, 68, i * (360/7) + (180/7)).y + 2.5"
                                fill="#854d0e"
                                font-size="7.5"
                                font-weight="bold"
                                text-anchor="middle"
                                :transform="`rotate(${i * (360/7) + (180/7)}, ${polarToCartesian(150, 150, 68, i * (360/7) + (180/7)).x}, ${polarToCartesian(150, 150, 68, i * (360/7) + (180/7)).y})`"
                            >
                                $
                            </text>
                        </g>

                        <!-- Central "GO" Processor button -->
                        <circle cx="150" cy="150" r="26" fill="#be46ff" stroke="#ffffff" stroke-width="1" class="cursor-pointer filter drop-shadow-[0_0_10px_rgba(168,85,247,0.5)]" @click="startSpin" />
                        <circle cx="150" cy="150" r="23" fill="#eab308" class="cursor-pointer" @click="startSpin" />
                        <text 
                            x="150" 
                            y="155" 
                            fill="#000000" 
                            font-size="14" 
                            font-weight="900" 
                            font-family="sans-serif" 
                            text-anchor="middle" 
                            class="cursor-pointer font-sans tracking-wide"
                            @click="startSpin"
                        >
                            GO
                        </text>
                    </svg>
                </div>
            </div>

                <!-- TOURS RESTANTS CAPSULE BADGE -->
                <div class="inline-flex items-center gap-1.5 bg-black/70 border border-white/10 px-6 py-2 rounded-full shadow-[0_4px_20px_rgba(0,0,0,0.6)] text-xs font-semibold mt-6 select-none">
                    <span class="text-slate-400 font-medium">Tours restants :</span>
                    <span class="text-[#facc15] font-black font-mono text-sm tracking-wider animate-pulse">{{ spinsLeft }}</span>
                </div>
            </div>

            <!-- ACTION TRIGGER BUTTON -->
            <div data-animate="fade-up" class="w-full">
                <button 
                    @click="startSpin"
                    :disabled="isSpinning || spinsLeft < 1"
                    class="w-full py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-fuchsia-600 hover:from-purple-500 hover:to-fuchsia-500 text-white font-extrabold uppercase tracking-widest text-xs transition-all shadow-[0_4px_25px_rgba(168,85,247,0.4)] disabled:from-slate-800 disabled:to-slate-900 disabled:text-slate-500 disabled:border disabled:border-white/5 disabled:shadow-none transform active:scale-[0.98] flex items-center justify-center gap-2 font-mono"
                >
                    <RotateCw v-if="isSpinning" class="w-4 h-4 animate-spin text-fuchsia-300" :stroke-width="2.5" />
                    Commencer le tirage
                </button>
            </div>

            <!-- TABS AREA: HISTORIQUE DES GAGNANTS & MES GAINS -->
            <div data-animate="fade-up" data-delay="150" class="glass rounded-3xl p-5 border border-purple-500/20 bg-[#070412]/80 mt-2 shadow-2xl">
                <!-- Tabs Header -->
                <div class="flex gap-2.5 p-1 bg-black/40 rounded-2xl border border-white/5 mb-4">
                    <button 
                        @click="activeTab = 'winners'"
                        class="flex-1 py-3 px-3 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all duration-300 text-center"
                        :class="activeTab === 'winners' ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-lg' : 'bg-transparent text-slate-400 hover:text-white'"
                    >
                        Historique des gagnants
                    </button>
                    <button 
                        @click="activeTab = 'myWinnings'"
                        class="flex-1 py-3 px-3 rounded-xl text-[11px] font-black uppercase tracking-wider transition-all duration-300 text-center"
                        :class="activeTab === 'myWinnings' ? 'bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-lg' : 'bg-transparent text-slate-400 hover:text-white'"
                    >
                        Mes gains
                    </button>
                </div>

                <!-- Tab 1: Historique des gagnants content -->
                <div v-if="activeTab === 'winners'" class="overflow-hidden h-52 relative no-scrollbar select-none">
                    <div class="space-y-2.5 animate-scroll-vertical">
                        <div 
                            v-for="(w, idx) in [...mockWinners, ...mockWinners]" 
                            :key="idx" 
                            class="flex items-center justify-between p-3.5 bg-black/30 border border-white/5 rounded-2xl"
                        >
                            <span class="text-xs text-slate-400 font-medium">
                                Félicitations <span class="text-white font-extrabold font-mono ml-1">{{ w.phone }}</span>
                            </span>
                            <span class="text-xs font-black font-mono text-emerald-400">
                                +{{ formatXAF(w.amount) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Mes gains content -->
                <div v-else class="space-y-2.5 max-h-56 overflow-y-auto no-scrollbar">
                    <div v-if="localMyWinnings.length === 0" class="py-8 text-center text-xs text-slate-500 font-mono uppercase tracking-wider">
                        Aucun gain enregistré pour le moment.
                    </div>
                    <div 
                        v-else
                        v-for="tx in localMyWinnings" 
                        :key="tx.id" 
                        class="flex items-center justify-between p-3.5 bg-black/30 border border-white/5 rounded-2xl"
                    >
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs text-slate-300 font-extrabold font-mono">
                                Félicitations (Vous)
                            </span>
                            <span class="text-[8px] text-slate-500 font-mono">
                                Ref: {{ tx.reference }}
                            </span>
                        </div>
                        <span class="text-xs font-black font-mono text-emerald-400">
                            +{{ formatXAF(tx.amount) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- RULES LIST CARD WITH FUCHSIA BORDER -->
            <div data-animate="fade-up" data-delay="200" class="glass rounded-3xl p-5 border border-fuchsia-500/25 bg-[#080210] shadow-2xl relative overflow-hidden">
                <div class="absolute inset-0 bg-grid opacity-[0.02]"></div>
                
                <h3 class="text-xs font-black text-white uppercase tracking-wider mb-4 font-mono">
                    Règles de l'événement
                </h3>
                
                <ul class="text-[11px] leading-relaxed text-slate-400 font-medium space-y-3.5 list-disc list-inside">
                    <li class="pl-1">
                        Cet événement est ouvert à tous les utilisateurs, nouveaux et existants, gratuitement et sans condition.
                    </li>
                    <li class="pl-1">
                        Chaque chance de tirage est attribuée exclusivement par le <span class="text-purple-400 font-bold">service des Ressources Humaines (RH)</span>.
                    </li>
                    <li class="pl-1">
                        Les chances de tirage sont émises automatiquement par le système ; entrez sur la page de l'événement et appuyez sur <span class="text-purple-400 font-bold">[GO]</span> ou <span class="text-purple-400 font-bold">[Commencer le tirage]</span> pour participer.
                    </li>
                    <li class="pl-1">
                        Les récompenses gagnées seront automatiquement créditées sur le solde de votre compte personnel.
                    </li>
                    <li class="pl-1">
                        Les résultats du tirage sont générés aléatoirement par le système ; l'ensemble du processus est ouvert, transparent, juste et impartial.
                    </li>
                    <li class="pl-1">
                        La plateforme se réserve le droit final d'interprétation de cet événement.
                    </li>
                </ul>
            </div>

        </div>

        <!-- SUCCESS WINNING MODAL -->
        <Teleport to="body">
            <div v-if="showSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                <div class="w-full max-w-md bg-[#0c051a] border-2 border-fuchsia-500/40 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="absolute right-[-10%] top-[-10%] w-32 h-32 bg-fuchsia-500/10 rounded-full blur-3xl"></div>
                        
                        <div class="h-16 w-16 rounded-full bg-fuchsia-500/10 border-2 border-fuchsia-500/40 flex items-center justify-center text-fuchsia-400 mb-6 mx-auto animate-bounce shadow-[0_0_20px_rgba(255,56,247,0.3)]">
                            <Trophy class="h-8 w-8" />
                        </div>
                        
                        <h3 class="text-lg font-black text-white uppercase tracking-wider font-mono">DISTRIBUTION VALIDÉE !</h3>
                        <p class="text-xs text-slate-400 mt-2 leading-relaxed">
                            Le protocole quantique de la console a complété le décryptage avec succès et injecté les récompenses dans votre portefeuille !
                        </p>
                        
                        <div class="my-6 p-4 rounded-2xl bg-black/60 border border-fuchsia-500/25 shadow-lg">
                            <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold font-mono">Crédits Captés</div>
                            <div class="text-3xl font-mono font-black text-fuchsia-400 mt-1.5 filter drop-shadow-[0_0_10px_rgba(255,56,247,0.5)]">
                                +{{ formatXAF(wonPrize) }}
                            </div>
                            <div class="text-[8px] text-slate-500 font-mono block mt-1">[ Ajouté au solde principal ]</div>
                        </div>

                        <button @click="showSuccessModal = false" class="w-full py-4 rounded-2xl bg-gradient-to-r from-fuchsia-600 to-purple-600 text-white font-black uppercase tracking-wider text-xs hover:from-fuchsia-500 hover:to-purple-500 transition-all shadow-[0_0_20px_rgba(255,56,247,0.4)]">
                            CONSOLIDER LES UNITÉS
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ERROR LIMIT MODAL -->
        <Teleport to="body">
            <div v-if="showLimitErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                <div class="w-full max-w-md bg-[#0a0514] border-2 border-rose-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-rose-500/10 border-2 border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 mx-auto animate-pulse">
                            <AlertCircle class="h-8 w-8" />
                        </div>
                        
                        <h3 class="text-base font-black text-white uppercase tracking-wider font-mono">ALERTE CONSOLE</h3>
                        <p class="text-xs text-rose-400 mt-4 font-mono leading-relaxed bg-rose-950/20 border border-rose-500/20 p-4 rounded-2xl">
                            {{ limitError }}
                        </p>

                        <button @click="showLimitErrorModal = false" class="w-full mt-6 py-3.5 rounded-2xl bg-white/5 border border-white/10 text-gray-300 font-bold uppercase tracking-wider text-xs hover:bg-white/10 transition-all">
                            Fermer le Log
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
@keyframes borderGlow {
    0% { box-shadow: 0 0 2px rgba(168,85,247,0.2),0 0 4px rgba(168,85,247,0.1); border-color: rgba(168,85,247,0.3); }
    50% { box-shadow: 0 0 8px rgba(168,85,247,0.6),0 0 12px rgba(168,85,247,0.3); border-color: rgba(168,85,247,0.8); }
    100% { box-shadow: 0 0 2px rgba(168,85,247,0.2),0 0 4px rgba(168,85,247,0.1); border-color: rgba(168,85,247,0.3); }
}
.glow-border { animation: borderGlow 3s ease-in-out infinite; border-style: solid; border-width: 1px; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
.animate-fadeInUp { animation: fadeInUp 0.4s ease-out forwards; }
.bg-grid {
    background-size: 40px 40px;
    background-image: 
        linear-gradient(to right, rgba(168, 85, 247, 0.03) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.03) 1px, transparent 1px);
}
@keyframes scrollVertical {
    0% { transform: translateY(0); }
    100% { transform: translateY(calc(-50% - 5px)); }
}
.animate-scroll-vertical {
    animation: scrollVertical 22s linear infinite;
}
.animate-scroll-vertical:hover {
    animation-play-state: paused;
}
@keyframes spinSlow {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.animate-spin-slow {
    animation: spinSlow 30s linear infinite;
    transform-origin: center;
}
</style>
