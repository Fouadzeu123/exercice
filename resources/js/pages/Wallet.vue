<script setup lang="ts">
import { ref, onMounted, watch, onUnmounted } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Wallet, 
    ArrowUpRight, 
    ArrowDownLeft, 
    CreditCard, 
    Clock, 
    Cpu, 
    CheckCircle2, 
    Smartphone, 
    AlertTriangle,
    Coins
} from 'lucide-vue-next';

// Props
const props = defineProps<{
    totalDeposits: number;
    totalWithdrawals: number;
    transactions: Array<{
        id: number;
        amount: string;
        type: string;
        status: string;
        reference: string;
        created_at: string;
    }>;
    defaultAction: string;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de Bord',
        href: '/dashboard',
    },
    {
        title: 'Mon Portefeuille',
        href: '/wallet',
    },
];

// Page Context
const page = usePage();
const user = page.props.auth.user;

// Modals and tabs state
const activeTab = ref<'history' | 'deposit' | 'withdraw'>('history');
const selectedMethod = ref<'mtn' | 'orange' | 'usdt' | 'notchpay' | null>(null);
const showErrorModal = ref(false);
const errorMessage = ref('');

// Forms
const depositForm = useForm({
    amount: '',
    method: '',
    phone: '',
    usdt_hash: ''
});

const withdrawForm = useForm({
    amount: '',
    method: '',
    phone: '',
    wallet_address: ''
});

onMounted(() => {
    if (props.defaultAction === 'deposit') {
        activeTab.value = 'deposit';
    } else if (props.defaultAction === 'withdraw') {
        activeTab.value = 'withdraw';
    }
});

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
};

const selectMethod = (method: 'mtn' | 'orange' | 'usdt' | 'notchpay') => {
    selectedMethod.value = method;
    depositForm.method = method;
    withdrawForm.method = method;
};

const handleDepositSubmit = () => {
    depositForm.post('/wallet/deposit', {
        onSuccess: () => {
            depositForm.reset();
            selectedMethod.value = null;
            activeTab.value = 'history';
        },
        onError: (err) => {
            errorMessage.value = err.error || 'Erreur lors de la soumission du dépôt.';
            showErrorModal.value = true;
        }
    });
};

const handleWithdrawSubmit = () => {
    withdrawForm.post('/wallet/withdraw', {
        onSuccess: () => {
            withdrawForm.reset();
            selectedMethod.value = null;
            activeTab.value = 'history';
        },
        onError: (err) => {
            errorMessage.value = err.error || 'Erreur lors de la soumission du retrait.';
            showErrorModal.value = true;
        }
    });
};

const getTxTypeBadgeClass = (type: string) => {
    switch (type) {
        case 'deposit':
            return 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
        case 'withdrawal':
            return 'bg-rose-500/10 text-rose-400 border border-rose-500/20';
        case 'earnings':
            return 'bg-purple-500/10 text-purple-400 border border-purple-500/20';
        default:
            return 'bg-amber-500/10 text-amber-400 border border-amber-500/20';
    }
};

const getTxTypeLabel = (type: string) => {
    switch (type) {
        case 'deposit': return 'Dépôt Réseau';
        case 'withdrawal': return 'Retrait Initié';
        case 'earnings': return 'Gains Génération';
        case 'purchase': return 'Abonnement Nœud';
        default: return type;
    }
};
// Watch to freeze scroll on modal activation
watch(showErrorModal, (newError) => {
    if (newError) {
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
    <Head title="Portefeuille Cyber" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div>
                    <h2 class="text-xl md:text-2xl font-semibold text-white">Passerelle de Paiements Sécurisés</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Alimentez votre console de calcul ou transférez vos fonds vers votre compte externe.</p>
                </div>
                <div class="flex gap-2">
                    <span class="text-xs font-semibold px-3 py-1 bg-primary/10 border border-primary/20 text-primary rounded-lg font-mono">
                        NotchPay V2 Active
                    </span>
                </div>
            </div>

            <!-- Dashboard Balance Card (Futuristic metallic style) -->
            <div class="glass relative overflow-hidden rounded-3xl p-6 border border-white/5 bg-gradient-to-br from-black/80 via-[#070b1e] to-black/90">
                <div class="absolute inset-0 bg-grid opacity-10 pointer-events-none"></div>
                <div class="absolute right-[-10%] top-[-30%] w-96 h-96 bg-primary/10 rounded-full blur-[100px] pointer-events-none"></div>
                <div class="absolute left-[-10%] bottom-[-30%] w-96 h-96 bg-secondary/5 rounded-full blur-[100px] pointer-events-none"></div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 relative z-10">
                    <!-- Available balance -->
                    <div class="border-b lg:border-b-0 lg:border-r border-white/5 pb-6 lg:pb-0 lg:pr-6 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-primary uppercase tracking-widest block">Consolidation Solde</span>
                            <h3 class="text-3xl md:text-4xl font-black text-white font-mono tracking-tight mt-2 text-glow">
                                {{ formatXAF(user?.balance || 0) }}
                            </h3>
                        </div>
                        <p class="text-[10px] text-muted-foreground mt-4 flex items-center gap-1.5">
                            <Cpu class="h-3.5 w-3.5 text-primary" :stroke-width="2.5" /> Synchronisé en direct sur la blockchain.
                        </p>
                    </div>

                    <!-- Deposit status -->
                    <div class="border-b lg:border-b-0 lg:border-r border-white/5 pb-6 lg:pb-0 lg:px-6 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-widest block">Volume Total Dépôts</span>
                            <h3 class="text-2xl font-bold text-white font-mono tracking-tight mt-2">
                                {{ formatXAF(totalDeposits) }}
                            </h3>
                        </div>
                        <span class="text-[10px] text-emerald-400 flex items-center gap-1 mt-4">
                            <ArrowUpRight class="h-3.5 w-3.5" /> Entrée d'énergie réseau validée
                        </span>
                    </div>

                    <!-- Withdrawals status -->
                    <div class="lg:pl-6 flex flex-col justify-between">
                        <div>
                            <span class="text-xs font-semibold text-rose-400 uppercase tracking-widest block">Volume Total Retraits</span>
                            <h3 class="text-2xl font-bold text-white font-mono tracking-tight mt-2">
                                {{ formatXAF(totalWithdrawals) }}
                            </h3>
                        </div>
                        <span class="text-[10px] text-rose-400 flex items-center gap-1 mt-4">
                            <ArrowDownLeft class="h-3.5 w-3.5" /> Sortie d'énergie réseau validée
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab Menu Actions -->
            <div class="flex gap-2 border-b border-white/5 pb-2">
                <button 
                    @click="activeTab = 'history'" 
                    class="py-2.5 px-5 rounded-xl text-xs font-extrabold uppercase transition-all duration-300 flex items-center gap-2 border"
                    :class="activeTab === 'history' ? 'bg-primary text-black border-primary shadow-[0_0_15px_rgba(168,85,247,0.25)]' : 'bg-transparent border-white/5 text-muted-foreground hover:text-white'"
                >
                    <Clock class="h-4 w-4" :stroke-width="2.5" />
                    Historique des Flux
                </button>
                <button 
                    @click="activeTab = 'deposit'" 
                    class="py-2.5 px-5 rounded-xl text-xs font-extrabold uppercase transition-all duration-300 flex items-center gap-2 border"
                    :class="activeTab === 'deposit' ? 'bg-emerald-500 text-black border-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.25)]' : 'bg-transparent border-white/5 text-muted-foreground hover:text-white'"
                >
                    <ArrowUpRight class="h-4 w-4" :stroke-width="2.5" />
                    Injecter des Fonds
                </button>
                <button 
                    @click="activeTab = 'withdraw'" 
                    class="py-2.5 px-5 rounded-xl text-xs font-extrabold uppercase transition-all duration-300 flex items-center gap-2 border"
                    :class="activeTab === 'withdraw' ? 'bg-rose-500 text-white border-rose-500 shadow-[0_0_15px_rgba(244,63,94,0.25)]' : 'bg-transparent border-white/5 text-muted-foreground hover:text-white'"
                >
                    <ArrowDownLeft class="h-4 w-4" :stroke-width="2.5" />
                    Retirer mes Gains
                </button>
            </div>

            <!-- Tab 1: History -->
            <div v-if="activeTab === 'history'" class="glass rounded-2xl p-6 border border-white/5">
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <Clock class="h-4.5 w-4.5 text-primary" />
                    Registre Complet des Flux Financiers
                </h3>

                <div v-if="transactions.length === 0" class="py-12 text-center text-xs text-muted-foreground">
                    Aucune transaction n'a encore été enregistrée sur ce réseau.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-left text-xs font-semibold text-muted-foreground">
                                <th class="pb-3">Référence</th>
                                <th class="pb-3">Type</th>
                                <th class="pb-3">Montant</th>
                                <th class="pb-3">Statut</th>
                                <th class="pb-3 text-right">Horodatage</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-xs text-white/80">
                            <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-white/[0.01] transition-colors">
                                <td class="py-3.5 font-mono text-muted-foreground">{{ tx.reference }}</td>
                                <td class="py-3.5">
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[9px] font-semibold uppercase tracking-wider"
                                        :class="getTxTypeBadgeClass(tx.type)"
                                    >
                                        {{ getTxTypeLabel(tx.type) }}
                                    </span>
                                </td>
                                <td class="py-3.5 font-bold font-mono" :class="parseFloat(tx.amount) >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                    {{ parseFloat(tx.amount) >= 0 ? '+' : '' }}{{ formatXAF(tx.amount) }}
                                </td>
                                <td class="py-3.5">
                                    <span 
                                        class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-widest"
                                        :class="tx.status === 'completed' ? 'text-emerald-400' : (tx.status === 'pending' ? 'text-amber-400' : 'text-rose-400')"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full bg-current animate-pulse"></span>
                                        {{ tx.status === 'completed' ? 'Validé' : (tx.status === 'pending' ? 'En Cours' : 'Refusé') }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right text-muted-foreground">
                                    {{ new Date(tx.created_at).toLocaleString('fr-FR') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: Deposit -->
            <div v-if="activeTab === 'deposit'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Method Selection -->
                <div class="lg:col-span-5 glass rounded-2xl p-6 border border-white/5">
                    <h3 class="text-sm font-bold text-white mb-4">Sélectionnez la Passerelle</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <button 
                            @click="selectMethod('mtn')"
                            class="p-4 rounded-xl border flex flex-col items-center justify-center gap-2.5 transition-all duration-300"
                            :class="selectedMethod === 'mtn' ? 'bg-primary/5 border-primary text-white shadow-[0_0_15px_rgba(168,85,247,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <Smartphone class="h-6 w-6 text-primary" :stroke-width="2.5" />
                            <span class="text-xs font-black">MTN Mobile Money</span>
                        </button>

                        <button 
                            @click="selectMethod('orange')"
                            class="p-4 rounded-xl border flex flex-col items-center justify-center gap-2.5 transition-all duration-300"
                            :class="selectedMethod === 'orange' ? 'bg-secondary/5 border-secondary text-white shadow-[0_0_15px_rgba(139,92,246,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <Smartphone class="h-6 w-6 text-secondary" :stroke-width="2.5" />
                            <span class="text-xs font-black">Orange Money</span>
                        </button>

                        <button 
                            @click="selectMethod('usdt')"
                            class="p-4 rounded-xl border flex flex-col items-center justify-center gap-2.5 transition-all duration-300"
                            :class="selectedMethod === 'usdt' ? 'bg-purple-500/5 border-purple-400 text-white shadow-[0_0_15px_rgba(168,85,247,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <Coins class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                            <span class="text-xs font-black">Crypto USDT (TRC-20)</span>
                        </button>

                        <button 
                            @click="selectMethod('notchpay')"
                            class="p-4 rounded-xl border flex flex-col items-center justify-center gap-2.5 transition-all duration-300"
                            :class="selectedMethod === 'notchpay' ? 'bg-emerald-500/5 border-emerald-500 text-white shadow-[0_0_15px_rgba(16,185,129,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <CreditCard class="h-6 w-6 text-emerald-400" :stroke-width="2.5" />
                            <span class="text-xs font-black">NotchPay Gateway</span>
                        </button>
                    </div>
                </div>

                <!-- Input Amount Form -->
                <div class="lg:col-span-7 glass rounded-2xl p-6 border border-white/5 flex flex-col justify-between">
                    <div v-if="!selectedMethod" class="py-12 text-center text-xs text-muted-foreground flex flex-col items-center justify-center gap-4">
                        <AlertTriangle class="h-10 w-10 text-primary animate-pulse" />
                        Veuillez d'abord sélectionner une passerelle de dépôt sur la gauche.
                    </div>

                    <form v-else @submit.prevent="handleDepositSubmit" class="space-y-6">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <CheckCircle2 class="h-4.5 w-4.5 text-primary" />
                            Configuration de l'alimentation via {{ selectedMethod.toUpperCase() }}
                        </h3>

                        <div class="space-y-2">
                            <label class="text-xs text-muted-foreground uppercase font-bold">Montant à alimenter (XAF)</label>
                            <input 
                                type="number" 
                                v-model="depositForm.amount"
                                required
                                min="100"
                                placeholder="Ex: 5000"
                                class="w-full bg-black/40 border border-white/10 rounded-xl p-3.5 text-white font-mono focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all"
                            />
                        </div>

                        <!-- Extra inputs depending on method -->
                        <div v-if="selectedMethod === 'mtn' || selectedMethod === 'orange'" class="space-y-2">
                            <label class="text-xs text-muted-foreground uppercase font-bold">Numéro de Mobile Money</label>
                            <input 
                                type="tel" 
                                v-model="depositForm.phone"
                                required
                                placeholder="Ex: +237690000000"
                                class="w-full bg-black/40 border border-white/10 rounded-xl p-3.5 text-white font-mono focus:border-primary outline-none transition-all"
                            />
                        </div>

                        <div v-if="selectedMethod === 'usdt'" class="space-y-4">
                            <div class="p-3.5 rounded-xl bg-purple-950/20 border border-purple-500/20 text-xs">
                                <span class="font-bold text-purple-400 block mb-1">Adresse de Dépôt TRC-20</span>
                                <code class="text-[10px] break-all text-white font-mono">TYrW7fXnB9Xm36Qsd9d43A2HjKdLnMbPqX</code>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs text-muted-foreground uppercase font-bold">Hash de la Transaction (TXID)</label>
                                <input 
                                    type="text" 
                                    v-model="depositForm.usdt_hash"
                                    required
                                    placeholder="Entrez le hash de la transaction blockchain"
                                    class="w-full bg-black/40 border border-white/10 rounded-xl p-3.5 text-white font-mono focus:border-primary outline-none transition-all"
                                />
                            </div>
                        </div>

                        <button 
                            type="submit"
                            :disabled="depositForm.processing"
                            class="w-full py-3.5 rounded-xl bg-primary text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-primary/95 transition-all duration-300"
                        >
                            {{ depositForm.processing ? 'Communication avec le nœud...' : 'Valider le dépôt (Simulé)' }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Tab 3: Withdraw -->
            <div v-if="activeTab === 'withdraw'" class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Selection withdraw method -->
                <div class="lg:col-span-5 glass rounded-2xl p-6 border border-white/5">
                    <h3 class="text-sm font-bold text-white mb-4">Canal de Destination</h3>
                    <div class="grid grid-cols-1 gap-4">
                        <button 
                            @click="selectMethod('mtn')"
                            class="p-4 rounded-xl border flex items-center justify-between gap-4 transition-all"
                            :class="selectedMethod === 'mtn' ? 'bg-primary/5 border-primary text-white shadow-[0_0_15px_rgba(168,85,247,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <span class="text-xs font-black">MTN Mobile Money</span>
                            <Smartphone class="h-6 w-6 text-primary" :stroke-width="2.5" />
                        </button>

                        <button 
                            @click="selectMethod('orange')"
                            class="p-4 rounded-xl border flex items-center justify-between gap-4 transition-all"
                            :class="selectedMethod === 'orange' ? 'bg-secondary/5 border-secondary text-white shadow-[0_0_15px_rgba(139,92,246,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <span class="text-xs font-black">Orange Money</span>
                            <Smartphone class="h-6 w-6 text-secondary" :stroke-width="2.5" />
                        </button>

                        <button 
                            @click="selectMethod('usdt')"
                            class="p-4 rounded-xl border flex items-center justify-between gap-4 transition-all"
                            :class="selectedMethod === 'usdt' ? 'bg-purple-500/5 border-purple-400 text-white shadow-[0_0_15px_rgba(168,85,247,0.15)]' : 'bg-white/[0.01] border-white/5 text-muted-foreground hover:text-white'"
                        >
                            <span class="text-xs font-black">Crypto USDT TRC-20</span>
                            <Coins class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                        </button>
                    </div>
                </div>

                <!-- Withdraw Amount Form -->
                <div class="lg:col-span-7 glass rounded-2xl p-6 border border-white/5">
                    <div v-if="!selectedMethod" class="py-12 text-center text-xs text-muted-foreground flex flex-col items-center justify-center gap-4">
                        <AlertTriangle class="h-10 w-10 text-primary animate-pulse" />
                        Veuillez d'abord sélectionner un canal de destination sur la gauche.
                    </div>

                    <form v-else @submit.prevent="handleWithdrawSubmit" class="space-y-6">
                        <h3 class="text-sm font-bold text-white flex items-center gap-2">
                            <CheckCircle2 class="h-4.5 w-4.5 text-primary" />
                            Configuration du Retrait vers {{ selectedMethod.toUpperCase() }}
                        </h3>

                        <div class="space-y-2">
                            <label class="text-xs text-muted-foreground uppercase font-bold">Montant à débiter (XAF)</label>
                            <input 
                                type="number" 
                                v-model="withdrawForm.amount"
                                required
                                min="500"
                                placeholder="Min: 500 XAF"
                                class="w-full bg-black/40 border border-white/10 rounded-xl p-3.5 text-white font-mono focus:border-primary outline-none transition-all"
                            />
                        </div>

                        <!-- Extra inputs depending on method -->
                        <div v-if="selectedMethod === 'mtn' || selectedMethod === 'orange'" class="space-y-2">
                            <label class="text-xs text-muted-foreground uppercase font-bold">Numéro Mobile Money Réceptionnaire</label>
                            <input 
                                type="tel" 
                                v-model="withdrawForm.phone"
                                required
                                placeholder="Ex: +237690000000"
                                class="w-full bg-black/40 border border-white/10 rounded-xl p-3.5 text-white font-mono focus:border-primary outline-none transition-all"
                            />
                        </div>

                        <div v-if="selectedMethod === 'usdt'" class="space-y-2">
                            <label class="text-xs text-muted-foreground uppercase font-bold">Adresse USDT TRC-20 Destinataire</label>
                            <input 
                                type="text" 
                                v-model="withdrawForm.wallet_address"
                                required
                                placeholder="Ex: T..."
                                class="w-full bg-black/40 border border-white/10 rounded-xl p-3.5 text-white font-mono focus:border-primary outline-none transition-all"
                            />
                        </div>

                        <button 
                            type="submit"
                            :disabled="withdrawForm.processing"
                            class="w-full py-3.5 rounded-xl bg-primary text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-primary/95 transition-all duration-300"
                        >
                            {{ withdrawForm.processing ? 'Transfert réseau encours...' : 'Soumettre le retrait' }}
                        </button>
                    </form>
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
