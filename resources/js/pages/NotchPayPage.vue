<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, Copy, Globe, Clock, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

interface Transaction {
    id: number;
    amount: number;
    status: string;
    reference: string;
    payment_method: string;
    payment_phone: string;
    gateway_ref: string;
    created_at: string;
}

interface Settings {
    fapshi_orange_agent: string;
    fapshi_orange_merchant: string;
    fapshi_mtn_agent: string;
    fapshi_mtn_merchant: string;
}

const props = defineProps<{
    transaction: Transaction;
    settings: Settings;
}>();

// Language state
const locale = ref<'fr' | 'en'>('fr');

const toggleLocale = () => {
    locale.value = locale.value === 'fr' ? 'en' : 'fr';
};

// Translations lookup
const trans = {
    fr: {
        paymentDetails: "Détails du paiement",
        yourPhone: "Votre numéro de paiement :",
        amountToPay: "Montant à payer :",
        operator: "Opérateur :",
        copier: "Copier",
        copie: "Copié !",
        ussdInstructions: "Si la notification de paiement (Push) n'apparaît pas sur votre téléphone, veuillez composer :",
        composez: "Composez",
        enterSecret: "2. Choisissez l'option de validation et entrez votre code PIN.",
        btnResult: "OBTENIR LE RÉSULTAT",
        checking: "Vérification...",
        notice: "Une fois que vous avez validé la transaction sur votre téléphone, cliquez sur le bouton ci-dessous pour obtenir le résultat.",
        title: "Paiement Direct Notch Pay",
        back: "Retour",
        success: "Dépôt validé avec succès !",
        failed: "Le dépôt a échoué ou a expiré.",
        pending: "Paiement toujours en attente de validation sur votre téléphone.",
        pushSent: "Un message push a été envoyé à votre téléphone.",
        confirmWallet: "Veuillez remplir le numéro correct de portefeuille électronique pour confirmer votre paiement.",
        operatorLabel: "Opérateur",
        mtnLabel: "MoMo (MTN)",
        orangeLabel: "Orange Cash (Orange)",
        yourPayPhone: "Votre numéro de paiement",
        numberMustMatch: "Le numéro doit être identique au numéro de paiement.",
        btnConfirmSubmit: "CONFIRMER, PUIS SOUMETTRE"
    },
    en: {
        paymentDetails: "Payment Details",
        yourPhone: "Your payment phone number:",
        amountToPay: "Amount to pay:",
        operator: "Operator:",
        copier: "Copy",
        copie: "Copied!",
        ussdInstructions: "If the payment notification (Push) does not appear on your phone, please dial:",
        composez: "Dial",
        enterSecret: "2. Select the validation option and enter your PIN code.",
        btnResult: "GET THE RESULT",
        checking: "Checking...",
        notice: "Once you have approved the transaction on your phone, click the button below to get the result.",
        title: "Notch Pay Direct Payment",
        back: "Back",
        success: "Deposit approved successfully!",
        failed: "Deposit failed or expired.",
        pending: "Payment still pending validation on your phone.",
        pushSent: "A push message has been sent to your phone.",
        confirmWallet: "Please fill in the correct e-wallet number to confirm your payment.",
        operatorLabel: "Operator",
        mtnLabel: "MoMo (MTN)",
        orangeLabel: "Orange Cash (Orange)",
        yourPayPhone: "Your payment number",
        numberMustMatch: "The number must be identical to the payment number.",
        btnConfirmSubmit: "CONFIRM, THEN SUBMIT"
    }
};

const t = (key: keyof typeof trans.fr) => {
    return trans[locale.value][key];
};

// Countdown Timer logic
const totalSeconds = ref(1800); // 30 minutes
const timerString = computed(() => {
    const minutes = Math.floor(totalSeconds.value / 60);
    const seconds = totalSeconds.value % 60;
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

let timerInterval: any = null;

const startTimer = () => {
    const storageKey = `notchpay_timer_${props.transaction.reference}`;
    const savedStartTime = localStorage.getItem(storageKey);
    const now = Math.floor(Date.now() / 1000);

    if (savedStartTime) {
        const elapsed = now - parseInt(savedStartTime, 10);
        if (elapsed >= 1800) {
            totalSeconds.value = 0;
        } else {
            totalSeconds.value = 1800 - elapsed;
        }
    } else {
        localStorage.setItem(storageKey, now.toString());
        totalSeconds.value = 1800;
    }

    timerInterval = setInterval(() => {
        if (totalSeconds.value > 0) {
            totalSeconds.value--;
        } else {
            clearInterval(timerInterval);
        }
    }, 1000);
};

// Copy states
const copiedState = ref<Record<string, boolean>>({});

const copyText = (text: string, key: string) => {
    navigator.clipboard.writeText(text).then(() => {
        copiedState.value[key] = true;
        setTimeout(() => {
            copiedState.value[key] = false;
        }, 2000);
    });
};

// Dial action
const dialCode = (code: string) => {
    window.location.href = 'tel:' + code;
};

// Local reactive copies of payment details (so we can update them on success)
const localPaymentMethod = ref(props.transaction.payment_method);
const localPaymentPhone = ref(props.transaction.payment_phone);

// Dynamic properties based on selected operator
const isOrange = computed(() => localPaymentMethod.value === 'orange');
const amount = computed(() => props.transaction.amount);

// Intermediate validation screen state
const isConfirmed = ref(false);
const confirmedPhone = ref('');
const selectedOperator = ref<'mtn' | 'orange' | ''>(props.transaction.payment_method as any || '');
const isSubmittingCharge = ref(false);
const chargeError = ref('');



const isPhoneValid = computed(() => {
    const cleanConfirmed = confirmedPhone.value.replace(/\D/g, '');
    let normConfirmed = cleanConfirmed;
    if (normConfirmed.startsWith('237')) {
        normConfirmed = normConfirmed.substring(3);
    }

    const cleanOriginal = localPaymentPhone.value.replace(/\D/g, '');
    let normOriginal = cleanOriginal;
    if (normOriginal.startsWith('237')) {
        normOriginal = normOriginal.substring(3);
    }

    return normConfirmed === normOriginal && selectedOperator.value !== '';
});

const submitCharge = async () => {
    if (!isPhoneValid.value || isSubmittingCharge.value) return;
    isSubmittingCharge.value = true;
    chargeError.value = '';

    try {
        const response = await axios.post(`/camerpayment/charge/${props.transaction.reference}`, {
            method: selectedOperator.value,
            phone: confirmedPhone.value
        });

        if (response.data && response.data.status === 'success') {
            localPaymentMethod.value = selectedOperator.value;
            localPaymentPhone.value = confirmedPhone.value;
            isConfirmed.value = true;
        } else {
            chargeError.value = response.data?.message || "Échec de la soumission du paiement.";
        }
    } catch (e: any) {
        chargeError.value = e.response?.data?.message || e.message || "Erreur réseau. Impossible de contacter le serveur.";
    } finally {
        isSubmittingCharge.value = false;
    }
};

// USSD manual approval dial string formation
const ussdString = computed(() => {
    return isOrange.value ? '#150*50#' : '*126#';
});

// Check Status polling
const isChecking = ref(false);
const statusMessage = ref('');
const statusType = ref<'success' | 'error' | 'info' | ''>('');

const checkStatus = async () => {
    if (isChecking.value) return;
    isChecking.value = true;
    statusMessage.value = '';
    statusType.value = '';

    try {
        const response = await axios.post(`/camerpayment/check-status/${props.transaction.reference}`);
        const data = response.data;

        if (data.status === 'completed') {
            statusMessage.value = t('success');
            statusType.value = 'success';
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 2000);
        } else if (data.status === 'rejected') {
            statusMessage.value = t('failed');
            statusType.value = 'error';
        } else {
            statusMessage.value = t('pending');
            statusType.value = 'info';
        }
    } catch (e: any) {
        statusMessage.value = e.response?.data?.message || e.message || "Erreur de vérification.";
        statusType.value = 'error';
    } finally {
        isChecking.value = false;
    }
};

onMounted(() => {
    startTimer();
});

onUnmounted(() => {
    if (timerInterval) clearInterval(timerInterval);
});
</script>

<template>
    <Head :title="t('title')" />
    
    <div class="min-h-screen bg-[#f3f4f6] text-[#333333] flex flex-col font-sans">
        
        <!-- Header -->
        <div class="bg-[#002f9c] text-white px-4 py-6 flex items-center justify-between shadow-md">
            <Link href="/recharger" class="flex items-center gap-1 text-white/80 hover:text-white transition-colors">
                <ArrowLeft class="h-5 w-5" />
                <span class="text-sm font-semibold">{{ t('back') }}</span>
            </Link>
            
            <!-- Timer countdown -->
            <div class="bg-[#001e68]/50 px-4 py-1.5 rounded-full flex items-center gap-1.5 font-mono text-xl font-bold tracking-wider">
                <Clock class="h-4.5 w-4.5 text-white/70" />
                <span>{{ timerString }}</span>
            </div>

            <!-- Language selector toggle -->
            <button @click="toggleLocale" class="border border-white/20 bg-white/10 px-3 py-1 rounded-lg text-xs font-bold tracking-wide flex items-center gap-1 hover:bg-white/20 transition-colors uppercase">
                <Globe class="h-3.5 w-3.5" />
                <span>{{ locale === 'fr' ? 'ENGLISH ⇆' : 'FRANÇAIS ⇆' }}</span>
            </button>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 max-w-md w-full mx-auto p-4 flex flex-col gap-5">
            
            <template v-if="!isConfirmed">
                <!-- Card 1: Veuillez remplir le numéro correct... -->
                <div class="bg-white rounded-[24px] p-6 shadow-sm border border-gray-100 text-center flex flex-col justify-center">
                    <p class="text-sm font-bold text-gray-800 leading-relaxed">
                        {{ t('confirmWallet') }}
                    </p>
                </div>

                <!-- Card 2: Opérateur + Votre numéro de paiement -->
                <div class="bg-white rounded-[24px] p-6 shadow-sm border border-gray-100 flex flex-col gap-4">
                    <!-- Heading -->
                    <div>
                        <span class="text-sm font-bold text-gray-800 uppercase tracking-wide">
                            {{ t('operatorLabel') }}
                        </span>
                    </div>

                    <!-- Operator selection rows (matching screenshot cards) -->
                    <div class="flex flex-col gap-3">
                        <!-- MTN MoMo Option -->
                        <div
                            @click="selectedOperator = 'mtn'"
                            class="rounded-2xl p-4 flex items-center justify-between border-2 transition-all duration-200 cursor-pointer"
                            :class="selectedOperator === 'mtn' ? 'border-[#6366f1] bg-[#6366f1]/5' : 'border-gray-100 bg-[#f9fafb]'"
                        >
                            <div class="flex items-center gap-3">
                                <!-- MoMo MTN SVG Logo -->
                                <svg viewBox="0 0 100 100" class="w-10 h-10 rounded-xl shadow-sm shrink-0">
                                    <rect width="100" height="100" rx="20" fill="#FFCC00"/>
                                    <circle cx="50" cy="50" r="32" fill="#003F87"/>
                                    <ellipse cx="50" cy="50" rx="20" ry="12" fill="#FFCC00" />
                                    <text x="50" y="54" font-family="'Helvetica Neue', Arial, sans-serif" font-weight="900" font-size="10" fill="#000000" text-anchor="middle">MTN</text>
                                </svg>
                                <span class="text-sm font-bold text-gray-800">{{ t('mtnLabel') }}</span>
                            </div>
                            <!-- Radio indicator circle -->
                            <div
                                class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                                :class="selectedOperator === 'mtn' ? 'border-[#6366f1] bg-[#6366f1]' : 'border-gray-300'"
                            >
                                <div v-if="selectedOperator === 'mtn'" class="w-2.5 h-2.5 rounded-full bg-white"></div>
                            </div>
                        </div>

                        <!-- Orange Cash Option -->
                        <div
                            @click="selectedOperator = 'orange'"
                            class="rounded-2xl p-4 flex items-center justify-between border-2 transition-all duration-200 cursor-pointer"
                            :class="selectedOperator === 'orange' ? 'border-[#6366f1] bg-[#6366f1]/5' : 'border-gray-100 bg-[#f9fafb]'"
                        >
                            <div class="flex items-center gap-3">
                                <!-- Orange Money SVG Logo -->
                                <svg viewBox="0 0 100 100" class="w-10 h-10 rounded-xl shadow-sm shrink-0">
                                    <rect width="100" height="100" rx="20" fill="#FF6600"/>
                                    <text x="50" y="65" font-family="'Helvetica Neue', Arial, sans-serif" font-weight="900" font-size="28" fill="#FFFFFF" text-anchor="middle">orange</text>
                                </svg>
                                <span class="text-sm font-bold text-gray-800">{{ t('orangeLabel') }}</span>
                            </div>
                            <!-- Radio indicator circle -->
                            <div
                                class="w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all"
                                :class="selectedOperator === 'orange' ? 'border-[#6366f1] bg-[#6366f1]' : 'border-gray-300'"
                            >
                                <div v-if="selectedOperator === 'orange'" class="w-2.5 h-2.5 rounded-full bg-white"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Phone Input Block -->
                    <div class="flex flex-col gap-2 mt-2">
                        <label class="text-xs font-bold text-gray-700">{{ t('yourPayPhone') }}</label>
                        <input
                            type="tel"
                            v-model="confirmedPhone"
                            placeholder="Ex: 691051864"
                            class="w-full bg-[#f9fafb] border-2 rounded-2xl py-4 px-4 text-gray-800 font-mono text-sm focus:bg-white focus:outline-none transition-all"
                            :class="isPhoneValid ? 'border-[#10b981] focus:border-[#10b981]' : 'border-gray-200 focus:border-[#6366f1]'"
                        />
                        <span class="text-[11px] font-semibold text-rose-500 mt-1 block">
                            {{ t('numberMustMatch') }}
                        </span>

                        <!-- API Error display -->
                        <div v-if="chargeError" class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs font-bold rounded-xl mt-2">
                            {{ chargeError }}
                        </div>
                    </div>
                </div>

                <!-- Confirm and submit button -->
                <button
                    @click="submitCharge"
                    :disabled="!isPhoneValid || isSubmittingCharge"
                    class="w-full py-4 rounded-full text-white font-bold text-sm uppercase tracking-wider shadow-md active:scale-[0.98] transition-all flex items-center justify-center gap-2"
                    :class="isPhoneValid && !isSubmittingCharge ? 'bg-[#6366f1] hover:bg-[#4f46e5]' : 'bg-gray-400 opacity-60 cursor-not-allowed'"
                >
                    <Loader2 v-if="isSubmittingCharge" class="h-4.5 w-4.5 animate-spin" />
                    <span>{{ isSubmittingCharge ? 'Soumission...' : t('btnConfirmSubmit') }}</span>
                </button>
            </template>

            <template v-else>
                <!-- Push notification sent alert card -->
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center gap-3 text-blue-800 text-xs font-bold leading-normal">
                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                        <Loader2 class="h-4.5 w-4.5 animate-spin" />
                    </div>
                    <span>{{ t('pushSent') }}</span>
                </div>

                <!-- Payment Instruction Card -->
                <div class="bg-white rounded-[28px] p-6 shadow-sm border border-gray-100 flex flex-col gap-4">
                    <div>
                        <h3 class="text-sm font-bold text-gray-800 uppercase tracking-wide">
                            {{ t('paymentDetails') }}
                        </h3>
                    </div>

                    <!-- Operator Row -->
                    <div class="flex items-center justify-between border-b border-gray-50 pb-3">
                        <span class="text-xs text-gray-500 font-medium">{{ t('operator') }}</span>
                        <svg v-if="isOrange" viewBox="0 0 100 100" class="w-12 h-12 rounded-xl shadow-sm shrink-0">
                            <rect width="100" height="100" rx="20" fill="#FF6600"/>
                            <text x="50" y="65" font-family="'Helvetica Neue', Arial, sans-serif" font-weight="900" font-size="28" fill="#FFFFFF" text-anchor="middle">orange</text>
                        </svg>
                        <svg v-else viewBox="0 0 100 100" class="w-12 h-12 rounded-xl shadow-sm shrink-0">
                            <rect width="100" height="100" rx="20" fill="#FFCC00"/>
                            <circle cx="50" cy="50" r="32" fill="#003F87"/>
                            <ellipse cx="50" cy="50" rx="20" ry="12" fill="#FFCC00" />
                            <text x="50" y="54" font-family="'Helvetica Neue', Arial, sans-serif" font-weight="900" font-size="10" fill="#000000" text-anchor="middle">MTN</text>
                        </svg>
                    </div>

                    <!-- Payer Phone Number Block -->
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex items-center justify-between mt-1">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">{{ t('yourPhone') }}</span>
                            <span class="text-lg font-bold font-mono tracking-wide text-gray-800 mt-1">{{ localPaymentPhone }}</span>
                        </div>

                        <button @click="copyText(localPaymentPhone, 'phone')" class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 bg-white border border-gray-200 shadow-sm px-3.5 py-1.5 rounded-xl hover:bg-gray-50 transition-colors">
                            <Check v-if="copiedState['phone']" class="h-3.5 w-3.5 text-green-500 animate-scaleIn" />
                            <Copy v-else class="h-3.5 w-3.5" />
                            <span>{{ copiedState['phone'] ? t('copie') : t('copier') }}</span>
                        </button>
                    </div>

                    <!-- Amount Block -->
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 flex items-center justify-between">
                        <div class="flex flex-col">
                            <span class="text-[10px] text-gray-500 font-bold uppercase tracking-wider">{{ t('amountToPay') }}</span>
                            <span class="text-lg font-bold font-mono tracking-wide text-gray-800 mt-1">{{ amount }} FCFA</span>
                        </div>

                        <button @click="copyText(amount.toString(), 'amount')" class="flex items-center gap-1.5 text-xs font-semibold text-gray-600 bg-white border border-gray-200 shadow-sm px-3.5 py-1.5 rounded-xl hover:bg-gray-50 transition-colors">
                            <Check v-if="copiedState['amount']" class="h-3.5 w-3.5 text-green-500 animate-scaleIn" />
                            <Copy v-else class="h-3.5 w-3.5" />
                            <span>{{ copiedState['amount'] ? t('copie') : t('copier') }}</span>
                        </button>
                    </div>
                </div>

                <!-- Validation Button Area -->
                <div class="flex flex-col gap-3">
                    <p class="text-xs text-gray-500 text-center px-4 leading-normal">
                        {{ t('notice') }}
                    </p>

                    <!-- Obtenir le résultat Button -->
                    <button
                        @click="checkStatus"
                        :disabled="isChecking"
                        class="w-full py-4 rounded-full bg-[#002075] text-white font-bold text-sm uppercase tracking-wider shadow-md hover:bg-[#001758] active:scale-[0.98] transition-all flex items-center justify-center gap-2 disabled:opacity-80"
                    >
                        <Loader2 v-if="isChecking" class="h-4.5 w-4.5 animate-spin" />
                        <span>{{ isChecking ? t('checking') : t('btnResult') }}</span>
                    </button>

                    <!-- Status notification helper message -->
                    <div v-if="statusMessage" class="p-3.5 rounded-xl text-center text-xs font-bold font-mono border"
                        :class="{
                            'bg-green-50 text-green-700 border-green-200': statusType === 'success',
                            'bg-red-50 text-red-700 border-red-200': statusType === 'error',
                            'bg-blue-50 text-blue-700 border-blue-200': statusType === 'info'
                        }"
                    >
                        {{ statusMessage }}
                    </div>
                </div>

                <!-- USSD Help Box (Light orange matching screenshot) -->
                <div class="bg-[#fff8eb] border border-[#ffe9cc] rounded-2xl p-5 flex flex-col gap-3">
                    <div>
                        <h4 class="text-xs font-bold text-[#b25900] uppercase tracking-wide leading-normal">
                            {{ t('ussdInstructions') }}
                        </h4>
                    </div>

                    <div class="flex flex-col gap-2.5">
                        <div class="flex flex-wrap items-baseline gap-1">
                            <span class="text-xs text-gray-700 font-medium">1. Composez</span>
                            <code class="text-xs font-bold font-mono text-red-600 bg-red-50 border border-red-100/50 px-1.5 py-0.5 rounded break-all select-all">
                                {{ ussdString }}
                            </code>
                        </div>

                        <!-- Composez & Copier inline buttons -->
                        <div class="flex gap-2">
                            <button
                                @click="dialCode(ussdString)"
                                class="bg-[#002f9c] text-white font-bold text-[10px] uppercase px-4 py-1.5 rounded-md hover:bg-[#00257a] transition-colors"
                            >
                                {{ t('composez') }}
                            </button>
                            <button
                                @click="copyText(ussdString, 'ussd')"
                                class="bg-[#002f9c] text-white font-bold text-[10px] uppercase px-4 py-1.5 rounded-md hover:bg-[#00257a] transition-colors"
                            >
                                {{ copiedState['ussd'] ? t('copie') : t('copier') }}
                            </button>
                        </div>

                        <span class="text-xs text-gray-700 font-medium">{{ t('enterSecret') }}</span>
                    </div>
                </div>
            </template>

        </div>
    </div>
</template>

<style scoped>
@keyframes scaleIn {
    0% { transform: scale(0.9); opacity: 0; }
    100% { transform: scale(1); opacity: 1; }
}
.animate-scaleIn {
    animation: scaleIn 0.2s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
</style>
