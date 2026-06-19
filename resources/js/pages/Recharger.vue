<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    ArrowLeft,
    Check,
    Smartphone,
    Globe,
    AlertCircle,
    Building2,
    Coins,
    X,
    History
} from 'lucide-vue-next';
import { t, currentLocale, toggleLocale } from '@/utils/trans';

const page = usePage();
const user = computed(() => page.props.auth.user);

const showSuccessCard = ref(false);
const successMessage = ref('');
const showErrorCard = ref(false);
const errorMessage = ref('');

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
    { title: t('Recharger', 'Recharge'), href: '/recharger' },
];

const showHistory = ref(false);

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

// Payment channels from mockup screenshot
const paymentChannels = [
    { id: 'mtn_momo_1', label: 'Fast Track 1', sub: 'XAF', operator: 'mtn', icon: Building2 },
    { id: 'orange_money_2', label: 'Fast Track 2', sub: 'XAF', operator: 'orange', icon: Building2 },
    { id: 'mtn_momo_3', label: 'Fast Track 3', sub: 'XAF', operator: 'mtn', icon: Smartphone },
];

const selectedChannel = ref(paymentChannels[0]); // Fast Track 1 selected by default

const depositForm = useForm({
    amount: '',
    method: selectedChannel.value.operator,
    phone: '',
    usdt_hash: ''
});

const selectChannel = (channel: any) => {
    selectedChannel.value = channel;
    depositForm.method = channel.operator;
};

const handleDepositSubmit = () => {
    // If MoMo, prompt for phone number to keep it simple and clean
    if (depositForm.method === 'mtn' || depositForm.method === 'orange') {
        if (!depositForm.phone) {
            const phoneVal = prompt(t('Entrez votre numéro Mobile Money de débit :', 'Enter your debit Mobile Money number:'));

            if (!phoneVal) return;
            depositForm.phone = phoneVal;
        }
    }

    depositForm.post('/wallet/deposit', {
        onSuccess: () => {
            depositForm.reset();
            successMessage.value = t('Votre demande de recharge a été soumise pour validation.', 'Your recharge request has been submitted for validation.');
            showSuccessCard.value = true;
        },
        onError: (err) => {
            errorMessage.value = err.error || t('Erreur lors du dépôt.', 'Error during deposit.');
            showErrorCard.value = true;
        }
    });
};

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const { containerRef } = useRevealAnimation();

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
    <Head :title="t('Recharger', 'Recharge')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-4 pt-3 pb-24 text-white">

            <!-- HEADER (Top Navbar match) -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-[#0c0f1d] p-4 rounded-2xl border border-purple-500/10 shadow-lg relative">
                <Link
                    href="/dashboard"
                    class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black transition-colors"
                >
                    <ArrowLeft class="h-4.5 w-4.5" :stroke-width="2.5" />
                </Link>

                <h2 class="text-base font-black text-white uppercase tracking-wider">
                    {{ t('Recharger', 'Recharge') }}
                </h2>

                <div class="flex items-center gap-2">
                    <div
                        @click="toggleLocale"
                        class="border border-white/10 bg-white/5 px-2.5 py-1 rounded-xl text-[9px] font-bold tracking-wide flex items-center gap-1.5 hover:border-purple-400/50 transition-colors cursor-pointer uppercase select-none mr-1"
                    >
                        <Globe class="h-3.5 w-3.5 text-purple-400" :stroke-width="2.5" />
                        <span>{{ currentLocale }}</span>
                    </div>

                    <Link
                        href="/recharges"
                        class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black transition-colors"
                        :title="t('Historique', 'History')"
                    >
                        <History class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </Link>
                </div>
            </div>

            <!-- PAYMENT CHANNELS GRID (Mockup List match) -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0c0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-md">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block mb-4">
                    {{ t('Méthode de paiement', 'Payment Method') }}
                </span>

                <div class="space-y-3">
                    <button
                        v-for="channel in paymentChannels" :key="channel.id"
                        type="button"
                        @click="selectChannel(channel)"
                        class="w-full p-4 rounded-2xl border flex items-center justify-between transition-all duration-300"
                        :class="selectedChannel.id === channel.id
                            ? 'bg-purple-500/10 border-purple-400 text-white shadow-[0_0_15px_rgba(168,85,247,0.15)]'
                            : 'bg-black/30 border-white/5 text-slate-400 hover:text-white'"
                    >
                        <div class="flex items-center gap-3">
                            <!-- Radio Indicator Circle -->
                            <div
                                class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-colors shrink-0"
                                :class="selectedChannel.id === channel.id ? 'border-purple-400 bg-purple-400' : 'border-white/20'"
                            >
                                <span v-if="selectedChannel.id === channel.id" class="w-1.5 h-1.5 rounded-full bg-[#070b14]"></span>
                            </div>

                            <!-- Icon inside grey capsule -->
                            <div class="w-10 h-10 rounded-xl bg-black/40 border border-white/5 flex items-center justify-center text-purple-400">
                                <component :is="channel.icon" class="h-5 w-5" :stroke-width="2.5" />
                            </div>

                            <div>
                                <span class="text-xs font-black uppercase tracking-wider block text-white">{{ channel.label }}</span>
                                <span class="text-[9px] font-mono text-slate-500 block leading-none mt-0.5">{{ channel.sub }}</span>
                            </div>
                        </div>

                        <!-- Checkmark icon on the right side if active -->
                        <Check
                            v-if="selectedChannel.id === channel.id"
                            class="h-4.5 w-4.5 text-purple-400 shrink-0"
                            :stroke-width="2.5"
                        />
                    </button>
                </div>
            </div>

            <!-- INPUT AMOUNT (Mockup Enter input match) -->
            <div data-animate="fade-up" data-delay="150" class="bg-gradient-to-b from-[#0c0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-md flex flex-col gap-4">

                <form @submit.prevent="handleDepositSubmit" class="space-y-4">
                    <div class="flex flex-col gap-1.5">
                        <div class="relative">
                            <!-- Prefix FCFA inside -->
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-xs font-extrabold text-purple-400 uppercase tracking-widest">FCFA</span>
                            <input
                                type="number"
                                v-model="depositForm.amount"
                                required
                                min="500"
                                max="500000000"
                                :placeholder="t('Entrer le montant de recharge', 'Enter the recharge amount')"
                                class="w-full bg-[#121625]/60 border border-purple-500/15 rounded-xl py-3.5 pl-16 pr-4 text-white font-mono text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/20"
                            />
                        </div>
                    </div>

                    <!-- Extra fields for MoMo prompt -->
                    <div v-if="selectedChannel.operator === 'mtn' || selectedChannel.operator === 'orange'" class="flex flex-col gap-1.5">
                        <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider">
                            {{ t('Numéro Mobile Money de Débit', 'Debit Mobile Money Number') }}
                        </label>
                        <input
                            type="tel"
                            v-model="depositForm.phone"
                            placeholder="Ex: 690000000"
                            class="w-full bg-[#121625]/60 border border-purple-500/15 rounded-xl py-3.5 px-4 text-white font-mono text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/20"
                        />
                    </div>



                    <button
                        type="submit"
                        :disabled="depositForm.processing"
                        class="w-full py-3.5 rounded-xl bg-purple-500 text-black font-extrabold text-xs uppercase tracking-widest shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300 disabled:opacity-50"
                    >
                        {{ depositForm.processing ? t('Traitement...', 'Processing...') : t('Confirmer la recharge', 'Confirm Recharge') }}
                    </button>
                </form>

                <!-- RECHARGE GUIDELINES (Mockup footer match) -->
                <div class="border-t border-purple-500/10 pt-4.5 text-[15px] text-slate-400 leading-relaxed font-mono">
                    <p class="font-bold text-white mb-2 uppercase tracking-wide">
                        {{ t('Conseils de recharge :', 'Recharge Guidelines:') }}
                    </p>
                    <p>• {{ t('Le montant minimum de recharge est 500.00 XAF', 'The minimum recharge amount is 500.00 XAF') }}</p>
                    <p>• {{ t('Le montant maximum de recharge est 500,000,000.00 XAF', 'The maximum recharge amount is 500,000,000.00 XAF') }}</p>
                    <p>• {{ t('Aucuns frais de traitement', 'No processing fees') }}</p>
                </div>
            </div>

            <!-- SUCCESS CARD MODAL -->
            <Teleport to="body">
                <div v-if="showSuccessCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn">
                    <div class="bg-[#0c051a] border-2 border-purple-500/30 w-full max-w-md rounded-3xl p-6 relative overflow-hidden shadow-2xl glow-border text-center">
                        <div class="h-14 w-14 rounded-full bg-purple-500/10 border border-purple-500/20 flex items-center justify-center text-purple-400 mb-5 mx-auto animate-bounce shadow-md">
                            <Check class="h-7 w-7" :stroke-width="3" />
                        </div>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider font-mono">Demande Soumise</h3>
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
                <div v-if="showErrorCard" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn">
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
    </AppLayout>
</template>
