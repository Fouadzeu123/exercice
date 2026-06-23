<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Cpu,
    Calendar,
    Coins,
    TrendingUp,
    Shield,
    CheckCircle2,
    Clock,
    ShoppingBag,
    Zap
} from 'lucide-vue-next';

const props = defineProps<{
    orders: Array<{
        id: number;
        node_id: number;
        node_name: string;
        node_amount: number;
        generation_profit: number;
        duration: number;
        technology_level: number;
        image_url: string;
        active: boolean;
        created_at: string;
        expires_at: string | null;
    }>;
    vaultInvestments?: Array<{
        id: number;
        amount: number;
        return_amount: number;
        status: string;
        payouts_claimed: number;
        created_at: string;
        expires_at: string;
        vault_plan?: {
            id: number;
            name: string;
            duration: number;
            payout_type: string;
            image: string | null;
        } | null;
    }>;
    userAvips?: Array<{
        id: number;
        amount: number;
        active: boolean;
        created_at: string;
        expires_at: string | null;
        avip_product?: {
            id: number;
            name: string;
            description: string;
            daily_salary: number;
            avip_level: number;
            image: string | null;
        } | null;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Mes Commandes', href: '/commandes' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Convert relative /uploads/ paths to absolute URLs (needed for mobile WebViews)
const resolveImageUrl = (url: string | null | undefined): string => {
    if (!url) return '/images/cyber_server_hero.png';

    let cleanUrl = url;
    if (url.includes('/uploads/')) {
        const index = url.indexOf('/uploads/');
        cleanUrl = url.substring(index);
    }

    if (cleanUrl.startsWith('http://') || cleanUrl.startsWith('https://')) return cleanUrl;
    
    const page = usePage();
    let appUrl = page.props.appUrl as string;
    
    if (!appUrl) {
        const origin = typeof window !== 'undefined' ? window.location.origin : '';
        if (!origin || origin.includes('localhost') || origin.startsWith('capacitor://') || origin.startsWith('http://127.0.0.1')) {
            appUrl = 'https://armicm.com';
        } else {
            appUrl = origin;
        }
    }
    
    const cleanBaseUrl = appUrl.replace(/\/+$/, '');
    
    if (cleanUrl.startsWith('/')) {
        return cleanBaseUrl + cleanUrl;
    }
    return cleanBaseUrl + '/' + cleanUrl;
};

const getDaysRemaining = (expiresAt: string | null): string => {
    if (!expiresAt) return 'Illimité';
    const now = new Date();
    const end = new Date(expiresAt);
    if (end <= now) return 'Expiré';
    let count = 0;
    const cur = new Date(now);
    cur.setHours(0, 0, 0, 0);
    const endDay = new Date(end);
    endDay.setHours(0, 0, 0, 0);
    while (cur < endDay) {
        const dow = cur.getDay();
        if (dow !== 0 && dow !== 6) count++;
        cur.setDate(cur.getDate() + 1);
    }
    return count > 0 ? `${count} jours restants` : 'Expiré';
};

const activeTab = ref('nodes'); // 'nodes' | 'vaults' | 'avips'

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Mes Commandes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <ShoppingBag class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">
                            {{ activeTab === 'nodes' ? 'Mes Serveurs' : (activeTab === 'vaults' ? 'Mes Placements Vault' : 'Mes Accélérateurs') }}
                        </h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Historique de vos acquisitions</p>
                    </div>
                </div>
                <span class="bg-purple-500/15 text-purple-400 text-[10px] font-black px-2.5 py-1 rounded-full border border-purple-500/20 font-mono">
                    {{ activeTab === 'nodes' ? orders.length : (activeTab === 'vaults' ? (vaultInvestments?.length || 0) : (userAvips?.length || 0)) }}
                </span>
            </div>

            <!-- TAB NAVIGATION BAR -->
            <div data-animate="fade-down" class="flex bg-black/40 border border-purple-500/10 p-1 rounded-xl w-full justify-between">
                <button 
                    @click="activeTab = 'nodes'" 
                    class="flex-1 py-2 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                    :class="activeTab === 'nodes' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                >
                    Serveurs GPU ({{ orders.length }})
                </button>
                <button 
                    @click="activeTab = 'vaults'" 
                    class="flex-1 py-2 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                    :class="activeTab === 'vaults' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                >
                    Coffres Vault ({{ vaultInvestments?.length || 0 }})
                </button>
                <button 
                    @click="activeTab = 'avips'" 
                    class="flex-1 py-2 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all"
                    :class="activeTab === 'avips' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-lg' : 'text-slate-400 hover:text-white'"
                >
                    Produits AVIP ({{ userAvips?.length || 0 }})
                </button>
            </div>

            <!-- TAB 1: SERVEURS GPU (NODES) -->
            <div v-if="activeTab === 'nodes'" class="space-y-4" data-stagger>
                <div v-if="orders.length === 0" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-10 text-center shadow-2xl backdrop-blur-sm">
                    <Cpu class="h-10 w-10 text-purple-400/20 mx-auto mb-3.5 animate-pulse" :stroke-width="2.5" />
                    <p class="text-xs font-black text-white uppercase tracking-wider mb-2">Aucun serveur actif</p>
                    <p class="text-[10px] text-slate-400 leading-relaxed max-w-xs mx-auto mb-5">Visitez la console de calcul ou le marché des nœuds pour louer votre premier serveur technologique.</p>
                    <Link 
                        href="/dashboard"
                        class="inline-flex py-2.5 px-6 rounded-xl bg-purple-500 text-black font-extrabold text-[10px] uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300"
                    >
                        Louer un Serveur
                    </Link>
                </div>

                <div 
                    v-for="(order, idx) in orders" :key="order.id"
                    data-animate="fade-up" :data-delay="String(idx * 100)"
                    class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative overflow-hidden group hover:border-purple-400/30 transition-all duration-300 card-hover-lift"
                >
                    <!-- Server image background indicator -->
                    <div class="absolute right-0 top-0 w-32 h-32 opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-110">
                        <img 
                            :src="resolveImageUrl(order.image_url)" 
                            @error="(e: Event) => ((e.target as HTMLImageElement).src = '/images/cyber_server_hero.png')"
                            class="w-full h-full object-contain" 
                            alt=""
                        />
                    </div>

                    <div class="flex items-center gap-3 pb-3.5 border-b border-purple-500/10">
                        <div class="w-12 h-12 rounded-2xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center shrink-0">
                            <Cpu class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                        </div>
                        <div>
                            <div class="text-xs font-black text-white uppercase tracking-wider">{{ order.node_name }}</div>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[8px] font-black bg-purple-500/10 border border-purple-500/20 text-purple-400 px-1.5 py-0.5 rounded uppercase">
                                    Niveau {{ order.technology_level }}
                                </span>
                                <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                                    :class="order.active ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/20 text-rose-400'"
                                >
                                    {{ order.active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4 text-xs font-mono">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Coins class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Valeur
                            </span>
                            <span class="text-xs font-black text-white">{{ formatXAF(order.node_amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <TrendingUp class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Gain Quotidien
                            </span>
                            <span class="text-xs font-black text-emerald-400">+{{ formatXAF(order.generation_profit) }}/j</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Calendar class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Date d'Acquisition
                            </span>
                            <span class="text-xs font-black text-white">{{ formatDate(order.created_at) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Clock class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Durée Restante
                            </span>
                            <span class="text-xs font-black text-purple-400 font-bold">{{ getDaysRemaining(order.expires_at) }}</span>
                        </div>
                    </div>

                    <!-- Footnote security -->
                    <div class="mt-4 p-2.5 rounded-xl bg-purple-950/20 border border-purple-500/10 flex items-center gap-2">
                        <Shield class="h-3.5 w-3.5 text-purple-400 shrink-0" :stroke-width="2.5" />
                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Algorithme de calcul sécurisé ARM Holdings PLC</span>
                    </div>
                </div>
            </div>

            <!-- TAB 2: COFFRES VAULT (VAULT PLACEMENTS) -->
            <div v-else-if="activeTab === 'vaults'" class="space-y-4" data-stagger>
                <div v-if="!vaultInvestments || vaultInvestments.length === 0" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-10 text-center shadow-2xl backdrop-blur-sm">
                    <Coins class="h-10 w-10 text-purple-400/20 mx-auto mb-3.5 animate-pulse" :stroke-width="2.5" />
                    <p class="text-xs font-black text-white uppercase tracking-wider mb-2">Aucun placement actif</p>
                    <p class="text-[10px] text-slate-400 leading-relaxed max-w-xs mx-auto mb-5">Ouvrez un contrat d'épargne bloqué sur les coffres Vault pour maximiser vos rendements stables.</p>
                    <Link 
                        href="/dashboard"
                        class="inline-flex py-2.5 px-6 rounded-xl bg-purple-500 text-black font-extrabold text-[10px] uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300"
                    >
                        Placer dans le Vault
                    </Link>
                </div>

                <div 
                    v-for="(inv, idx) in vaultInvestments" :key="inv.id"
                    data-animate="fade-up" :data-delay="String(idx * 100)"
                    class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative overflow-hidden group hover:border-purple-400/30 transition-all duration-300 card-hover-lift"
                >
                    <!-- Vault image background indicator -->
                    <div class="absolute right-0 top-0 w-32 h-32 opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-110">
                        <img 
                            :src="resolveImageUrl(inv.vault_plan?.image)" 
                            @error="(e: Event) => ((e.target as HTMLImageElement).src = '/images/cyber_server_hero.png')"
                            class="w-full h-full object-contain" 
                            alt=""
                        />
                    </div>

                    <div class="flex items-center gap-3 pb-3.5 border-b border-purple-500/10">
                        <div class="w-12 h-12 rounded-2xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center shrink-0">
                            <Coins class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                        </div>
                        <div>
                            <div class="text-xs font-black text-white uppercase tracking-wider">{{ inv.vault_plan ? inv.vault_plan.name : 'Placement Coffre' }}</div>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[8px] font-black bg-purple-500/10 border border-purple-500/20 text-purple-400 px-1.5 py-0.5 rounded uppercase">
                                    {{ inv.vault_plan?.payout_type === 'daily' ? 'Rendement Journalier' : 'À l\'échéance' }}
                                </span>
                                <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase font-mono"
                                    :class="inv.status === 'active' ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-purple-500/10 border border-purple-500/20 text-purple-400'"
                                >
                                    {{ inv.status === 'active' ? 'En Cours' : 'Complété' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4 text-xs font-mono">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Coins class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Capital Investi
                            </span>
                            <span class="text-xs font-black text-white">{{ formatXAF(inv.amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <TrendingUp class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Retour Attendu
                            </span>
                            <span class="text-xs font-black text-emerald-400">+{{ formatXAF(inv.return_amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Calendar class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Date de Placement
                            </span>
                            <span class="text-xs font-black text-white">{{ formatDate(inv.created_at) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Clock class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Échéance / Fin
                            </span>
                            <span class="text-xs font-black text-purple-400">{{ formatDate(inv.expires_at) }}</span>
                        </div>
                    </div>

                    <!-- Progress Indicator if Daily Payout -->
                    <div v-if="inv.vault_plan?.payout_type === 'daily'" class="mt-3.5">
                        <div class="flex justify-between items-center text-[9px] text-slate-400 font-bold uppercase tracking-wider mb-1 font-mono">
                            <span>Retours payés :</span>
                            <span class="text-white">{{ inv.payouts_claimed }} / {{ inv.vault_plan.duration }} Jours</span>
                        </div>
                        <div class="w-full h-1.5 rounded-full bg-white/5 overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-purple-500 to-cyan-500 rounded-full" :style="`width: ${(inv.payouts_claimed / inv.vault_plan.duration) * 100}%`"></div>
                        </div>
                    </div>

                    <!-- Footnote security -->
                    <div class="mt-4 p-2.5 rounded-xl bg-purple-950/20 border border-purple-500/10 flex items-center gap-2">
                        <Shield class="h-3.5 w-3.5 text-purple-400 shrink-0" :stroke-width="2.5" />
                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Épargne sécurisée ARM Holdings PLC Vault</span>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PRODUITS AVIP (AVIP PRODUCTS) -->
            <div v-else-if="activeTab === 'avips'" class="space-y-4" data-stagger>
                <div v-if="!userAvips || userAvips.length === 0" class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-10 text-center shadow-2xl backdrop-blur-sm">
                    <Zap class="h-10 w-10 text-purple-400/20 mx-auto mb-3.5 animate-pulse" :stroke-width="2.5" />
                    <p class="text-xs font-black text-white uppercase tracking-wider mb-2">Aucun équipement AVIP actif</p>
                    <p class="text-[10px] text-slate-400 leading-relaxed max-w-xs mx-auto mb-5">Achetez un équipement d'accélération AVIP supérieur pour débloquer des salaires journaliers massifs.</p>
                    <Link 
                        href="/dashboard"
                        class="inline-flex py-2.5 px-6 rounded-xl bg-purple-500 text-black font-extrabold text-[10px] uppercase tracking-wider shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-all duration-300"
                    >
                        Marché AVIP
                    </Link>
                </div>

                <div 
                    v-for="(avip, idx) in userAvips" :key="avip.id"
                    data-animate="fade-up" :data-delay="String(idx * 100)"
                    class="bg-gradient-to-b from-[#0a0f1d]/90 to-[#070b14]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative overflow-hidden group hover:border-purple-400/30 transition-all duration-300 card-hover-lift"
                >
                    <!-- AVIP image background indicator -->
                    <div class="absolute right-0 top-0 w-32 h-32 opacity-10 pointer-events-none transition-transform duration-500 group-hover:scale-110">
                        <img 
                            :src="resolveImageUrl(avip.avip_product?.image)" 
                            @error="(e: Event) => ((e.target as HTMLImageElement).src = '/images/cyber_server_hero.png')"
                            class="w-full h-full object-contain" 
                            alt=""
                        />
                    </div>

                    <div class="flex items-center gap-3 pb-3.5 border-b border-purple-500/10">
                        <div class="w-12 h-12 rounded-2xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center shrink-0">
                            <Zap class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                        </div>
                        <div>
                            <div class="text-xs font-black text-white uppercase tracking-wider">{{ avip.avip_product ? avip.avip_product.name : 'Équipement AVIP' }}</div>
                            <div class="flex items-center gap-1.5 mt-1">
                                <span class="text-[8px] font-black bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 px-1.5 py-0.5 rounded uppercase">
                                    Rang AVIP {{ avip.avip_product?.avip_level }}
                                </span>
                                <span class="text-[8px] font-black px-1.5 py-0.5 rounded uppercase"
                                    :class="avip.active ? 'bg-emerald-500/10 border border-emerald-500/20 text-emerald-400' : 'bg-rose-500/10 border border-rose-500/20 text-rose-400'"
                                >
                                    {{ avip.active ? 'Actif' : 'Expiré' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mt-4 text-xs font-mono">
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Coins class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Coût d'Acquisition
                            </span>
                            <span class="text-xs font-black text-white">{{ formatXAF(avip.amount) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <TrendingUp class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Salaire Quotidien
                            </span>
                            <span class="text-xs font-black text-emerald-400">+{{ formatXAF(avip.avip_product ? avip.avip_product.daily_salary : 0) }}/j</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Calendar class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Date d'Acquisition
                            </span>
                            <span class="text-xs font-black text-white">{{ formatDate(avip.created_at) }}</span>
                        </div>
                        <div class="flex flex-col gap-1 pl-3 border-l border-white/5">
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                <Clock class="h-3 w-3 text-purple-400" :stroke-width="2.5" />
                                Expiration
                            </span>
                            <span class="text-xs font-black text-purple-400 font-bold">{{ avip.expires_at ? formatDate(avip.expires_at) : 'Illimité' }}</span>
                        </div>
                    </div>

                    <!-- Footnote security -->
                    <div class="mt-4 p-2.5 rounded-xl bg-purple-950/20 border border-purple-500/10 flex items-center gap-2">
                        <Shield class="h-3.5 w-3.5 text-purple-400 shrink-0" :stroke-width="2.5" />
                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">Calcul de salaire prioritaire crypté ARM AVIP</span>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
