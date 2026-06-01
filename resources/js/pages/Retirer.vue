<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    ArrowLeft,
    History,
    Lock,
    Eye,
    EyeOff,
    Check,
    ChevronRight,
    Smartphone,
    Globe,
    X,
    AlertCircle
} from 'lucide-vue-next';
import { t, currentLocale, toggleLocale } from '@/utils/trans';

const page = usePage();
const user = computed(() => page.props.auth.user);

const showSuccessCard = ref(false);
const successMessage = ref('');
const showErrorCard = ref(false);
const errorMessage = ref('');

const props = defineProps<{
    withdrawals: Array<{
        id: number;
        amount: number;
        status: string;
        reference: string;
        created_at: string;
    }>;
    withdrawalMethods: Array<{
        id: number;
        operator: string;
        full_name: string;
        phone: string;
        is_default: boolean;
    }>;
    hasWithdrawalPassword: boolean;
    hasInvested: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('Tableau de Bord', 'Dashboard'), href: '/dashboard' },
    { title: t('Retirer', 'Withdraw'), href: '/retirer' },
];

const selectedMethod = ref<any | null>(
    props.withdrawalMethods.find(m => m.is_default) || props.withdrawalMethods[0] || null
);

const showMethodSelector = ref(false);
const showPassword = ref(false);
const showHistory = ref(false);

const fixedAmounts = [1000, 5000, 15000, 50000, 150000, 500000, 1500000, 5000000];
const selectedAmount = ref<number | null>(null);

const withdrawForm = useForm({
    amount: '',
    method: '',
    phone: '',
    wallet_address: '',
    withdrawal_password: ''
});

const selectAmount = (amount: number) => {
    selectedAmount.value = amount;
    withdrawForm.amount = String(amount);
};

const selectMethod = (method: any) => {
    selectedMethod.value = method;
    withdrawForm.method = method.operator;
    withdrawForm.phone = method.phone;
    showMethodSelector.value = false;
};

const showSetPasswordModal = ref(false);
const pinForm = useForm({
    withdrawal_password: '',
    withdrawal_password_confirmation: '',
});

const handleSavePinAndWithdraw = () => {
    pinForm.post('/settings/withdrawal-password', {
        onSuccess: () => {
            showSetPasswordModal.value = false;
            // Transfer the defined password into the withdrawal form
            withdrawForm.withdrawal_password = pinForm.withdrawal_password;
            // Complete the withdrawal submit!
            setTimeout(() => {
                handleWithdrawSubmit();
            }, 300);
        },
        onError: (err: any) => {
            errorMessage.value = err.withdrawal_password || t('Veuillez entrer un code PIN valide.', 'Please enter a valid PIN code.');
            showErrorCard.value = true;
        }
    });
};

watch([selectedMethod, selectedAmount], ([newMethod, newAmount]) => {
    if (newMethod && newAmount && !props.hasWithdrawalPassword) {
        showSetPasswordModal.value = true;
    }
});

const handleWithdrawSubmit = () => {
    if (!selectedMethod.value) {
        errorMessage.value = t('Veuillez configurer et sélectionner un numéro de retrait.', 'Please configure and select a withdrawal number.');
        showErrorCard.value = true;
        return;
    }
    
    if (!props.hasWithdrawalPassword && !withdrawForm.withdrawal_password) {
        showSetPasswordModal.value = true;
        return;
    }
    
    withdrawForm.method = selectedMethod.value.operator;
    withdrawForm.phone = selectedMethod.value.phone;

    withdrawForm.post('/wallet/withdraw', {
        onSuccess: () => {
            withdrawForm.reset('withdrawal_password');
            selectedAmount.value = null;
            successMessage.value = t('Votre demande de retrait a été soumise avec succès.', 'Your withdrawal request has been submitted successfully.');
            showSuccessCard.value = true;
        },
        onError: (err) => {
            errorMessage.value = err.error || t('Erreur lors de la soumission.', 'Error during submission.');
            showErrorCard.value = true;
        }
    });
};

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const formatCompact = (value: number) => {
    return new Intl.NumberFormat('fr-FR').format(value);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

const getStatusColor = (status: string) => {
    switch(status) {
        case 'completed': return 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20';
        case 'pending': return 'text-yellow-400 bg-yellow-500/10 border-yellow-500/20';
        case 'rejected': return 'text-rose-400 bg-rose-500/10 border-rose-500/20';
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

// Watch to freeze scroll on modal activation
watch([showSuccessCard, showErrorCard, showSetPasswordModal], ([newSuccess, newError, newPassword]) => {
    if (newSuccess || newError || newPassword) {
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
    <Head :title="t('Retirer', 'Withdraw')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-4 pt-3 pb-24 text-white">

            <!-- HEADER (Top Navbar match) -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-[#0c0f1d] p-4 rounded-2xl border border-cyan-500/10 shadow-lg">
                <Link 
                    href="/dashboard"
                    class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black transition-colors"
                >
                    <ArrowLeft class="h-4.5 w-4.5" :stroke-width="2.5" />
                </Link>

                <h2 class="text-base font-black text-white uppercase tracking-wider">
                    {{ t('Retirer', 'Withdraw') }}
                </h2>

                <div class="flex items-center gap-2">
                    <!-- Language selector -->
                    <div 
                        @click="toggleLocale"
                        class="border border-white/10 bg-white/5 px-2 py-0.5 rounded-lg text-[9px] font-bold tracking-wide flex items-center gap-1 hover:border-cyan-400/50 transition-colors cursor-pointer uppercase select-none mr-1"
                    >
                        <Globe class="h-3 w-3 text-cyan-400" :stroke-width="2.5" />
                        <span>{{ currentLocale }}</span>
                    </div>

                    <button 
                        @click="showHistory = !showHistory"
                        class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black transition-colors"
                        :title="t('Historique', 'History')"
                    >
                        <History class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </button>
                </div>
            </div>

            <!-- WITHDRAWAL HISTORY DRAWER / VIEW OVERLAY -->
            <div v-if="showHistory" class="bg-gradient-to-b from-[#060b13] to-[#02050c] border border-cyan-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm animate-fadeIn duration-300">
                <div class="flex items-center justify-between mb-4 border-b border-cyan-500/10 pb-3">
                    <h3 class="text-xs font-black uppercase tracking-wider flex items-center gap-2 text-cyan-400">
                        <History class="h-4.5 w-4.5" :stroke-width="2.5" />
                        {{ t('Historique des retraits', 'Withdrawals History') }}
                    </h3>
                    <button @click="showHistory = false" class="text-xs font-bold text-slate-400 hover:text-white uppercase">
                        {{ t('Fermer', 'Close') }}
                    </button>
                </div>

                <div v-if="!withdrawals || withdrawals.length === 0" class="py-8 text-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                    {{ t('Aucun retrait effectué', 'No withdrawals made') }}
                </div>

                <div class="space-y-3 max-h-64 overflow-y-auto">
                    <div 
                        v-for="wth in withdrawals" :key="wth.id"
                        class="bg-black/40 border border-white/5 rounded-xl p-3.5 flex items-center justify-between"
                    >
                        <div>
                            <div class="text-xs font-black font-mono text-cyan-400">-{{ formatXAF(Math.abs(wth.amount)) }}</div>
                            <span class="text-[9px] text-slate-500 font-mono mt-0.5 block">{{ formatDate(wth.created_at) }}</span>
                        </div>
                        <span 
                            class="text-[8px] font-black px-2 py-0.5 rounded-full border uppercase tracking-wider"
                            :class="getStatusColor(wth.status)"
                        >
                            {{ getStatusLabel(wth.status) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- CORE MOCKUP SCREEN: RETIRER -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0c0f1d]/90 to-[#070b14]/90 border border-cyan-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-md flex flex-col gap-4">
                
                <!-- SELECT MOBILE NUMBER (Box 1 match) -->
                <div class="flex flex-col gap-1.5">
                    <button 
                        type="button"
                        @click="showMethodSelector = !showMethodSelector"
                        class="w-full bg-[#121625]/60 hover:bg-[#161b2d] border border-cyan-500/15 rounded-xl p-4 flex items-center justify-between text-left transition-all"
                    >
                        <span class="text-xs font-semibold text-slate-300">
                            {{ t('Sélectionner le numéro mobile', 'Select mobile number') }}
                        </span>
                        
                        <div class="flex items-center gap-1">
                            <span v-if="selectedMethod" class="text-xs font-black text-cyan-400 font-mono uppercase">
                                {{ selectedMethod.operator === 'mtn' ? 'MTN' : 'ORANGE' }} {{ selectedMethod.phone }}
                            </span>
                            <span v-else class="text-xs font-black text-rose-400 uppercase">
                                {{ t('Aucun numéro', 'No number') }}
                            </span>
                            <ChevronRight class="h-4.5 w-4.5 text-cyan-400 shrink-0" :stroke-width="2.5" />
                        </div>
                    </button>
                    
                    <!-- SELECTOR LIST OVERLAY -->
                    <div v-if="showMethodSelector" class="bg-black/60 border border-cyan-500/10 rounded-xl p-3.5 space-y-2 mt-1 animate-fadeIn duration-200">
                        <p class="text-[9px] text-slate-400 font-black uppercase tracking-wider mb-2">
                            {{ t('Vos numéros configurés', 'Your configured numbers') }}
                        </p>
                        <div v-if="withdrawalMethods.length === 0" class="text-center py-4">
                            <Link 
                                href="/settings/mobile-numbers"
                                class="text-[10px] text-cyan-400 font-black uppercase underline"
                            >
                                + {{ t('Configurer un numéro', 'Configure a number') }}
                            </Link>
                        </div>
                        <button 
                            v-for="m in withdrawalMethods" :key="m.id"
                            type="button"
                            @click="selectMethod(m)"
                            class="w-full p-3 rounded-lg border text-left flex items-center justify-between transition-colors"
                            :class="selectedMethod?.id === m.id ? 'bg-cyan-500/10 border-cyan-400 text-white' : 'bg-[#101424] border-white/5 text-slate-400 hover:text-white'"
                        >
                            <span class="text-xs font-mono font-black uppercase">
                                {{ m.operator === 'mtn' ? 'MTN' : 'Orange' }} ({{ m.phone }})
                            </span>
                            <span class="text-[9px] font-mono text-slate-500">{{ m.full_name }}</span>
                        </button>
                    </div>
                </div>

                <!-- MONTANT DISPONIBLE (Box 2 match) -->
                <div class="bg-[#121625]/60 border border-cyan-500/15 rounded-xl p-4 flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-300">
                        {{ t('Montant disponible', 'Available amount') }}
                    </span>
                    <span class="text-xs font-black text-cyan-400 font-mono">
                        {{ formatXAF(user?.balance || 0) }}
                    </span>
                </div>

                <!-- SELECT RETRAIT AMOUNT GRID (Selection match) -->
                <div class="border border-cyan-500/15 bg-black/30 rounded-2xl p-4.5 flex flex-col gap-4">
                    <span class="text-xs font-semibold text-slate-300 block">
                        {{ t('Sélectionner le montant de retrait', 'Select withdrawal amount') }}
                    </span>

                    <div class="grid grid-cols-4 gap-2">
                        <button 
                            v-for="amount in fixedAmounts" :key="amount"
                            type="button"
                            @click="selectAmount(amount)"
                            class="py-3 px-1 rounded-xl border text-center transition-all duration-300"
                            :class="selectedAmount === amount 
                                ? 'bg-cyan-500/15 border-cyan-400 text-cyan-400 shadow-[0_0_12px_rgba(6,182,212,0.2)]' 
                                : 'bg-[#121524]/60 border-[#1a1f33] text-slate-400 hover:text-white'"
                        >
                            <span class="text-[11px] font-black font-mono block">{{ formatCompact(amount) }}</span>
                        </button>
                    </div>

                    <!-- PASSWORD INPUT (Mot de passe de fonds match) -->
                    <div class="flex flex-col gap-1.5 mt-2">
                        <div class="relative">
                            <Lock class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-cyan-500/50" :stroke-width="2.5" />
                            <input 
                                :type="showPassword ? 'text' : 'password'"
                                v-model="withdrawForm.withdrawal_password"
                                required
                                :placeholder="t('Veuillez entrer le mot de passe de fonds', 'Please enter your funds password')"
                                class="w-full bg-[#121625]/60 border border-cyan-500/15 rounded-xl py-3.5 pl-11 pr-11 text-white font-sans text-xs focus:border-cyan-400 outline-none transition-all placeholder:text-white/30"
                            />
                            <button 
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                            >
                                <component :is="showPassword ? EyeOff : Eye" class="h-4.5 w-4.5" :stroke-width="2.5" />
                            </button>
                        </div>
                    </div>

                    <!-- CONFIRM BUTTON (Confirm withdraw match) -->
                    <button 
                        type="button"
                        @click="handleWithdrawSubmit"
                        :disabled="withdrawForm.processing"
                        class="w-full py-3.5 mt-2 rounded-xl bg-gradient-to-r from-cyan-600 to-purple-600 hover:from-cyan-500 hover:to-purple-500 text-white font-extrabold text-xs uppercase tracking-widest shadow-[0_0_15px_rgba(6,182,212,0.3)] transition-all duration-300 disabled:opacity-50"
                    >
                        {{ withdrawForm.processing ? t('Traitement...', 'Processing...') : t('Confirmer le retrait', 'Confirm withdrawal') }}
                    </button>
                </div>

                <!-- LEGAL FOOTER / INFO NOTES (Screenshot footnote match) -->
                <div class="border-t border-purple-500/10 pt-4.5 text-[9px] text-slate-400 leading-relaxed font-mono">
                    <p class="font-bold text-white mb-2">
                        {{ t('Conseils de retrait :', 'Withdrawal Guidelines:') }}
                    </p>
                    <p>• {{ t('Délai de traitement des retraits : 1 à 3 jours ouvrables (sous 72 heures).', 'Withdrawal Processing Time: 1-3 business days (within 72 hours).') }}</p>
                    <p>• {{ t('Chaque transaction engendre des frais de traitement administratifs de 6%.', 'Each transaction incurs a 6% handling fee.') }}</p>
                    <p>• {{ t('Les frais de gestion de 6% seront automatiquement prélevés lors du transfert.', 'A 6% management fee will be deducted for each withdrawal.') }}</p>
                    <p>• {{ t('Les frais de gestion se rapportent aux coûts opérationnels des serveurs technologiques.', 'The management fee refers to the technology server operational costs.') }}</p>
                </div>

            <!-- SET RETRAIT PIN MODAL (Inline Propose) -->
            <Teleport to="body">
                <div v-if="showSetPasswordModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                    <div class="bg-[#0c051a] border-2 border-purple-500/30 w-full max-w-md rounded-3xl p-6 relative overflow-hidden shadow-2xl glow-border">
                        <div class="h-14 w-14 rounded-full bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-5 mx-auto animate-bounce shadow-md">
                            <Lock class="h-7 w-7" :stroke-width="2.5" />
                        </div>
                        <h3 class="text-sm font-black text-white text-center uppercase tracking-wider font-mono">Mot de Passe de Retrait</h3>
                        <p class="text-xs text-slate-400 text-center mt-2.5 leading-relaxed font-sans mb-5">
                            {{ t("Vous n'avez pas encore défini de Code PIN secret. Pour valider ce retrait, veuillez configurer un code PIN de fonds (4 à 12 caractères) ci-dessous.", "You have not set a secret PIN code yet. To approve this withdrawal, please configure a funds PIN (4 to 12 characters) below.") }}
                        </p>

                        <form @submit.prevent="handleSavePinAndWithdraw" class="space-y-4">
                            <!-- New PIN -->
                            <div class="flex flex-col gap-1.5 text-left text-white">
                                <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                                    {{ t('Nouveau Code PIN (4-12 caractères)', 'New PIN Code (4-12 characters)') }}
                                </label>
                                <div class="relative">
                                    <Lock class="absolute left-3.5 top-3.5 h-4 w-4 text-purple-500/50" :stroke-width="2.5" />
                                    <input 
                                        type="password" 
                                        v-model="pinForm.withdrawal_password"
                                        required
                                        placeholder="••••"
                                        maxlength="12"
                                        class="w-full bg-black/50 border border-purple-500/20 rounded-xl py-3.5 pl-11 pr-4 text-white font-mono text-center tracking-widest text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/20"
                                    />
                                </div>
                            </div>

                            <!-- Confirm PIN -->
                            <div class="flex flex-col gap-1.5 text-left text-white">
                                <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                                    {{ t('Confirmer le Code PIN', 'Confirm PIN Code') }}
                                </label>
                                <div class="relative">
                                    <Lock class="absolute left-3.5 top-3.5 h-4 w-4 text-purple-500/50" :stroke-width="2.5" />
                                    <input 
                                        type="password" 
                                        v-model="pinForm.withdrawal_password_confirmation"
                                        required
                                        placeholder="••••"
                                        maxlength="12"
                                        class="w-full bg-black/50 border border-purple-500/20 rounded-xl py-3.5 pl-11 pr-4 text-white font-mono text-center tracking-widest text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/20"
                                    />
                                </div>
                            </div>

                            <div class="flex gap-3 mt-6">
                                <button 
                                    type="button"
                                    @click="showSetPasswordModal = false" 
                                    class="flex-1 py-3 rounded-xl border border-white/10 text-white font-semibold text-xs hover:bg-white/5 transition-all duration-300"
                                >
                                    {{ t('Annuler', 'Cancel') }}
                                </button>
                                <button 
                                    type="submit"
                                    :disabled="pinForm.processing"
                                    class="flex-1 py-3 rounded-xl bg-purple-500 text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300"
                                >
                                    {{ pinForm.processing ? t('Sauvegarde...', 'Saving...') : t('Enregistrer & Confirmer', 'Save & Confirm') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Teleport>

            <!-- SUCCESS CARD MODAL -->
            <Teleport to="body">
                <div v-if="showSuccessCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                    <div class="bg-[#0c051a] border-2 border-purple-500/30 w-full max-w-md rounded-3xl p-6 relative overflow-hidden shadow-2xl glow-border text-center">
                        <div class="h-14 w-14 rounded-full bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-5 mx-auto animate-bounce shadow-md">
                            <Check class="h-7 w-7" :stroke-width="3" />
                        </div>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider font-mono">Retrait Envoyé</h3>
                        <p class="text-xs text-slate-400 mt-2.5 leading-relaxed bg-purple-950/20 border border-purple-500/10 p-3.5 rounded-xl font-mono">
                            {{ successMessage }}
                        </p>
                        <button @click="showSuccessCard = false" class="w-full mt-6 py-3.5 rounded-2xl bg-purple-500 text-black font-extrabold uppercase tracking-widest text-xs shadow-lg">
                            Consolider
                        </button>
                    </div>
                </div>
            </Teleport>

            <!-- ERROR CARD MODAL -->
            <Teleport to="body">
                <div v-if="showErrorCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn" @touchmove.prevent>
                    <div class="bg-[#0a0514] border-2 border-rose-500/30 w-full max-w-md rounded-3xl p-6 relative overflow-hidden shadow-2xl glow-border text-center">
                        <div class="h-14 w-14 rounded-full bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 mb-5 mx-auto animate-pulse shadow-md">
                            <AlertCircle class="h-7 w-7" :stroke-width="3" />
                        </div>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider font-mono">Alerte Système</h3>
                        <p class="text-xs text-rose-400 mt-2.5 leading-relaxed bg-rose-950/20 border border-rose-500/20 p-3.5 rounded-xl font-mono">
                            {{ errorMessage }}
                        </p>
                        <button @click="showErrorCard = false" class="w-full mt-6 py-3.5 rounded-2xl bg-white/5 border border-white/10 text-gray-300 font-bold uppercase tracking-wider text-xs hover:bg-white/10">
                            Fermer
                        </button>
                    </div>
                </div>
            </Teleport>

            </div>

        </div>
    </AppLayout>
</template>
