<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Cpu,
    Coins,
    Lock,
    Unlock,
    Award,
    Zap,
    TrendingUp,
    CheckCircle2,
    AlertCircle,
    ChevronRight,
    Volume2,
    X
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps<{
    products: Array<{
        id: number;
        name: string;
        description: string;
        amount: number;
        daily_salary: number;
        required_vip_level: number;
        avip_level: number;
        image: string;
        active: boolean;
    }>;
    userProducts: Array<{
        id: number;
        avip_product_id: number;
        amount: string;
        active: boolean;
        purchased_at: string;
        avip_product?: {
            name: string;
            daily_salary: number;
        }
    }>;
    userVipLevel: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Équipements AVIP & Salaire', href: '/avip-products' },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const claimProcessing = ref(false);
const showClaimSuccessModal = ref(false);
const claimedAmount = ref(0);
const showErrorModal = ref(false);
const errorMessage = ref('');

const dailySalaries = {
    0: 0.00,
    1: 100.00,
    2: 250.00,
    3: 500.00,
    4: 1000.00,
    5: 2000.00,
};

const userDailyRate = computed(() => {
    const rate = dailySalaries[props.userVipLevel as keyof typeof dailySalaries];
    return rate !== undefined ? rate : 0.00;
});

const isSalaryClaimedToday = computed(() => {
    if (!user.value?.last_salary_claim_date) return false;
    const lastClaim = new Date(user.value.last_salary_claim_date);
    const today = new Date();
    return lastClaim.toDateString() === today.toDateString();
});

const handleClaimSalary = async () => {
    if (claimProcessing.value || isSalaryClaimedToday.value) return;
    claimProcessing.value = true;
    try {
        const res = await axios.post('/avip-products/claim-salary');
        // Let's reload to update balance and state
        router.reload({
            only: ['auth', 'userProducts'],
            onSuccess: () => {
                claimedAmount.value = userDailyRate.value;
                showClaimSuccessModal.value = true;
                claimProcessing.value = false;
            }
        });
    } catch (e: any) {
        claimProcessing.value = false;
        errorMessage.value = e.response?.data?.error || "Une erreur est survenue lors de la réclamation.";
        showErrorModal.value = true;
    }
};

const purchaseForm = useForm({});
const showConfirmModal = ref<any | null>(null);
const showPurchaseSuccessModal = ref(false);
const successMessage = ref('');

const initiatePurchase = (product: any) => {
    if (props.userVipLevel < product.required_vip_level) {
        errorMessage.value = `Niveau VIP insuffisant. Vous devez être au minimum VIP ${product.required_vip_level} pour louer ce produit. Votre niveau actuel : VIP ${props.userVipLevel}.`;
        showErrorModal.value = true;
        return;
    }
    
    // Check if already owned
    const owned = props.userProducts.some(p => p.avip_product_id === product.id);
    if (owned) {
        errorMessage.value = "Vous possédez déjà cet accélérateur AVIP actif.";
        showErrorModal.value = true;
        return;
    }
    
    if (user.value.balance < product.amount) {
        errorMessage.value = "Solde insuffisant pour louer cet accélérateur AVIP.";
        showErrorModal.value = true;
        return;
    }
    
    showConfirmModal.value = product;
};

const confirmPurchase = () => {
    if (!showConfirmModal.value) return;
    const productName = showConfirmModal.value.name;
    purchaseForm.post(`/avip-products/${showConfirmModal.value.id}/purchase`, {
        onSuccess: () => {
            showConfirmModal.value = null;
            successMessage.value = t(`L'accélérateur AVIP ${productName} a été loué avec succès ! Votre dividende quotidien a été mis à jour.`, `AVIP accelerator ${productName} rented successfully! Your daily dividend has been updated.`);
            showPurchaseSuccessModal.value = true;
        },
        onError: (err: any) => {
            showConfirmModal.value = null;
            errorMessage.value = err.error || "Erreur lors de l'achat.";
            showErrorModal.value = true;
        }
    });
};

const { containerRef } = useRevealAnimation();

// Watch to freeze scroll on modal activation
watch([showConfirmModal, showErrorModal, showClaimSuccessModal, showPurchaseSuccessModal], ([newConfirm, newError, newClaim, newPurchase]) => {
    if (newConfirm || newError || newClaim || newPurchase) {
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
    <Head title="Marché AVIP & Salaire" />
    <AppLayout :breadcrumbs="breadcrumbs" class="bg-gradient-to-b from-[#05020c] to-[#0e061b]">
        
        <div ref="containerRef" class="relative z-10 w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24 px-4">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg glow-border">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <Cpu class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-sm font-black text-white uppercase tracking-wide">AVIP & Salaire</h2>
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Console de dividendes et accélération</p>
                    </div>
                </div>
                <div class="text-right shrink-0">
                    <span class="text-[8px] text-slate-500 uppercase tracking-widest block font-bold">Solde</span>
                    <span class="text-xs font-mono font-black text-purple-400">{{ formatXAF(user?.balance || 0) }}</span>
                </div>
            </div>

            <!-- DAILY SALARY HUB -->
            <div data-animate="scale-up" data-delay="100" class="bg-gradient-to-b from-[#0f0724] to-[#070312] border border-purple-500/20 rounded-3xl p-5 shadow-2xl relative overflow-hidden glow-border">
                <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_30%,rgba(168,85,247,0.06),transparent_70%)] pointer-events-none"></div>
                
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <span class="text-[8px] text-slate-400 uppercase font-black tracking-widest block">Mon Dividende Journalier</span>
                        <div class="text-2xl font-mono font-black text-purple-400 mt-1 filter drop-shadow-[0_0_10px_rgba(168,85,247,0.3)]">
                            {{ formatXAF(userDailyRate) }} / jour
                        </div>
                    </div>
                    <span class="px-2 py-0.5 bg-purple-900/60 text-purple-300 border border-purple-700/30 rounded text-[8px] font-black uppercase tracking-wider">
                        VIP {{ props.userVipLevel }}
                    </span>
                </div>

                <p class="text-[10px] text-slate-400 leading-relaxed mb-5">
                    Chaque jour, réclamez votre salaire d'infrastructure calculé directement sur votre niveau VIP.
                </p>

                <!-- Claim Button -->
                <button 
                    @click="handleClaimSalary"
                    :disabled="isSalaryClaimedToday || claimProcessing || props.userVipLevel < 1"
                    class="w-full py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest transition-all duration-300 flex items-center justify-center gap-2"
                    :class="props.userVipLevel < 1
                        ? 'bg-purple-950/20 text-slate-500 border border-white/5 cursor-not-allowed'
                        : (isSalaryClaimedToday 
                            ? 'bg-emerald-950/40 text-emerald-400 border border-emerald-800/30 cursor-not-allowed' 
                            : 'bg-purple-600 hover:bg-purple-500 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)] active:scale-[0.98]')"
                >
                    <CheckCircle2 v-if="isSalaryClaimedToday" class="w-4 h-4" />
                    <Coins v-else class="w-4 h-4" :class="claimProcessing ? 'animate-spin' : ''" />
                    {{ props.userVipLevel < 1 ? 'AUCUN SALAIRE DISPONIBLE (VIP 0)' : (isSalaryClaimedToday ? 'SALAIRE RÉCLAMÉ AUJOURD\'HUI' : (claimProcessing ? 'SYNCHRONISATION...' : 'RÉCLAMER MON SALAIRE')) }}
                </button>
            </div>

            <!-- SALARIES TABLE SUMMARY -->
            <div data-animate="fade-up" data-delay="150" class="bg-black/30 border border-white/5 rounded-2xl p-4">
                <span class="text-[9px] text-slate-500 uppercase tracking-widest font-black block mb-3 text-center sm:text-left">Grille Salaires VIP</span>
                <div class="grid grid-cols-5 gap-2 text-center text-[9px] font-bold">
                    <div v-for="level in [1, 2, 3, 4, 5]" :key="level" class="p-2 rounded-xl bg-[#0a0514]/60 border" :class="props.userVipLevel === level ? 'border-purple-500/50 text-purple-400' : 'border-white/5 text-slate-500'">
                        <span class="block text-[8px] uppercase">VIP{{ level }}</span>
                        <span class="block font-mono font-black mt-1">{{ dailySalaries[level as keyof typeof dailySalaries] }}</span>
                    </div>
                </div>
            </div>

            <!-- OWNED AVIP ACCELERATORS TAB -->
            <div v-if="props.userProducts.length > 0" data-animate="fade-up" data-delay="200" class="bg-[#0f0724]/40 border border-purple-500/10 rounded-3xl p-5 shadow-lg">
                <div class="flex items-center gap-2 mb-3">
                    <Unlock class="w-4 h-4 text-emerald-400 animate-pulse" />
                    <h3 class="text-xs font-black text-white uppercase tracking-wider">Mes Équipements Actifs ({{ props.userProducts.length }})</h3>
                </div>
                <div class="space-y-3">
                    <div v-for="up in props.userProducts" :key="up.id" class="p-3.5 rounded-xl bg-black/40 border border-emerald-500/20 flex justify-between items-center">
                        <div>
                            <div class="text-xs font-black text-white uppercase tracking-wider">{{ up.avip_product?.name }}</div>
                            <span class="text-[8px] text-emerald-400 uppercase tracking-widest block font-bold mt-0.5">Calcul en cours... (+{{ formatXAF(up.avip_product?.daily_salary || 0) }}/j)</span>
                        </div>
                        <div class="text-right">
                            <span class="text-[8px] text-slate-500 uppercase block">Acheté le</span>
                            <span class="text-[9px] font-mono text-gray-300 font-bold block">{{ new Date(up.purchased_at).toLocaleDateString('fr-FR') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AVIP ACCELERATORS MARKET CATALOG -->
            <div data-animate="fade-up" data-delay="250" class="space-y-4">
                <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                    <Zap class="w-4 h-4 text-purple-400 animate-pulse" />
                    Accélérateurs AVIP Disponibles
                </h3>

                <div v-for="product in props.products" :key="product.id" class="group relative bg-[#0a0416] border rounded-3xl overflow-hidden hover:border-purple-400 transition-all duration-300 shadow-lg flex flex-col justify-between" :class="props.userVipLevel < product.required_vip_level ? 'border-red-500/20 grayscale opacity-80' : 'border-purple-500/20'">
                    
                    <!-- Top header info -->
                    <div class="p-3 bg-[#0e071d] flex items-center justify-between border-b border-white/5">
                        <span class="text-[9px] font-black text-white uppercase tracking-wider truncate max-w-[70%]">
                            {{ product.name }}
                        </span>
                        <!-- Required VIP status lock -->
                        <span class="text-[8px] font-black px-2.5 py-1 rounded-lg uppercase tracking-wider flex items-center gap-1" :class="props.userVipLevel < product.required_vip_level ? 'bg-red-950 text-red-400 border border-red-500/20' : 'bg-purple-600 text-white'">
                            <Lock v-if="props.userVipLevel < product.required_vip_level" class="w-2.5 h-2.5" />
                            <Unlock v-else class="w-2.5 h-2.5" />
                            REQUIS: VIP {{ product.required_vip_level }}
                        </span>
                    </div>

                    <!-- Graphic Image Area -->
                    <div class="w-full h-36 overflow-hidden relative bg-black/20">
                        <img :src="product.image" class="w-full h-full object-cover opacity-80 group-hover:opacity-100 transition-all duration-500 group-hover:scale-105" :alt="product.name">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#0a0416] via-transparent to-transparent"></div>
                    </div>

                    <!-- Description and details -->
                    <div class="px-4 pb-4 pt-1">
                        <p class="text-[10px] text-slate-400 leading-relaxed min-h-[30px] mb-3">
                            {{ product.description }}
                        </p>

                        <!-- Brand miniature bar replacing ARM HOLDING with logo markup -->
                        <div class="bg-gradient-to-r from-purple-950 via-[#13072b] to-purple-950 rounded-lg p-2.5 border border-purple-500/10 flex items-center justify-between mb-4">
                            <div class="flex items-center gap-2">
                                <img src="/images/logo.jpg" class="h-4.5 w-4.5 rounded object-cover border border-white/20 shrink-0" alt="Logo" />
                                <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest font-mono">AVIP SYSTEM</span>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-[8px] text-slate-500 uppercase block font-bold">Dividende AVIP</span>
                                <span class="text-[10px] font-mono font-black text-emerald-400">+{{ formatXAF(product.daily_salary) }}/jour</span>
                            </div>
                        </div>

                        <!-- Rent Button & Price -->
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="text-[8px] text-slate-500 uppercase block">Licence AVIP</span>
                                <span class="text-sm font-mono font-black text-purple-400">{{ formatXAF(product.amount) }}</span>
                            </div>

                            <button 
                                @click="initiatePurchase(product)"
                                :disabled="props.userVipLevel < product.required_vip_level || props.userProducts.some(p => p.avip_product_id === product.id)"
                                class="px-4 py-2 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all"
                                :class="props.userProducts.some(p => p.avip_product_id === product.id)
                                    ? 'bg-emerald-950 text-emerald-400 border border-emerald-800/30'
                                    : (props.userVipLevel < product.required_vip_level 
                                        ? 'bg-red-950/20 text-red-500/50 border border-red-500/10 cursor-not-allowed'
                                        : 'bg-purple-600 hover:bg-purple-500 text-white shadow-[0_0_10px_rgba(168,85,247,0.3)]')"
                            >
                                {{ props.userProducts.some(p => p.avip_product_id === product.id) ? 'DÉJÀ ACQUIS' : 'LOUER L\'APPAREIL' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- CONFIRMATION RENTAL MODAL -->
        <Teleport to="body">
            <div v-if="showConfirmModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-sm font-black text-white uppercase tracking-wider flex items-center gap-1.5">
                                <Cpu class="w-4 h-4 text-purple-400 animate-pulse" />
                                Confirmer la Location
                            </h3>
                            <button @click="showConfirmModal = null" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-gray-400" /></button>
                        </div>

                        <p class="text-xs text-slate-400 leading-relaxed mb-5">
                            Vous êtes sur le point d'activer le système de calcul décentralisé <span class="text-white font-bold">{{ showConfirmModal.name }}</span> pour une durée permanente de dividendes.
                        </p>

                        <div class="bg-black/40 border border-white/5 rounded-xl p-4 mb-6 space-y-2">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-slate-500 uppercase tracking-widest font-bold">Produit AVIP :</span>
                                <span class="text-white font-black uppercase">{{ showConfirmModal.name }}</span>
                            </div>
                            <div class="flex justify-between text-[10px]">
                                <span class="text-slate-500 uppercase tracking-widest font-bold">Rendement :</span>
                                <span class="text-emerald-400 font-mono font-black">+{{ formatXAF(showConfirmModal.daily_salary) }}/j</span>
                            </div>
                            <div class="flex justify-between text-[10px] border-t border-white/5 pt-2 mt-2">
                                <span class="text-slate-500 uppercase tracking-widest font-bold">Débit Requis :</span>
                                <span class="text-purple-400 font-mono font-black">{{ formatXAF(showConfirmModal.amount) }}</span>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button @click="confirmPurchase" class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-xs rounded-xl shadow-[0_0_15px_rgba(168,85,247,0.4)]">
                                ACCEPTER & LOUER
                            </button>
                            <button @click="showConfirmModal = null" class="flex-1 py-3 bg-white/5 border border-white/10 text-gray-300 font-bold uppercase tracking-wider text-xs rounded-xl hover:bg-white/10">
                                ANNULER
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ERROR MESSAGE MODAL -->
        <Teleport to="body">
            <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0e071d] border border-rose-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 mx-auto animate-pulse">
                            <AlertCircle class="h-8 w-8" />
                        </div>
                        
                        <h3 class="text-base font-black text-white uppercase tracking-wider">Alerte protocole</h3>
                        <p class="text-xs text-rose-400 mt-4 font-mono leading-relaxed bg-rose-950/20 border border-rose-500/20 p-3.5 rounded-xl">
                            {{ errorMessage }}
                        </p>

                        <button @click="showErrorModal = false" class="w-full mt-6 py-3 rounded-2xl bg-white/5 border border-white/10 text-gray-300 font-bold uppercase tracking-wider text-xs hover:bg-white/10 transition-all">
                            Fermer la notification
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- DIVIDEND SUCCESS CLAIM MODAL -->
        <Teleport to="body">
            <div v-if="showClaimSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0e071d] border border-emerald-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6 mx-auto animate-bounce shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                            <CheckCircle2 class="h-8 w-8" />
                        </div>
                        
                        <h3 class="text-base font-black text-white uppercase tracking-wider font-mono">Dividende validé</h3>
                        <p class="text-xs text-gray-400 mt-2 leading-relaxed">
                            Le protocole de dividende a injecté les crédits dans votre compte avec succès !
                        </p>
                        
                        <div class="my-6 p-4 rounded-xl bg-black/50 border border-emerald-500/25 shadow-lg">
                            <div class="text-[10px] text-slate-500 uppercase tracking-widest font-bold font-mono">Salaire Capté</div>
                            <div class="text-2xl font-mono font-black text-emerald-400 mt-1">
                                +{{ formatXAF(claimedAmount) }}
                            </div>
                        </div>

                        <button @click="showClaimSuccessModal = false" class="w-full py-3.5 rounded-2xl bg-emerald-500 text-black font-black uppercase tracking-wider text-xs hover:bg-emerald-400 transition-all shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                            TERMINER LA TRANSACTION
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- AVIP PURCHASE SUCCESS MODAL -->
        <Teleport to="body">
            <div v-if="showPurchaseSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0e071d] border border-emerald-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6 mx-auto animate-bounce shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                            <CheckCircle2 class="h-8 w-8" />
                        </div>
                        
                        <h3 class="text-base font-black text-white uppercase tracking-wider font-mono font-black">Location Activée</h3>
                        <p class="text-xs text-gray-300 mt-2.5 leading-relaxed bg-black/40 border border-emerald-500/10 p-3.5 rounded-xl">
                            {{ successMessage }}
                        </p>

                        <button @click="showPurchaseSuccessModal = false" class="w-full mt-6 py-3.5 rounded-2xl bg-emerald-500 text-black font-black uppercase tracking-wider text-xs hover:bg-emerald-400 transition-all shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                            Fermer la notification
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
</style>
