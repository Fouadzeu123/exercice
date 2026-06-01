<script setup lang="ts">
import { ref } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Lock,
    Key,
    CheckCircle2,
    AlertCircle,
    ArrowLeft,
    Globe,
    ShieldCheck
} from 'lucide-vue-next';
import { t, currentLocale, toggleLocale } from '@/utils/trans';

const props = defineProps<{
    hasPassword: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('Tableau de Bord', 'Dashboard'), href: '/dashboard' },
    { title: t('Mon Profil', 'My Profile'), href: '/settings/profile' },
    { title: t('Code PIN de Retrait', 'Withdrawal PIN'), href: '/settings/withdrawal-password' },
];

const pinForm = useForm({
    current_password: '',
    withdrawal_password: '',
    withdrawal_password_confirmation: '',
});

const isSubmitting = ref(false);
const successMsg = ref('');

const handleSubmit = () => {
    isSubmitting.value = true;
    successMsg.value = '';
    
    pinForm.post('/settings/withdrawal-password', {
        onSuccess: () => {
            pinForm.reset();
            isSubmitting.value = false;
            successMsg.value = t('Votre Code PIN de retrait a été enregistré avec succès.', 'Your withdrawal PIN has been successfully saved.');
            setTimeout(() => {
                successMsg.value = '';
            }, 5000);
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head :title="t('Code PIN de Retrait', 'Withdrawal PIN')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24 text-white">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-[#0c0f1d] p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div class="flex items-center gap-3">
                    <Link 
                        href="/settings/profile"
                        class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black transition-colors"
                    >
                        <ArrowLeft class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </Link>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">
                            {{ t('Code PIN de Retrait', 'Withdrawal PIN') }}
                        </h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">
                            {{ t('Protégez vos transferts de fonds', 'Protect your funds transfers') }}
                        </p>
                    </div>
                </div>
                <!-- Language selector -->
                <div 
                    @click="toggleLocale"
                    class="border border-white/10 bg-white/5 px-2.5 py-1 rounded-xl text-[10px] font-bold tracking-wide flex items-center gap-1.5 hover:border-purple-400/50 transition-colors cursor-pointer uppercase select-none"
                >
                    <Globe class="h-3.5 w-3.5 text-purple-400" :stroke-width="2.5" />
                    <span>{{ currentLocale }}</span>
                </div>
            </div>

            <!-- FORM CARD -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm">
                
                <div class="flex items-center gap-2 mb-4 border-b border-purple-500/10 pb-3">
                    <Lock class="h-4.5 w-4.5 text-purple-400" :stroke-width="2.5" />
                    <h3 class="text-xs font-black text-white uppercase tracking-wider">
                        {{ hasPassword ? t('Mettre à jour votre Code PIN', 'Update your PIN code') : t('Configurer votre Code PIN', 'Configure your PIN code') }}
                    </h3>
                </div>

                <!-- Alert Success -->
                <div v-if="successMsg" class="mb-4 p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 text-[10px] rounded-xl font-bold font-mono flex items-center gap-2 animate-pulse">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                    <span>{{ successMsg }}</span>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <!-- Current PIN (if set) -->
                    <div v-if="hasPassword" class="flex flex-col gap-1.5">
                        <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                            {{ t('Code PIN Actuel', 'Current PIN Code') }}
                        </label>
                        <div class="relative">
                            <Key class="absolute left-3.5 top-3.5 h-4 w-4 text-purple-500/50" :stroke-width="2.5" />
                            <input 
                                type="password" 
                                v-model="pinForm.current_password"
                                required
                                placeholder="••••"
                                maxlength="12"
                                class="w-full bg-black/50 border border-purple-500/20 rounded-xl py-3.5 pl-11 pr-4 text-white font-mono text-center tracking-widest text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/20"
                            />
                        </div>
                        <span v-if="pinForm.errors.current_password" class="text-[9px] text-rose-400 font-bold uppercase tracking-wider mt-1 flex items-center gap-1">
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ pinForm.errors.current_password }}
                        </span>
                    </div>

                    <!-- New PIN -->
                    <div class="flex flex-col gap-1.5">
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
                        <span v-if="pinForm.errors.withdrawal_password" class="text-[9px] text-rose-400 font-bold uppercase tracking-wider mt-1 flex items-center gap-1">
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ pinForm.errors.withdrawal_password }}
                        </span>
                    </div>

                    <!-- Confirm PIN -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                            {{ t('Confirmer le Nouveau Code PIN', 'Confirm New PIN Code') }}
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

                    <button 
                        type="submit"
                        :disabled="isSubmitting || pinForm.processing"
                        class="w-full py-3.5 rounded-xl bg-purple-500 text-black font-extrabold text-xs uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300 disabled:opacity-50"
                    >
                        {{ pinForm.processing ? t('Enregistrement...', 'Saving...') : t('Enregistrer le PIN de fonds', 'Save Funds PIN') }}
                    </button>
                </form>
            </div>

            <!-- INFO SECURITY -->
            <div data-animate="scale-up" data-delay="150" class="p-4 rounded-2xl border border-purple-500/10 bg-purple-950/5 relative overflow-hidden shadow">
                <div class="flex items-start gap-2.5">
                    <ShieldCheck class="h-4.5 w-4.5 text-purple-400 shrink-0 mt-0.5" :stroke-width="2.5" />
                    <div>
                        <span class="text-[10px] font-black text-white uppercase tracking-wider block mb-1">
                            {{ t('Code PIN de retrait réglementaire', 'Regulatory Withdrawal PIN') }}
                        </span>
                        <span class="text-[9px] text-slate-400 leading-relaxed block">
                            {{ t('Ce code PIN de fonds (ou mot de passe de retrait) vous sera demandé pour valider chaque retrait. Ne le communiquez jamais et évitez les suites simples.', 'This funds PIN (or withdrawal password) will be required to validate each withdrawal. Never share it and avoid simple sequences.') }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
