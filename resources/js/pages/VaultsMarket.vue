<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Lock, 
    ShieldCheck, 
    Clock, 
    TrendingUp, 
    Activity
} from 'lucide-vue-next';

// Define Props from backend controller
const props = defineProps<{
    vaults: Array<{
        id: number;
        name: string;
        fixed_investment_amount: string;
        fixed_return: string;
        profit_amount: string;
        duration: number;
        image: string | null;
        active: boolean;
    }>;
    userInvestments: Array<{
        id: number;
        amount: string;
        return_amount: string;
        expires_at: string;
        status: string;
        vault_plan: {
            name: string;
            duration: number;
        }
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de Bord',
        href: '/dashboard',
    },
    {
        title: 'ARM Vaults',
        href: '/vaults',
    },
];

// User Info from Inertia global state
const page = usePage();
const user = computed(() => page.props.auth.user);

// Selection & Modal States
const selectedVault = ref<any | null>(null);
const showConfirmModal = ref(false);
const showErrorModal = ref(false);
const errorMessage = ref('');

const form = useForm({});

// Format currency standard utility
const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
};

// Select vault to open details page
const selectVault = (vault: any) => {
    router.visit(`/products/vault/${vault.id}`);
};

// Handle invest confirm action
const confirmInvest = () => {
    if (!selectedVault.value) return;
    
    form.post(`/vaults/${selectedVault.value.id}/invest`, {
        onSuccess: () => {
            showConfirmModal.value = false;
            selectedVault.value = null;
        },
        onError: (errors) => {
            showConfirmModal.value = false;
            errorMessage.value = errors.error || 'Erreur lors de l\'investissement.';
            showErrorModal.value = true;
        }
    });
};

const calculateRemainingDays = (expiresAt: string) => {
    const end = new Date(expiresAt).getTime();
    const now = Date.now();
    const diff = end - now;
    if (diff <= 0) return 0;
    return Math.ceil(diff / (1000 * 60 * 60 * 24));
};

const { containerRef } = useRevealAnimation();

// Watch to freeze scroll on modal activation
watch([showConfirmModal, showErrorModal], ([newConfirm, newError]) => {
    if (newConfirm || newError) {
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
    <Head title="ARM Vaults" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full">
            
            <!-- Welcome Header -->
            <div data-animate="fade-down" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div>
                    <h2 class="text-xl md:text-2xl font-semibold text-white">ARM Vaults</h2>
                    <p class="text-xs text-muted-foreground mt-0.5">Staking de haute sécurité. Verrouillez vos fonds et recevez des rendements garantis.</p>
                </div>
                <div class="glass px-4 py-2 rounded-xl flex items-center gap-2 border border-white/10">
                    <span class="text-xs text-muted-foreground">Solde Disponible:</span>
                    <span class="text-xs font-bold text-secondary">{{ formatXAF(user?.balance || 0) }}</span>
                </div>
            </div>

            <!-- Active Vaults Banner -->
            <div v-if="userInvestments.length > 0" data-animate="fade-up" data-delay="100" class="glass rounded-2xl p-5 border border-secondary/20 bg-secondary/[0.01]">
                <h3 class="text-sm font-bold text-white mb-4 flex items-center gap-2">
                    <ShieldCheck class="h-5 w-5 text-secondary" :stroke-width="2.5" />
                    Vos Investissements Actifs
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div v-for="inv in userInvestments" :key="inv.id" class="border border-white/5 bg-white/[0.02] p-4 rounded-xl flex flex-col gap-3">
                        <div class="flex justify-between items-center">
                            <span class="text-xs font-bold text-secondary">{{ inv.vault_plan.name }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full bg-secondary/10 text-secondary border border-secondary/20">
                                {{ calculateRemainingDays(inv.expires_at) }} jours restants
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-muted-foreground">Investi: <span class="text-white font-mono">{{ formatXAF(inv.amount) }}</span></span>
                            <span class="text-muted-foreground">Retour: <span class="text-emerald-400 font-mono font-bold">{{ formatXAF(inv.return_amount) }}</span></span>
                        </div>
                        <div class="h-1.5 w-full bg-white/5 rounded-full overflow-hidden">
                            <div class="h-full bg-secondary" :style="{ width: Math.max(0, 100 - (calculateRemainingDays(inv.expires_at) / inv.vault_plan.duration * 100)) + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vault Plans Market -->
            <div data-stagger="true" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div 
                    v-for="vault in vaults" 
                    :key="vault.id"
                    data-animate="fade-up"
                    class="glass rounded-2xl p-6 relative flex flex-col justify-between transition-all duration-300 border border-white/5 hover:border-secondary/30 group shadow-lg cursor-pointer"
                    @click="selectVault(vault)"
                >
                    <!-- Background aesthetic -->
                    <div class="absolute inset-0 bg-grid-secondary opacity-[0.03] pointer-events-none rounded-2xl"></div>

                    <!-- Header -->
                    <div class="flex justify-between items-start mb-5 z-10">
                        <div class="h-12 w-12 rounded-xl bg-secondary/10 border border-secondary/20 flex items-center justify-center text-secondary shadow-[0_0_15px_rgba(139,92,246,0.2)]">
                            <Lock class="h-6 w-6" :stroke-width="2.5" />
                        </div>
                        <span class="text-[12px] font-black text-white tracking-wide px-3.5 py-1.5 rounded-lg bg-white/5 border border-white/10 flex items-center gap-1 shadow-sm">
                            <Clock class="h-3.5 w-3.5 text-secondary animate-pulse" :stroke-width="2.5" />
                            {{ vault.duration }} Jours
                        </span>
                    </div>

                    <!-- Details -->
                    <div class="z-10 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white group-hover:text-secondary transition-colors duration-300">{{ vault.name }}</h3>
                            <p class="text-xs text-muted-foreground mt-1">Programme de rendement sécurisé à terme fixe.</p>
                        </div>

                        <!-- Financials -->
                        <div class="grid grid-cols-2 gap-4 border-y border-white/5 py-4 my-4">
                            <div>
                                <span class="text-[10px] text-muted-foreground block">Dépôt Requis</span>
                                <span class="text-[14.5px] font-black text-white font-mono tracking-tight">{{ formatXAF(vault.fixed_investment_amount) }}</span>
                            </div>
                            <div>
                                <span class="text-[10px] text-muted-foreground block">Profit Total</span>
                                <span class="text-[14.5px] font-black text-secondary font-mono tracking-tight">+{{ formatXAF(vault.profit_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="z-10 mt-auto">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs text-muted-foreground">Retour Final:</span>
                            <span class="text-xl font-black text-emerald-400 font-mono tracking-tight">{{ formatXAF(vault.fixed_return) }}</span>
                        </div>

                        <button 
                            @click="selectVault(vault)"
                            class="w-full py-2.5 rounded-xl bg-secondary text-white hover:bg-secondary/90 transition-all duration-300 font-bold text-xs shadow-[0_0_20px_rgba(139,92,246,0.3)] flex items-center justify-center gap-2"
                        >
                            <Lock class="h-4 w-4" :stroke-width="2.5" />
                            Verrouiller & Investir
                        </button>
                    </div>
                </div>
            </div>

            <!-- Confirm Modal -->
            <Teleport to="body">
                <div v-if="showConfirmModal && selectedVault" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden animate-fade-in">
                    <div class="glass max-w-md w-full rounded-2xl border border-secondary/20 p-6 relative overflow-hidden shadow-[0_0_50px_rgba(139,92,246,0.15)]">
                        <div class="absolute inset-0 bg-grid-secondary opacity-10 pointer-events-none"></div>

                        <h3 class="text-base font-bold text-white mb-3 flex items-center gap-2">
                            <Lock class="h-5 w-5 text-secondary" :stroke-width="2.5" />
                            Confirmer l'Investissement
                        </h3>

                        <div class="border border-white/5 bg-white/[0.01] p-4 rounded-xl mb-4">
                            <h4 class="text-sm font-extrabold text-secondary">{{ selectedVault.name }}</h4>
                            <div class="grid grid-cols-2 gap-3 mt-3 text-xs">
                                <div>
                                    <span class="text-[10px] text-muted-foreground block">Montant à verrouiller</span>
                                    <span class="font-bold text-white font-mono">{{ formatXAF(selectedVault.fixed_investment_amount) }}</span>
                                </div>
                                <div>
                                    <span class="text-[10px] text-muted-foreground block">Durée</span>
                                    <span class="font-bold text-white">{{ selectedVault.duration }} Jours</span>
                                </div>
                                <div class="col-span-2 border-t border-white/5 pt-2 mt-1">
                                    <span class="text-[10px] text-muted-foreground block">Rendement total estimé</span>
                                    <span class="font-extrabold text-emerald-400 font-mono text-base">{{ formatXAF(selectedVault.fixed_return) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3.5 rounded-xl bg-secondary/10 border border-secondary/20 flex items-start gap-2.5 mb-4">
                            <Activity class="h-4.5 w-4.5 text-secondary shrink-0 mt-0.5" :stroke-width="2.5" />
                            <div class="text-xs">
                                <span class="font-bold text-secondary block">Contrat Sécurisé</span>
                                <p class="text-[11px] text-muted-foreground mt-0.5">
                                    Vos fonds seront verrouillés pendant {{ selectedVault.duration }} jours. Le capital et les profits seront crédités automatiquement à l'échéance.
                                </p>
                            </div>
                        </div>

                        <div class="flex gap-3">
                            <button 
                                @click="showConfirmModal = false"
                                class="flex-1 py-2.5 rounded-xl border border-white/10 text-white font-semibold text-xs hover:bg-white/5 transition-all duration-300"
                            >
                                Annuler
                            </button>
                            <button 
                                @click="confirmInvest"
                                class="flex-1 py-2.5 rounded-xl bg-secondary text-white font-bold text-xs shadow-[0_0_15px_rgba(139,92,246,0.3)] hover:bg-secondary/90 transition-all duration-300 flex items-center justify-center gap-2"
                            >
                                Confirmer
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

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
.bg-grid-secondary {
    background-size: 40px 40px;
    background-image: 
        linear-gradient(to right, rgba(139, 92, 246, 0.05) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(139, 92, 246, 0.05) 1px, transparent 1px);
}
</style>
