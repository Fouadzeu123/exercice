<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    CreditCard,
    PlusCircle,
    Smartphone,
    User,
    CheckCircle2,
    Trash2,
    Star,
    Globe,
    AlertCircle,
    Shield
} from 'lucide-vue-next';
import { t, currentLocale, toggleLocale } from '@/utils/trans';

const props = defineProps<{
    methods: Array<{
        id: number;
        operator: string;
        full_name: string;
        phone: string;
        is_default: boolean;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('Tableau de bord', 'Dashboard'), href: '/dashboard' },
    { title: t('Mon Profil', 'My Profile'), href: '/settings/profile' },
    { title: t('Numéros Mobiles', 'Mobile Numbers'), href: '/settings/mobile-numbers' },
];

const mobileForm = useForm({
    operator: 'mtn',
    full_name: '',
    phone: '',
});

const isSubmitting = ref(false);
const showProposePinModal = ref(false);

const handleSubmit = () => {
    isSubmitting.value = true;
    mobileForm.post('/settings/mobile-numbers', {
        onSuccess: () => {
            mobileForm.reset();
            isSubmitting.value = false;
            // Propose withdrawal password right away!
            showProposePinModal.value = true;
        },
        onError: () => {
            isSubmitting.value = false;
        }
    });
};

const makeDefault = (id: number) => {
    router.patch(`/settings/mobile-numbers/${id}/default`);
};

const deleteMethod = (id: number) => {
    if (confirm(t('Voulez-vous vraiment supprimer ce moyen de retrait ?', 'Are you sure you want to delete this withdrawal method?'))) {
        router.delete(`/settings/mobile-numbers/${id}`);
    }
};

watch(showProposePinModal, (newVal) => {
    if (newVal) {
        document.body.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
});

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head :title="t('Numéros de Retrait', 'Withdrawal Numbers')" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <CreditCard class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">
                            {{ t('Numéros de Retrait', 'Withdrawal Numbers') }}
                        </h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">
                            {{ t('Gérez vos comptes de réception', 'Manage your reception accounts') }}
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

            <!-- ADD FORM -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm">
                <div class="flex items-center gap-2 mb-4 border-b border-purple-500/10 pb-3">
                    <PlusCircle class="h-4 w-4 text-purple-400" :stroke-width="2.5" />
                    <h3 class="text-xs font-black text-white uppercase tracking-wider">
                        {{ t('Configurer un nouveau numéro', 'Configure a new number') }}
                    </h3>
                </div>

                <form @submit.prevent="handleSubmit" class="space-y-4">
                    <!-- Operator -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                            {{ t('Opérateur Réseau', 'Network Operator') }}
                        </label>
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                type="button"
                                @click="mobileForm.operator = 'mtn'"
                                class="py-3.5 rounded-xl border flex items-center justify-center gap-2 transition-all duration-300"
                                :class="mobileForm.operator === 'mtn' ? 'bg-purple-500/10 border-purple-400 text-white shadow-[0_0_12px_rgba(168,85,247,0.15)]' : 'bg-black/30 border-purple-500/10 text-slate-400 hover:text-white'"
                            >
                                <div class="w-2.5 h-2.5 rounded-full" :class="mobileForm.operator === 'mtn' ? 'bg-yellow-400' : 'bg-slate-600'"></div>
                                <span class="text-[10px] font-black uppercase tracking-wider">MTN MoMo</span>
                            </button>

                            <button
                                type="button"
                                @click="mobileForm.operator = 'orange'"
                                class="py-3.5 rounded-xl border flex items-center justify-center gap-2 transition-all duration-300"
                                :class="mobileForm.operator === 'orange' ? 'bg-purple-500/10 border-purple-400 text-white shadow-[0_0_12px_rgba(168,85,247,0.15)]' : 'bg-black/30 border-purple-500/10 text-slate-400 hover:text-white'"
                            >
                                <div class="w-2.5 h-2.5 rounded-full" :class="mobileForm.operator === 'orange' ? 'bg-orange-500' : 'bg-slate-600'"></div>
                                <span class="text-[10px] font-black uppercase tracking-wider">Orange Money</span>
                            </button>
                        </div>
                    </div>

                    <!-- Full Name -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                            {{ t('Nom Complet (Titulaire)', 'Full Name (Holder)') }}
                        </label>
                        <div class="relative">
                            <User class="absolute left-3.5 top-3.5 h-4 w-4 text-purple-500/50" :stroke-width="2.5" />
                            <input
                                type="text"
                                v-model="mobileForm.full_name"
                                required
                                :placeholder="t('Ex: Jean Dupont', 'E.g., John Doe')"
                                class="w-full bg-black/50 border border-purple-500/20 rounded-xl py-3.5 pl-11 pr-4 text-white font-sans text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/25"
                            />
                        </div>
                    </div>

                    <!-- Phone Number -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-[10px] text-slate-400 uppercase font-bold tracking-wider">
                            {{ t('Numéro de Téléphone', 'Phone Number') }}
                        </label>
                        <div class="relative">
                            <Smartphone class="absolute left-3.5 top-3.5 h-4 w-4 text-purple-500/50" :stroke-width="2.5" />
                            <input
                                type="tel"
                                v-model="mobileForm.phone"
                                required
                                placeholder="2376xxxxxxxx"
                                class="w-full bg-black/50 border border-purple-500/20 rounded-xl py-3.5 pl-11 pr-4 text-white font-mono text-xs focus:border-purple-400 outline-none transition-all placeholder:text-white/25"
                            />
                        </div>
                        <span v-if="mobileForm.errors.phone" class="text-[12px] text-rose-400 font-bold uppercase tracking-wider mt-1 flex items-center gap-1">
                            <AlertCircle class="h-3.5 w-3.5 shrink-0" />
                            {{ mobileForm.errors.phone }}
                        </span>
                    </div>

                    <button
                        type="submit"
                        :disabled="isSubmitting || mobileForm.processing"
                        class="w-full py-3.5 rounded-xl bg-purple-500 text-black font-extrabold text-xs uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300 disabled:opacity-50"
                    >
                        {{ mobileForm.processing ? t('Enregistrement...', 'Saving...') : t('Enregistrer le numéro', 'Register number') }}
                    </button>
                </form>
            </div>

            <!-- CONFIGURED METHODS (CARDS) -->
            <div data-stagger="true" class="space-y-3.5">
                <h3 class="text-xs font-black text-white uppercase tracking-wider mb-2">
                    {{ t('Vos Comptes de Retrait', 'Your Withdrawal Accounts') }} ({{ methods.length }})
                </h3>

                <div v-if="methods.length === 0" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-8 text-center text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                    {{ t('Aucun numéro configuré actuellement', 'No numbers currently configured') }}
                </div>

                <div
                    v-for="method in methods" :key="method.id"
                    data-animate="fade-up"
                    class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border rounded-2xl p-4 flex flex-col gap-3 relative overflow-hidden transition-all duration-300"
                    :class="method.is_default ? 'border-purple-400 shadow-[0_0_15px_rgba(168,85,247,0.15)] bg-purple-950/[0.02]' : 'border-purple-500/10 bg-black/20'"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center text-xs" :class="method.operator === 'mtn' ? 'bg-yellow-500/15 text-yellow-400' : 'bg-orange-500/15 text-orange-500'">
                                <Smartphone class="h-4.5 w-4.5" :stroke-width="2.5" />
                            </div>
                            <div>
                                <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    {{ method.operator === 'mtn' ? 'MTN MoMo' : 'Orange Money' }}
                                </span>
                                <div class="text-xs font-black text-white font-mono mt-0.5">{{ method.phone }}</div>
                            </div>
                        </div>

                        <!-- Star to make default -->
                        <div class="flex items-center gap-2">
                            <button
                                v-if="!method.is_default"
                                @click="makeDefault(method.id)"
                                class="w-7 h-7 rounded-lg border border-purple-500/10 bg-black/40 flex items-center justify-center text-slate-500 hover:text-purple-400 hover:border-purple-500/30 transition-all"
                                :title="t('Définir par défaut', 'Set as default')"
                            >
                                <Star class="h-4 w-4" :stroke-width="2.5" />
                            </button>
                            <span
                                v-else
                                class="text-[8px] font-black bg-purple-500/10 border border-purple-500/20 text-purple-400 px-2 py-0.5 rounded-full uppercase tracking-wider flex items-center gap-1"
                            >
                                <CheckCircle2 class="h-3 w-3" :stroke-width="2.5" />
                                {{ t('Par Défaut', 'Default') }}
                            </span>

                            <!-- Delete button -->
                            <button
                                @click="deleteMethod(method.id)"
                                class="w-7 h-7 rounded-lg border border-rose-500/15 bg-rose-500/5 hover:bg-rose-500/15 flex items-center justify-center text-rose-400 transition-all"
                                :title="t('Supprimer', 'Delete')"
                            >
                                <Trash2 class="h-4 w-4" :stroke-width="2.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Holder Info -->
                    <div class="bg-black/30 border border-white/5 rounded-xl p-3 flex justify-between items-center text-[10px] text-slate-400">
                        <span class="font-bold uppercase">{{ t('Titulaire', 'Holder') }} :</span>
                        <span class="text-white font-black uppercase">{{ method.full_name }}</span>
                    </div>
                </div>
            </div>

            <!-- SECURITY BANNER -->
            <div data-animate="scale-up" data-delay="150" class="p-4 rounded-2xl border border-purple-500/10 bg-purple-950/5 relative overflow-hidden shadow">
                <div class="flex items-start gap-2.5">
                    <Shield class="h-4.5 w-4.5 text-purple-400 shrink-0 mt-0.5" :stroke-width="2.5" />
                    <div>
                        <span class="text-[13px] font-black text-white uppercase tracking-wider block mb-1">
                            {{ t('Sécurité des transactions', 'Transaction Security') }}
                        </span>
                        <span class="text-[13px] text-slate-400 leading-relaxed block">
                            {{ t('Pour votre sécurité, chaque numéro de retrait ne peut être utilisé que par un seul compte ARM Holdings. Veillez à renseigner le nom exact associé à votre opérateur mobile.', 'For your security, each withdrawal number can only be used by a single ARM Holdings account. Make sure to enter the exact name associated with your mobile operator.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- PROPOSE PIN MODAL -->
            <Teleport to="body">
                <div v-if="showProposePinModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fadeIn">
                    <div class="glass max-w-xl w-full rounded-2xl border border-purple-500/30 p-6 relative text-center shadow-[0_0_50px_rgba(168,85,247,0.25)]">
                        <div class="h-16 w-16 rounded-full bg-purple-500/10 border-2 border-purple-500/30 flex items-center justify-center text-purple-400 mb-6 mx-auto animate-pulse">
                            <Shield class="h-8 w-8" />
                        </div>

                        <span class="text-purple-400 font-extrabold uppercase text-[10px] tracking-widest block mb-1">Sécurité Réseau</span>
                        <h3 class="text-sm font-black text-white uppercase tracking-wider mb-2">Sécuriser vos Retraits</h3>
                        <p class="text-slate-300 text-xs leading-relaxed font-sans mb-6">
                            Votre numéro de retrait a été enregistré avec succès ! Pour des raisons de sécurité, vous devez définir un code PIN secret avant d'initier tout transfert externe. Souhaitez-vous le définir maintenant ?
                        </p>
                        <div class="flex gap-3">
                            <button
                                @click="showProposePinModal = false"
                                class="flex-1 py-2.5 rounded-xl border border-white/10 text-white font-semibold text-xs hover:bg-white/5 transition-all duration-300"
                            >
                                Plus tard
                            </button>
                            <Link
                                href="/settings/withdrawal-password"
                                class="flex-1 py-2.5 rounded-xl bg-primary text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-primary/95 transition-all duration-300 flex items-center justify-center gap-2"
                            >
                                Définir mon PIN
                            </Link>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </AppLayout>
</template>
