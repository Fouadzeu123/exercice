<script setup lang="ts">
import { computed, ref, onMounted, watch, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Cpu, 
    Clock, 
    ArrowLeft, 
    CheckCircle, 
    TrendingUp, 
    Box, 
    BrainCircuit, 
    Activity, 
    ShieldAlert,
    Coins,
    Lock,
    X,
    AlertCircle
} from 'lucide-vue-next';
import { t } from '@/utils/trans';

const showSuccessCard = ref(false);
const successMessage = ref('');
const showErrorCard = ref(false);
const errorMessage = ref('');

const props = defineProps<{
    product: {
        id: number;
        name: string;
        amount: number;
        generation_profit: number;
        technology_level: number;
        duration: number;
        stock_quantity: number | null;
        limited_purchase_count?: number | null;
        max_purchase_limit?: number | null;
        image?: string | null;
        image_url?: string | null;
        isVault: boolean;
        fixed_return?: string;
        profit_amount?: string;
    };
    type: 'node' | 'vault';
    activeUserNode?: {
        id: number;
        node_id: number;
        node_name: string;
        generation_profit: string;
        node_amount: string;
        technology_level: number;
    } | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('console principale', 'main console'), href: '/dashboard' },
    { title: props.product.isVault ? 'ARM Vaults' : t('marché des nœuds', 'nodes market'), href: props.product.isVault ? '/vaults' : '/nodes' },
    { title: props.product.name, href: '' },
];

const page = usePage();
const user = computed(() => page.props.auth.user);
const rentForm = useForm({});
const isProcessing = ref(false);

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const getProductImage = () => {
    if (props.product.isVault) {
        return 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=600&auto=format';
    }
    return props.product.image || props.product.image_url || 'https://images.unsplash.com/photo-1591453089816-0fbb971b454c?auto=format&fit=crop&w=600&q=80';
};

// Check node statuses if it's a node
const isCurrentNode = computed(() => {
    return props.activeUserNode && props.activeUserNode.node_id === props.product.id;
});

const isUpgradeNode = computed(() => {
    if (props.product.isVault) return false;
    if (!props.activeUserNode) return false;
    return props.product.technology_level > props.activeUserNode.technology_level;
});

const isLockedNode = computed(() => {
    if (props.product.isVault) return false;
    if (!props.activeUserNode) return false;
    return props.product.technology_level < props.activeUserNode.technology_level;
});

const handlePurchase = () => {
    if (user.value?.balance < props.product.amount) {
        errorMessage.value = t('Solde insuffisant pour cette opération.', 'Insufficient balance for this operation.');
        showErrorCard.value = true;
        return;
    }
    
    isProcessing.value = true;
    const url = props.product.isVault 
        ? `/vaults/${props.product.id}/invest` 
        : `/nodes/${props.product.id}/rent`;

    rentForm.post(url, {
        onSuccess: () => {
            successMessage.value = props.product.isVault 
                ? t('Votre investissement dans le Vault a été validé avec succès !', 'Your Vault investment was successfully approved!')
                : t('La location de votre nœud de calcul GPU a été activée !', 'Your GPU computing node lease was successfully activated!');
            showSuccessCard.value = true;
        },
        onFinish: () => {
            isProcessing.value = false;
        },
        onError: (errors: any) => {
            errorMessage.value = errors.error || t('Une erreur s\'est produite.', 'An error occurred.');
            showErrorCard.value = true;
        }
    });
};

const pageLoaded = ref(false);
onMounted(() => {
    setTimeout(() => {
        pageLoaded.value = true;
    }, 100);
});

// Watch to freeze scroll on modal activation
watch([showSuccessCard, showErrorCard], ([newSuccess, newError]) => {
    if (newSuccess || newError) {
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
    <Head :title="product.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24 text-white font-sans transition-all duration-500" :class="pageLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'">
            
            <!-- Header Nav Back -->
            <div class="flex items-center justify-between bg-[#0c0f1d] p-3 rounded-2xl border border-purple-500/10 shadow-lg">
                <Link :href="product.isVault ? '/vaults' : '/nodes'" class="flex items-center gap-2 text-xs font-black text-purple-400 hover:text-white uppercase transition-colors select-none">
                    <ArrowLeft class="h-4.5 w-4.5" :stroke-width="2.5" />
                    {{ t('Retour', 'Back') }}
                </Link>
                <span class="text-[9px] font-black uppercase text-slate-400 font-mono">
                    {{ product.isVault ? 'STAKING SPEC' : 'GPU AI CORE' }}
                </span>
            </div>

            <!-- Product Grand holographic View Card -->
            <div class="relative group bg-[#090b15] border border-purple-500/20 rounded-3xl overflow-hidden shadow-2xl">
                <!-- Top Header Info -->
                <div class="p-4 bg-[#0c0f1d] flex items-center justify-between border-b border-white/5">
                    <span class="text-xs font-black text-white uppercase tracking-wider truncate max-w-[70%]">
                        {{ product.isVault ? t('Vault d\'Épargne ARM', 'ARM Savings Vault') : t('Location Carte Unique ' + product.name, 'Rental Single Card ' + product.name) }}
                    </span>
                    <!-- Duration Badge -->
                    <span class="text-[12px] font-black bg-purple-500 text-black px-4 py-2 rounded-xl uppercase tracking-wider font-mono shadow-md">
                        {{ product.duration }} {{ t('Jours', 'Days') }}
                    </span>
                </div>

                <!-- Product Image Banner with scan lines -->
                <div class="w-full h-56 overflow-hidden relative bg-black/30">
                    <img :src="getProductImage()" :alt="product.name" class="w-full h-full object-cover opacity-90" />
                    <!-- Absolute glow overlay -->
                    <div class="absolute inset-0 bg-gradient-to-t from-[#090b15] via-transparent to-transparent"></div>
                    <div class="absolute inset-x-0 top-0 h-[1.5px] bg-gradient-to-r from-transparent via-purple-400 to-transparent shadow-[0_0_10px_rgba(168,85,247,0.8)] animate-scan pointer-events-none"></div>
                </div>

                <!-- Brand Purple Banner (Premium ARM design) -->
                <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-800 px-5 py-3.5 flex items-center gap-3 justify-between text-white border-t border-purple-500/30">
                    <div class="flex items-center gap-1.5">
                        <img src="/images/logo.jpg" class="h-4.5 w-4.5 rounded object-cover border border-white/20 shrink-0 shadow-sm" alt="Logo" />
                        <div class="bg-black/40 text-purple-200 font-extrabold text-[8px] px-1.5 py-0.5 rounded border border-purple-400/20 leading-none shrink-0 tracking-tighter font-mono" v-if="!product.isVault">AI GPU</div>
                        <div class="bg-black/40 text-emerald-300 font-extrabold text-[8px] px-1.5 py-0.5 rounded border border-emerald-400/20 leading-none shrink-0 tracking-tighter font-mono" v-else>STAKING</div>
                    </div>
                    <div class="text-sm font-black text-white uppercase tracking-wider truncate text-right flex-1 font-mono">
                        {{ product.isVault ? product.name : product.name.replace(/Location\s+/gi, '').replace(/Carte\s+Unique\s+/gi, '').toUpperCase() }}
                    </div>
                </div>
            </div>

            <!-- Technical Specification Sheets -->
            <div class="bg-[#0c0f1d]/90 border border-white/10 rounded-2xl p-5 shadow-xl backdrop-blur-sm space-y-4">
                <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2 border-b border-white/5 pb-3">
                    <Activity class="h-4.5 w-4.5 text-purple-400" :stroke-width="2.5" />
                    {{ t('Spécifications du Système', 'System Specifications') }}
                </h3>

                <div class="grid grid-cols-1 gap-3.5 text-xs">
                    <!-- Standard specs rows -->
                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5">
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ t('Nom de l\'Unité', 'Unit Name') }}</span>
                        <span class="font-extrabold text-white text-[11px] font-mono">{{ product.name }}</span>
                    </div>

                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5" v-if="!product.isVault">
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ t('Niveau Technologique', 'Tech Level') }}</span>
                        <span class="font-black text-purple-400 text-[11px] font-mono">LEVEL {{ product.technology_level }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5" v-else>
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ t('Type d\'Épargne', 'Savings Type') }}</span>
                        <span class="font-black text-emerald-400 text-[11px] font-mono">ARM VAULT STAKING</span>
                    </div>

                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5">
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ t('Durée de Co-Calcul', 'Co-Processing Term') }}</span>
                        <span class="font-extrabold text-white text-[11px] font-mono">{{ product.duration }} Jours</span>
                    </div>

                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5">
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ product.isVault ? t('Rendement Journalier', 'Daily Yield') : t('Revenus par Session', 'Earning per Session') }}</span>
                        <span class="font-extrabold text-purple-300 text-[11px] font-mono">+{{ formatXAF(product.generation_profit) }} / jour</span>
                    </div>

                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5" v-if="product.isVault">
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ t('Retour Final Brut', 'Gross Final Return') }}</span>
                        <span class="font-black text-emerald-400 text-[12px] font-mono">{{ formatXAF(product.fixed_return || 0) }}</span>
                    </div>
                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5" v-else>
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ t('Gains Globaux Estimés', 'Total Estimated Gains') }}</span>
                        <span class="font-black text-emerald-400 text-[12px] font-mono">{{ formatXAF(product.generation_profit * product.duration) }}</span>
                    </div>

                    <div class="flex justify-between items-center bg-black/30 p-3 rounded-xl border border-white/5">
                        <span class="text-slate-400 font-bold uppercase text-[9px]">{{ product.isVault ? t('Sécurité', 'Security') : t('Disponibilité Stock', 'Stock Qty') }}</span>
                        <span class="font-bold text-white text-[10px] font-mono">
                            {{ product.isVault ? '100% GARANTI' : (product.stock_quantity ?? '12018') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Upgrade Info alerts if necessary -->
            <div v-if="isUpgradeNode" class="p-4 rounded-2xl bg-purple-950/20 border border-purple-500/20 flex items-start gap-3 text-xs leading-relaxed">
                <ShieldAlert class="h-5 w-5 text-purple-400 shrink-0 mt-0.5 animate-pulse" :stroke-width="2.5" />
                <div>
                    <span class="font-bold text-purple-400 block uppercase tracking-wide text-[10px]">{{ t('Mise à niveau de la console', 'Console Upgrade') }}</span>
                    <p class="text-[10px] text-slate-400 mt-1">
                        {{ t('Louer ce processeur AI résiliera immédiatement votre abonnement inférieur actuel et recréditera sa valeur d\'achat originale dans votre solde.', 'Renting this AI processor will immediately terminate your active inferior subscription and refund its original purchase value to your balance.') }}
                    </p>
                </div>
            </div>

            <!-- Active User Node details if already active -->
            <div v-if="isCurrentNode" class="p-4 rounded-2xl bg-emerald-950/20 border border-emerald-500/20 flex items-start gap-3 text-xs leading-relaxed">
                <CheckCircle class="h-5 w-5 text-emerald-400 shrink-0 mt-0.5" :stroke-width="2.5" />
                <div>
                    <span class="font-bold text-emerald-400 block uppercase tracking-wide text-[10px]">{{ t('Équipement déjà loué', 'Already Rented Equipment') }}</span>
                    <p class="text-[10px] text-slate-400 mt-1">
                        {{ t('Vous disposez déjà de cette unité active dans votre baie d\'infrastructure. Visitez l\'onglet Génération pour lancer le traitement quotidien.', 'You already have this active unit running in your infrastructure bay. Visit the Generation tab to start daily processing.') }}
                    </p>
                </div>
            </div>

            <!-- Confirm block at bottom -->
            <div class="bg-[#0c0f1d]/90 border border-white/10 rounded-2xl p-5 shadow-xl backdrop-blur-sm space-y-4">
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-400 font-bold uppercase">{{ t('Solde Actuel', 'Current Balance') }}</span>
                    <span class="font-black text-white font-mono text-sm">{{ formatXAF(user?.balance || 0) }}</span>
                </div>
                <div class="h-px bg-white/5"></div>
                <div class="flex justify-between items-center text-xs">
                    <span class="text-slate-200 font-bold uppercase">{{ product.isVault ? t('Dépôt Requis', 'Deposit Required') : t('Frais de Réservation', 'Rental Fee') }}</span>
                    <span class="font-black text-yellow-400 font-mono text-xl tracking-tight shadow-sm">{{ formatXAF(product.amount) }}</span>
                </div>

                <!-- Action Button -->
                <button 
                    v-if="isCurrentNode"
                    disabled
                    class="w-full py-4 rounded-xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-black text-xs uppercase tracking-wider"
                >
                    {{ t('Déjà Actif', 'Already Active') }}
                </button>

                <button 
                    v-else-if="isLockedNode"
                    disabled
                    class="w-full py-4 rounded-xl bg-slate-800 text-slate-500 border border-white/5 font-black text-xs uppercase tracking-wider"
                >
                    {{ t('Unité verrouillée (Niveau inférieur)', 'Unit Locked (Lower Level)') }}
                </button>

                <button 
                    v-else
                    @click="handlePurchase"
                    :disabled="isProcessing"
                    class="w-full py-4 rounded-xl bg-purple-500 text-black hover:bg-purple-400 font-black text-xs uppercase tracking-widest shadow-[0_0_15px_rgba(168,85,247,0.4)] hover:shadow-[0_0_25px_rgba(168,85,247,0.6)] transition-all flex items-center justify-center gap-2"
                >
                    <Coins class="h-4.5 w-4.5" v-if="isProcessing" />
                    <span v-if="isProcessing">{{ t('Traitement en cours...', 'Processing...') }}</span>
                    <span v-else class="flex items-center gap-2">
                        <Lock class="h-4 w-4" v-if="product.isVault" :stroke-width="2.5" />
                        <Cpu class="h-4 w-4" v-else :stroke-width="2.5" />
                        {{ product.isVault ? t('Confirmer l\'Investissement', 'Confirm Investment') : t('Confirmer la Location', 'Confirm Rental') }}
                    </span>
                </button>
            </div>

            <!-- SUCCESS CARD MODAL -->
            <Teleport to="body">
                <div v-if="showSuccessCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                    <div class="glass max-w-xl w-full rounded-2xl border border-emerald-500/30 p-6 text-center shadow-[0_0_50px_rgba(16,185,129,0.25)] relative">
                        <div class="h-16 w-16 rounded-full bg-emerald-500/10 border-2 border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6 mx-auto animate-bounce">
                            <CheckCircle class="h-8 w-8" />
                        </div>
                        
                        <span class="text-emerald-400 font-extrabold uppercase text-[10px] tracking-widest block mb-1">Succès Système</span>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider mb-2">Opération Validée</h3>
                        <p class="text-slate-300 text-xs leading-relaxed font-sans mb-6">
                            {{ successMessage }}
                        </p>
                        <button 
                            @click="showSuccessCard = false; $inertia.visit('/dashboard')" 
                            class="w-full py-3.5 rounded-xl bg-emerald-500 text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(16,185,129,0.3)] hover:bg-emerald-400 transition-all duration-300"
                        >
                            Retour au Tableau de Bord
                        </button>
                    </div>
                </div>
            </Teleport>

            <!-- ERROR CARD MODAL -->
            <Teleport to="body">
                <div v-if="showErrorCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                    <div class="glass max-w-xl w-full rounded-2xl border border-rose-500/30 p-6 text-center shadow-[0_0_50px_rgba(244,63,94,0.25)] relative">
                        <div class="h-16 w-16 rounded-full bg-rose-500/10 border-2 border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 mx-auto animate-pulse">
                            <AlertCircle class="h-8 w-8" />
                        </div>
                        
                        <span class="text-rose-400 font-extrabold uppercase text-[10px] tracking-widest block mb-1">Alerte Système</span>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider mb-2">Erreur d'Opération</h3>
                        <p class="text-slate-300 text-xs leading-relaxed font-mono mb-6">
                            {{ errorMessage }}
                        </p>
                        <button 
                            @click="showErrorCard = false" 
                            class="w-full py-3.5 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold uppercase tracking-wider text-xs hover:bg-rose-500 hover:text-black transition-all duration-300"
                        >
                            Fermer
                        </button>
                    </div>
                </div>
            </Teleport>

        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes scan { 0% { top: 0%; opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { top: 100%; opacity: 0; } }
.animate-scan { animation: scan 4s linear infinite; }
</style>
