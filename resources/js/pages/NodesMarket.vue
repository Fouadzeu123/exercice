<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Cpu, 
    Clock, 
    ArrowUpRight, 
    ShieldAlert,
    BrainCircuit,
    Globe
} from 'lucide-vue-next';
import { t, currentLocale, toggleLocale } from '@/utils/trans';

// Define Props from backend controller
const props = defineProps<{
    nodes: Array<{
        id: number;
        name: string;
        amount: string;
        generation_profit: string;
        technology_level: number;
        duration: number;
        stock_quantity: number | null;
        limited_purchase_count: number | null;
        image: string | null;
    }>;
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
    { title: t('marché des nœuds', 'nodes market'), href: '/nodes' },
];

const page = usePage();
const user = computed(() => page.props.auth.user);

const selectedNode = ref<any | null>(null);
const showConfirmModal = ref(false);
const showErrorModal = ref(false);
const errorMessage = ref('');

const activeCategory = ref('Offres VIP');
const filteredNodes = computed(() => {
    if (activeCategory.value === 'Offres VIP') {
        return props.nodes.filter(n => n.technology_level <= 3);
    } else if (activeCategory.value === 'Offres AVIP') {
        return props.nodes.filter(n => n.technology_level === 4 || n.technology_level === 5);
    } else if (activeCategory.value === 'Produits Limités') {
        return props.nodes.filter(n => n.stock_quantity !== null && n.stock_quantity <= 12500);
    }
    return props.nodes;
});

const form = useForm({});

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const isCurrentNode = (nodeId: number) => {
    return props.activeUserNode && props.activeUserNode.node_id === nodeId;
};

const getNodeStatus = (node: any) => {
    if (!props.activeUserNode) return 'available';
    if (props.activeUserNode.node_id === node.id) return 'active';
    if (node.technology_level > props.activeUserNode.technology_level) return 'upgrade';
    return 'locked';
};

const selectNodeForRent = (node: any) => {
    const status = getNodeStatus(node);
    if (status === 'locked' || status === 'active') return;
    
    router.visit(`/products/node/${node.id}`);
};

const confirmRent = () => {
    if (!selectedNode.value) return;
    
    form.post(`/nodes/${selectedNode.value.id}/rent`, {
        onSuccess: () => {
            showConfirmModal.value = false;
            selectedNode.value = null;
        },
        onError: (errors) => {
            showConfirmModal.value = false;
            errorMessage.value = errors.error || t('Erreur lors de la location du nœud.', 'Error during node rental.');
            showErrorModal.value = true;
        }
    });
};

// Technology graphics images from screenshot mockup
const nodeImages = [
    'https://images.unsplash.com/photo-1591453089816-0fbb971b454c?auto=format&fit=crop&w=600&q=80', // RTX graphics card style
    'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1563770660941-20978e870e26?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1677442136019-21780efad99a?auto=format&fit=crop&w=600&q=80'
];

const getNodeImage = (id: number) => {
    return nodeImages[id % nodeImages.length];
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head :title="t('Marché des Nœuds', 'Nodes Market')" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-md mx-auto flex flex-col gap-4 pt-3 pb-24 text-white">
            
            <!-- Welcome Header -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-[#0c0f1d] p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div>
                    <h2 class="text-sm font-black text-white uppercase tracking-wider">
                        {{ t('Marché des Nœuds', 'Nodes Market') }}
                    </h2>
                    <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">
                        {{ t('Louez de la puissance GPU de calcul', 'Rent high-performance GPU computing') }}
                    </p>
                </div>
                
                <div class="flex items-center gap-2">
                    <!-- Language selector -->
                    <div 
                        @click="toggleLocale"
                        class="border border-white/10 bg-white/5 px-2.5 py-1 rounded-xl text-[9px] font-bold tracking-wide flex items-center gap-1.5 hover:border-purple-400/50 transition-colors cursor-pointer uppercase select-none"
                    >
                        <Globe class="h-3.5 w-3.5 text-purple-400" :stroke-width="2.5" />
                        <span>{{ currentLocale }}</span>
                    </div>

                    <div class="bg-purple-500/15 border border-purple-500/20 px-3 py-1.5 rounded-xl text-[10px] font-mono font-black text-purple-400">
                        {{ formatXAF(user?.balance || 0) }}
                    </div>
                </div>
            </div>

            <!-- Active User Node Banner (if any) -->
            <div v-if="activeUserNode" data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0c0f1d]/90 to-[#070b14]/90 rounded-2xl p-4 border border-purple-500/20 flex justify-between items-center gap-3 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center">
                        <Cpu class="h-5 w-5" :stroke-width="2.5" />
                    </div>
                    <div>
                        <span class="text-[8px] font-black text-purple-400 tracking-wide uppercase px-2 py-0.5 rounded-full bg-purple-500/10 border border-purple-500/20">
                            {{ t('ACTIF ACTUELLEMENT', 'CURRENTLY RUNNING') }}
                        </span>
                        <h4 class="text-xs font-black text-white mt-1 leading-tight">{{ activeUserNode.node_name }}</h4>
                    </div>
                </div>
            </div>

            <!-- CATEGORY BUTTONS TABS -->
            <div data-animate="fade-up" data-delay="150" class="flex bg-black/40 border border-white/5 p-1 rounded-xl w-full gap-1 mb-2 mt-1">
                <button 
                    v-for="cat in ['Offres VIP', 'Offres AVIP', 'Produits Limités']" 
                    :key="cat"
                    @click="activeCategory = cat"
                    class="flex-1 py-2 rounded-lg text-[9px] font-black uppercase tracking-wider transition-all duration-300"
                    :class="activeCategory === cat ? 'bg-gradient-to-r from-purple-500 to-fuchsia-600 text-black font-extrabold shadow-[0_0_12px_rgba(168,85,247,0.3)]' : 'text-slate-500 hover:text-white'"
                >
                    {{ t(cat, cat) }}
                </button>
            </div>

            <!-- Nodes Marketplace Cards List (Mockup layout match) -->
            <div data-stagger="true" class="space-y-5">
                <div 
                    v-for="node in filteredNodes" 
                    data-animate="fade-up"
                    :key="node.id"
                    class="group relative bg-[#090b15] border border-purple-500/20 rounded-3xl overflow-hidden hover:border-purple-400 transition-all duration-300 shadow-lg hover:shadow-purple-500/10 flex flex-col justify-between cursor-pointer"
                    @click="selectNodeForRent(node)"
                    :class="{ 
                        'border-purple-400 shadow-[0_0_15px_rgba(168,85,247,0.15)] bg-purple-950/[0.01]': isCurrentNode(node.id),
                        'opacity-50 pointer-events-none filter grayscale': getNodeStatus(node) === 'locked'
                    }"
                >
                    <!-- Top Header Info -->
                    <div class="p-3 bg-[#0c0f1d] flex items-center justify-between border-b border-white/5">
                        <span class="text-[10px] font-black text-white uppercase tracking-wider truncate max-w-[70%]">
                            {{ t('Location Carte Unique ' + node.name, 'Rental Single Card ' + node.name) }}
                        </span>
                        <!-- Duration Badge -->
                        <span class="text-[9px] font-black bg-purple-400 text-black px-3 py-1 rounded-lg uppercase tracking-wider">
                            {{ node.duration }} {{ t('Jours', 'Days') }}
                        </span>
                    </div>

                    <!-- GPU Image Area -->
                    <div class="w-full h-44 overflow-hidden relative bg-black/20">
                        <img 
                            :src="node.image || getNodeImage(node.id)" 
                            :alt="node.name" 
                            class="w-full h-full object-cover opacity-85 group-hover:opacity-100 transition-all duration-500 group-hover:scale-105"
                            loading="lazy"
                        />
                    </div>

                    <!-- Brand Purple Banner (Premium ARM design) -->
                    <div class="bg-gradient-to-r from-purple-600 via-indigo-600 to-purple-800 px-4 py-2.5 flex items-center gap-3 justify-between text-white">
                        <div class="flex items-center gap-1.5 shrink-0">
                            <img src="/images/logo.jpg" class="h-4.5 w-4.5 rounded object-cover border border-white/20 shrink-0 shadow-sm" alt="Logo" />
                            <div class="bg-black/40 text-purple-200 font-extrabold text-[7px] px-1 py-0.5 rounded border border-purple-400/20 leading-none shrink-0 tracking-tighter font-mono">AI CPU</div>
                        </div>
                        <div class="text-xs font-black text-white uppercase tracking-wider truncate text-right flex-1">
                            {{ node.name.replace(/Location\s+/gi, '').replace(/Carte\s+Unique\s+/gi, '').toUpperCase() }}
                        </div>
                    </div>

                    <!-- Stats Grid 2x2 with borders -->
                    <div class="grid grid-cols-2 bg-[#0c0f1d]/50">
                        <!-- Stock -->
                        <div class="p-3 border-r border-b border-white/5 flex items-center justify-between gap-1 text-[9px] uppercase tracking-wider">
                            <span class="text-slate-400 font-bold text-[9px]">{{ t('Qté en stock:', 'Stock Qty:') }}</span>
                            <span class="text-purple-400 font-black font-mono text-[9px]">{{ node.stock_quantity ?? '12018' }}</span>
                        </div>
                        <!-- Purchase Limit -->
                        <div class="p-3 border-b border-white/5 flex items-center justify-between gap-1 text-[9px] uppercase tracking-wider">
                            <span class="text-slate-400 font-bold text-[9px]">{{ t("Lim. d'achat:", 'Limit:') }}</span>
                            <span class="text-purple-400 font-black font-mono text-[9px]">{{ node.limited_purchase_count ?? 0 }}</span>
                        </div>
                        <!-- Total Revenue -->
                        <div class="p-3 border-r border-white/5 flex flex-col gap-1">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">{{ t('Revenu total', 'Total revenue') }}</span>
                            <span class="text-[11px] font-black text-emerald-400 font-mono">{{ formatXAF(parseFloat(node.generation_profit) * node.duration) }}</span>
                        </div>
                        <!-- Rental Price -->
                        <div class="p-3 flex flex-col gap-1">
                            <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">{{ t('Montant location', 'Rental fee') }}</span>
                            <span class="text-[11px] font-black text-yellow-400 font-mono">{{ formatXAF(node.amount) }}</span>
                        </div>
                    </div>

                    <!-- Action RENT trigger buttons under grid -->
                    <div class="p-4 bg-[#0a0d18] flex flex-col gap-2">
                        <button 
                            v-if="getNodeStatus(node) === 'active'"
                            disabled
                            class="w-full py-3 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 font-extrabold text-[10px] uppercase tracking-wider"
                        >
                            {{ t('nœud actif actuellement', 'currently running node') }}
                        </button>
                        
                        <button 
                            v-else-if="getNodeStatus(node) === 'locked'"
                            disabled
                            class="w-full py-3 rounded-xl bg-[#141829] text-slate-600 font-extrabold text-[10px] uppercase tracking-wider border border-white/5"
                        >
                            {{ t('serveur verrouillé', 'locked server') }}
                        </button>

                        <button 
                            v-else-if="getNodeStatus(node) === 'upgrade'"
                            @click="selectNodeForRent(node)"
                            class="w-full py-3 rounded-xl bg-purple-500 text-black hover:bg-purple-400 font-extrabold text-[10px] uppercase tracking-widest flex items-center justify-center gap-1.5 shadow-[0_0_15px_rgba(168,85,247,0.3)] transition-all"
                        >
                            <ArrowUpRight class="h-4 w-4" :stroke-width="2.5" />
                            {{ t('mettre à niveau', 'upgrade server') }}
                        </button>

                        <button 
                            v-else
                            @click="selectNodeForRent(node)"
                            class="w-full py-3 rounded-xl bg-purple-500 text-black hover:bg-purple-400 font-extrabold text-[10px] uppercase tracking-widest shadow-[0_0_15px_rgba(168,85,247,0.3)] transition-all"
                        >
                            {{ t("louer l'infrastructure", 'rent infrastructure') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Confirm Rental Modal -->
            <div v-if="showConfirmModal && selectedNode" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-fadeIn">
                <div class="bg-[#0f111a] border border-purple-500/30 w-full max-w-sm rounded-3xl p-6 relative overflow-hidden shadow-2xl">
                    <h3 class="text-xs font-black text-white uppercase tracking-wider mb-4 flex items-center gap-2 border-b border-purple-500/10 pb-3">
                        <Cpu class="h-5 w-5 text-purple-400 animate-pulse" :stroke-width="2.5" />
                        {{ selectedNode.name }}
                    </h3>

                    <!-- Details Node -->
                    <div class="border border-[#141b2f] bg-black/40 p-4 rounded-2xl mb-4 text-xs font-mono">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <span class="text-[9px] text-slate-500 font-bold uppercase block">{{ t('Coût Contrat', 'Contract cost') }}</span>
                                <span class="font-bold text-white font-mono mt-0.5 block">{{ formatXAF(selectedNode.amount) }}</span>
                            </div>
                            <div>
                                <span class="text-[9px] text-slate-500 font-bold uppercase block">{{ t('Gains Génération', 'Generation gains') }}</span>
                                <span class="font-bold text-emerald-400 font-mono mt-0.5 block">+{{ formatXAF(selectedNode.generation_profit) }} / session</span>
                            </div>
                        </div>
                    </div>

                    <!-- Upgrade Refund Info Box if upgrading -->
                    <div v-if="activeUserNode && getNodeStatus(selectedNode) === 'upgrade'" class="p-3.5 rounded-xl bg-purple-950/20 border border-purple-500/20 flex items-start gap-2.5 mb-4 text-[10px]">
                        <ShieldAlert class="h-4.5 w-4.5 text-purple-400 shrink-0 mt-0.5" :stroke-width="2.5" />
                        <div>
                            <span class="font-bold text-purple-400 block">{{ t('Remboursement de mise à niveau', 'Upgrade refund') }}</span>
                            <p class="text-[9px] text-slate-400 mt-0.5 leading-relaxed">
                                {{ t('La location d\'un nœud supérieur résilie automatiquement votre nœud actif et recrédite sa valeur d\'acquisition dans votre solde.', 'Renting a superior node automatically cancels your active node and refunds its acquisition cost to your balance.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Balance validation warning -->
                    <div class="flex justify-between items-center text-[10px] border-t border-purple-500/10 pt-4 mb-5">
                        <span class="text-slate-400 font-bold uppercase">{{ t('Solde disponible', 'Available balance') }} :</span>
                        <span class="font-black text-white font-mono">{{ formatXAF(user?.balance || 0) }}</span>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex gap-3">
                        <button 
                            @click="showConfirmModal = false"
                            class="flex-1 py-3 rounded-xl border border-white/5 text-white font-bold text-xs uppercase hover:bg-white/5 transition-colors"
                        >
                            {{ t('annuler', 'cancel') }}
                        </button>
                        <button 
                            @click="confirmRent"
                            class="flex-1 py-3 rounded-xl bg-purple-500 text-black font-extrabold text-xs uppercase shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:bg-purple-400 transition-colors"
                        >
                            {{ t('confirmer location', 'confirm rental') }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Error Modal -->
            <div v-if="showErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/95 backdrop-blur-sm">
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

        </div>
    </AppLayout>
</template>
