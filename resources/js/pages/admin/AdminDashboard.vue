<script setup lang="ts">
import { ref, computed, watch, onUnmounted, onMounted } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/layouts/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    ShieldAlert, 
    Check, 
    X, 
    Users, 
    Wallet, 
    TrendingUp, 
    Clock, 
    Search,
    AlertTriangle,
    Zap,
    Cpu,
    Settings,
    CheckCircle2,
    Trash2,
    Plus,
    Edit3,
    Unlock,
    Lock,
    PlusCircle,
    Activity,
    Gift,
    Megaphone as MegaphoneIcon,
    Coins
} from 'lucide-vue-next';

// Props passed from backend AdminController
const props = defineProps<{
    pendingTransactions: Array<{
        id: number;
        user_id: number;
        amount: string;
        type: string;
        status: string;
        reference: string;
        created_at: string;
        payment_method?: string | null;
        payment_phone?: string | null;
        user: {
            id: number;
            phone: string;
            balance: string;
        };
    }>;
    users: Array<{
        id: number;
        phone: string;
        balance: string;
        vip_level: number;
        avip_level: number;
        role: string;
        draw_spins: number;
        next_spin_prize_index: number | null;
        created_at: string;
    }>;
    giftCodes: Array<{
        id: number;
        code: string;
        amount: string;
        max_usages: number;
        usages: number;
        created_at: string;
    }>;
    nodes: Array<{
        id: number;
        name: string;
        amount: string;
        generation_profit: string;
        referral_reward: string;
        technology_level: number;
        duration: number;
        stock_quantity: number | null;
        limited_purchase_count: number | null;
        active: boolean;
        deleted_at: string | null;
    }>;
    avipProducts: Array<{
        id: number;
        name: string;
        description: string;
        amount: string;
        daily_salary: string;
        referral_reward: string;
        required_vip_level: number;
        avip_level: number;
        active: boolean;
        deleted_at: string | null;
    }>;
    vaultPlans: Array<{
        id: number;
        name: string;
        fixed_investment_amount: string;
        fixed_return: string;
        profit_amount: string;
        duration: number;
        payout_type: string;
        active: boolean;
        image: string | null;
        created_at: string;
    }>;
    announcements: Array<{
        id: number;
        title: string;
        content: string;
        image_url: string | null;
        link: string | null;
        active: boolean;
        created_at: string;
    }>;
    settings: {
        vip_salaries: Record<number, number>;
        min_deposit: number;
        min_withdrawal: number;
        support_telegram: string;
        support_whatsapp: string;
        lucky_draw_cost: number;
        generation_duration: number;
    };
    metrics: {
        total_deposits: number;
        total_withdrawals: number;
        active_nodes_count: number;
        total_users_count: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Console de Contrôle Admin', href: '/admin' },
];

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' XAF';
};

const getObjectUrl = (file: File | null | undefined) => {
    if (!file) return '';
    try {
        return URL.createObjectURL(file);
    } catch (e) {
        return '';
    }
};

// Tabs: 'transactions' | 'users' | 'giftcodes' | 'products' | 'announcements' | 'settings'
const activeTab = ref('transactions');
const searchQuery = ref('');

// Set active tab from query parameter on mount/URL change
const syncTabWithUrl = () => {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam && ['transactions', 'users', 'giftcodes', 'products', 'announcements', 'settings'].includes(tabParam)) {
        activeTab.value = tabParam;
    }
};

onMounted(() => {
    syncTabWithUrl();
});

// Watch Inertia page URL to reactively sync tab changes
const page = usePage();
watch(() => page.url, () => {
    syncTabWithUrl();
});

// Filtered Users list
const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.users;
    const query = searchQuery.value.toLowerCase();
    return props.users.filter(u => 
        u.phone.toLowerCase().includes(query) || 
        u.vip_level.toString().includes(query) ||
        u.role.toLowerCase().includes(query)
    );
});

// Toast / Logs Notification
const successMsg = ref('');
const errorMsg = ref('');

const showToast = (message: string, isError = false) => {
    if (isError) {
        errorMsg.value = message;
        setTimeout(() => errorMsg.value = '', 5000);
    } else {
        successMsg.value = message;
        setTimeout(() => successMsg.value = '', 5000);
    }
};

// TRANSACTION APPROVAL/REJECTION FORMS
const txForm = useForm({});
const handleApproveTx = (id: number) => {
    txForm.post(`/admin/transaction/${id}/approve`, {
        onSuccess: () => showToast("La transaction financière a été signée et approuvée."),
        onError: (err: any) => showToast(err.error || "Erreur de validation", true)
    });
};

const handleRejectTx = (id: number) => {
    txForm.post(`/admin/transaction/${id}/reject`, {
        onSuccess: () => showToast("La transaction a été rejetée et les fonds restaurés."),
        onError: (err: any) => showToast(err.error || "Erreur de rejet", true)
    });
};

// USER MANAGEMENT MODAL STATE
const selectedUser = ref<any | null>(null);
const userEditForm = useForm({
    balance: 0,
    role: 'user',
    vip_level: 0,
    avip_level: 0,
    draw_spins: 0,
    next_spin_prize_index: null as number | null | string,
});

const openUserModal = (user: any) => {
    selectedUser.value = user;
    userEditForm.balance = parseFloat(user.balance);
    userEditForm.role = user.role;
    userEditForm.vip_level = user.vip_level;
    userEditForm.avip_level = user.avip_level;
    userEditForm.draw_spins = user.draw_spins;
    userEditForm.next_spin_prize_index = user.next_spin_prize_index;
};

const handleUpdateUser = () => {
    if (!selectedUser.value) return;
    
    // Normalize next_spin_prize_index
    let rigIndex = userEditForm.next_spin_prize_index;
    if (rigIndex === 'null' || rigIndex === '' || rigIndex === null) {
        rigIndex = null;
    } else {
        rigIndex = parseInt(rigIndex.toString());
    }

    userEditForm.transform((data) => ({
        ...data,
        next_spin_prize_index: rigIndex
    })).post(`/admin/user/${selectedUser.value.id}/update`, {
        onSuccess: () => {
            selectedUser.value = null;
            showToast("Profil et variables système du mineur modifiés.");
        },
        onError: (err: any) => showToast(err.error || "Erreur lors de la mise à jour", true)
    });
};

const handleDeleteUser = (id: number) => {
    if (!confirm("Voulez-vous vraiment bannir et supprimer définitivement ce compte utilisateur de l'écosystème ?")) return;
    router.delete(`/admin/user/${id}/delete`, {
        onSuccess: () => {
            selectedUser.value = null;
            showToast("L'utilisateur a été banni.");
        },
        onError: (err: any) => showToast(err.error || "Échec du bannissement", true)
    });
};

// DYNAMIC GIFT CODE GENERATION FORM
const giftCodeForm = useForm({
    code: '',
    amount: 1000,
    max_usages: 10,
});

const handleCreateGiftCode = () => {
    giftCodeForm.post('/admin/gift-code', {
        onSuccess: () => {
            giftCodeForm.reset();
            showToast("Le code cadeau a été généré avec succès en base de données.");
        },
        onError: (err: any) => showToast(err.code || err.amount || err.max_usages || "Erreur de génération", true)
    });
};

const handleDeleteGiftCode = (id: number) => {
    if (!confirm("Supprimer ce code cadeau ? Les utilisateurs ne pourront plus le réclamer.")) return;
    router.delete(`/admin/gift-code/${id}`, {
        onSuccess: () => showToast("Code cadeau supprimé."),
        onError: (err: any) => showToast("Échec de suppression", true)
    });
};

// PRODUCT CATALOG CONFIGURATION MODALS
const selectedNode = ref<any | null>(null);
const nodeForm = useForm({
    name: '',
    amount: 0,
    generation_profit: 0,
    referral_reward: 0,
    technology_level: 0,
    duration: 30,
    stock_quantity: null as number | null,
    limited_purchase_count: null as number | null,
    active: true,
    is_limited: false,
    required_active_referrals: 0,
    restore: false,
    image_url: '',
    image_file: null as File | null,
});

const openNodeModal = (node: any) => {
    selectedNode.value = node;
    nodeForm.name = node.name;
    nodeForm.amount = parseFloat(node.amount);
    nodeForm.generation_profit = parseFloat(node.generation_profit);
    nodeForm.referral_reward = parseFloat(node.referral_reward || 0);
    nodeForm.technology_level = node.technology_level;
    nodeForm.duration = node.duration;
    nodeForm.stock_quantity = node.stock_quantity;
    nodeForm.limited_purchase_count = node.limited_purchase_count;
    nodeForm.active = !!node.active;
    nodeForm.is_limited = !!node.is_limited;
    nodeForm.required_active_referrals = node.required_active_referrals || 0;
    nodeForm.restore = false;
    nodeForm.image_url = node.image_url || '';
    nodeForm.image_file = null;
};

const handleUpdateNode = () => {
    if (!selectedNode.value) return;
    nodeForm.post(`/admin/node/${selectedNode.value.id}/update`, {
        onSuccess: () => {
            selectedNode.value = null;
            showToast("Nœud standard configuré avec succès.");
        },
        onError: (err: any) => showToast("Une erreur est survenue.", true)
    });
};

const handleDeleteNode = (id: number) => {
    if (!confirm("Voulez-vous supprimer logiquement ce serveur standard ? Ses offres seront masquées mais les commandes en cours persisteront.")) return;
    router.delete(`/admin/node/${id}/delete`, {
        onSuccess: () => {
            selectedNode.value = null;
            showToast("Nœud serveur supprimé logiquement (Soft Delete).");
        },
        onError: (err: any) => showToast("Échec de suppression", true)
    });
};

// AVIP PRODUCT CONFIGURATION MODALS
const selectedAvip = ref<any | null>(null);
const avipForm = useForm({
    name: '',
    description: '',
    amount: 0,
    daily_salary: 0,
    referral_reward: 0,
    required_vip_level: 1,
    avip_level: 1,
    duration: 7,
    stock_quantity: null as number | null,
    limited_purchase_count: null as number | null,
    active: true,
    is_limited: false,
    required_active_referrals: 0,
    restore: false,
    image: '',
    image_file: null as File | null,
});

const openAvipModal = (product: any) => {
    selectedAvip.value = product;
    avipForm.name = product.name;
    avipForm.description = product.description;
    avipForm.amount = parseFloat(product.amount);
    avipForm.daily_salary = parseFloat(product.daily_salary);
    avipForm.referral_reward = parseFloat(product.referral_reward || 0);
    avipForm.required_vip_level = product.required_vip_level;
    avipForm.avip_level = product.avip_level;
    avipForm.duration = product.duration || 7;
    avipForm.stock_quantity = product.stock_quantity !== null ? parseInt(product.stock_quantity) : null;
    avipForm.limited_purchase_count = product.limited_purchase_count !== null ? parseInt(product.limited_purchase_count) : null;
    avipForm.active = !!product.active;
    avipForm.is_limited = !!product.is_limited;
    avipForm.required_active_referrals = product.required_active_referrals || 0;
    avipForm.restore = false;
    avipForm.image = product.image || '';
    avipForm.image_file = null;
};

const handleUpdateAvip = () => {
    if (!selectedAvip.value) return;
    avipForm.post(`/admin/avip-product/${selectedAvip.value.id}/update`, {
        onSuccess: () => {
            selectedAvip.value = null;
            showToast("Équipement AVIP configuré avec succès.");
        },
        onError: (err: any) => showToast("Une erreur est survenue.", true)
    });
};

const handleDeleteAvip = (id: number) => {
    if (!confirm("Voulez-vous supprimer logiquement ce accélérateur AVIP ? Ses offres seront masquées mais les commandes en cours persisteront.")) return;
    router.delete(`/admin/avip-product/${id}/delete`, {
        onSuccess: () => {
            selectedAvip.value = null;
            showToast("Accélérateur AVIP supprimé logiquement (Soft Delete).");
        },
        onError: (err: any) => showToast("Échec de suppression", true)
    });
};

// CREATE NODE STATE & FORM
const showCreateNodeModal = ref(false);
const createNodeForm = useForm({
    name: '',
    amount: 10000,
    generation_profit: 500,
    referral_reward: 0,
    technology_level: 0,
    duration: 30,
    stock_quantity: null as number | null,
    limited_purchase_count: null as number | null,
    active: true,
    is_limited: false,
    required_active_referrals: 0,
    image_url: '',
    image_file: null as File | null,
});

const handleCreateNode = () => {
    createNodeForm.post('/admin/node', {
        onSuccess: () => {
            showCreateNodeModal.value = false;
            createNodeForm.reset();
            showToast("Nouveau nœud de serveur standard créé avec succès.");
        },
        onError: (err: any) => showToast("Erreur lors de la création du serveur.", true)
    });
};

// CREATE AVIP STATE & FORM
const showCreateAvipModal = ref(false);
const createAvipForm = useForm({
    name: '',
    description: '',
    amount: 50000,
    daily_salary: 2000,
    referral_reward: 0,
    required_vip_level: 1,
    avip_level: 1,
    duration: 7,
    stock_quantity: null as number | null,
    limited_purchase_count: null as number | null,
    active: true,
    is_limited: false,
    required_active_referrals: 0,
    image: '',
    image_file: null as File | null,
});

const handleCreateAvip = () => {
    createAvipForm.post('/admin/avip-product', {
        onSuccess: () => {
            showCreateAvipModal.value = false;
            createAvipForm.reset();
            showToast("Nouvel équipement AVIP créé avec succès.");
        },
        onError: (err: any) => showToast("Erreur lors de la création de l'AVIP.", true)
    });
};

// VAULT PLAN CONFIGURATION MODALS & CRUD
const selectedVault = ref<any | null>(null);
const showCreateVaultModal = ref(false);

const vaultForm = useForm({
    name: '',
    fixed_investment_amount: 0,
    fixed_return: 0,
    duration: 30,
    payout_type: 'on_expiration',
    active: true,
    image_url: '',
    image_file: null as File | null,
});

const openVaultModal = (vault: any) => {
    selectedVault.value = vault;
    vaultForm.name = vault.name;
    vaultForm.fixed_investment_amount = parseFloat(vault.fixed_investment_amount);
    vaultForm.fixed_return = parseFloat(vault.fixed_return);
    vaultForm.duration = vault.duration;
    vaultForm.payout_type = vault.payout_type;
    vaultForm.active = !!vault.active;
    vaultForm.image_url = vault.image || '';
    vaultForm.image_file = null;
};

const handleUpdateVault = () => {
    if (!selectedVault.value) return;
    vaultForm.post(`/admin/vault-plan/${selectedVault.value.id}/update`, {
        onSuccess: () => {
            selectedVault.value = null;
            showToast("Produit de coffre-fort (Vault Plan) mis à jour avec succès.");
        },
        onError: (err: any) => showToast("Une erreur est survenue lors de la mise à jour.", true)
    });
};

const handleDeleteVault = (id: number) => {
    if (!confirm("Voulez-vous vraiment supprimer définitivement ce produit de coffre-fort ? Ses offres seront masquées mais les investissements actifs persisteront.")) return;
    router.delete(`/admin/vault-plan/${id}/delete`, {
        onSuccess: () => {
            selectedVault.value = null;
            showToast("Produit de coffre-fort (Vault Plan) supprimé.");
        },
        onError: (err: any) => showToast("Échec de suppression", true)
    });
};

const createVaultForm = useForm({
    name: '',
    fixed_investment_amount: 10000,
    fixed_return: 15000,
    duration: 30,
    payout_type: 'on_expiration',
    active: true,
    image_url: '',
    image_file: null as File | null,
});

const handleCreateVault = () => {
    createVaultForm.post('/admin/vault-plan', {
        onSuccess: () => {
            showCreateVaultModal.value = false;
            createVaultForm.reset();
            showToast("Nouveau produit de coffre-fort (Vault Plan) créé avec succès.");
        },
        onError: (err: any) => showToast("Erreur lors de la création du coffre-fort.", true)
    });
};

// ANNOUNCEMENT FORM & MODALS
const selectedAnnouncement = ref<any | null>(null);
const showCreateAnnouncementModal = ref(false);

const announcementForm = useForm({
    title: '',
    content: '',
    image_url: '',
    image_file: null as File | null,
    link: '',
    active: true,
});

const openAnnouncementModal = (announcement: any) => {
    selectedAnnouncement.value = announcement;
    announcementForm.title = announcement.title;
    announcementForm.content = announcement.content;
    announcementForm.image_url = announcement.image_url || '';
    announcementForm.image_file = null;
    announcementForm.link = announcement.link || '';
    announcementForm.active = !!announcement.active;
};

const handleCreateAnnouncement = () => {
    announcementForm.post('/admin/announcement', {
        onSuccess: () => {
            showCreateAnnouncementModal.value = false;
            announcementForm.reset();
            showToast("Nouvelle annonce publiée.");
        },
        onError: (err: any) => showToast("Erreur lors de la publication de l'annonce.", true)
    });
};

const handleUpdateAnnouncement = () => {
    if (!selectedAnnouncement.value) return;
    announcementForm.post(`/admin/announcement/${selectedAnnouncement.value.id}/update`, {
        onSuccess: () => {
            selectedAnnouncement.value = null;
            showToast("Annonce mise à jour avec succès.");
        },
        onError: (err: any) => showToast("Erreur de mise à jour.", true)
    });
};

const handleDeleteAnnouncement = (id: number) => {
    if (!confirm("Voulez-vous supprimer cette annonce ?")) return;
    router.delete(`/admin/announcement/${id}/delete`, {
        onSuccess: () => showToast("Annonce supprimée."),
        onError: (err: any) => showToast("Échec de suppression.", true)
    });
};

// SYSTEM CONFIGURATION FORM
const settingsForm = useForm({
    min_deposit: props.settings.min_deposit,
    min_withdrawal: props.settings.min_withdrawal,
    support_telegram: props.settings.support_telegram,
    support_whatsapp: props.settings.support_whatsapp,
    lucky_draw_cost: props.settings.lucky_draw_cost,
    generation_duration: props.settings.generation_duration,
    vip_salaries: { ...props.settings.vip_salaries },
});

const handleUpdateSettings = () => {
    settingsForm.post('/admin/settings', {
        onSuccess: () => showToast("Configuration globale du système enregistrée."),
        onError: (err: any) => showToast("Erreur d'enregistrement des paramètres.", true)
    });
};

// Scroll Lock Watcher
watch([selectedUser, selectedNode, selectedAvip, selectedVault, showCreateNodeModal, showCreateAvipModal, showCreateVaultModal, selectedAnnouncement, showCreateAnnouncementModal], (states) => {
    if (states.some(Boolean)) {
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
    <Head title="Console Administration Premium" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full text-foreground">
            
            <!-- ADMIN MAIN HEADER -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div class="flex items-center gap-3">
                    <div class="h-12 w-12 rounded-xl bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.25)]">
                        <ShieldAlert class="h-6 w-6" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-lg md:text-xl font-black text-white uppercase tracking-wider">Console de Contrôle Global & Sécurité</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Configuration des serveurs, gestion des mineurs et protocoles de triche</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 bg-cyan-950/20 border border-cyan-500/20 px-3.5 py-1.5 rounded-xl">
                    <span class="h-2 w-2 rounded-full bg-cyan-400 animate-ping"></span>
                    <span class="text-[9px] font-black uppercase tracking-widest text-cyan-400 font-mono">SYSOP: ROOT ACTIVE</span>
                </div>
            </div>

            <!-- STATE TOASTS -->
            <div v-if="successMsg" class="p-3 bg-emerald-950/40 border border-emerald-500/25 text-emerald-400 text-xs rounded-xl font-mono flex items-center gap-2 animate-pulse shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-400" />
                <span>[ LOG SUCCESS ] : {{ successMsg }}</span>
            </div>
            <div v-if="errorMsg" class="p-3 bg-rose-950/40 border border-rose-500/25 text-rose-400 text-xs rounded-xl font-mono flex items-center gap-2 animate-pulse shadow-[0_0_10px_rgba(244,63,94,0.1)]">
                <AlertTriangle class="h-4 w-4 shrink-0 text-rose-400" />
                <span>[ LOG ERROR ] : {{ errorMsg }}</span>
            </div>

            <!-- METRICS PANEL GRID -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- Pending transactions -->
                <div class="glass relative overflow-hidden rounded-2xl p-5 border border-purple-500/20 bg-purple-950/[0.02]">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-purple-500/5 rounded-full blur-2xl"></div>
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-purple-400/80">Flux en Attente</span>
                        <div class="p-1.5 rounded-lg bg-purple-500/10 border border-purple-500/25 text-purple-400">
                            <Clock class="h-4 w-4" />
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-white tracking-tight font-mono">
                        {{ pendingTransactions.length }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Transactions financières en attente de visa.</p>
                </div>

                <!-- Total active miners -->
                <div class="glass relative overflow-hidden rounded-2xl p-5 border border-white/5">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Mineurs Connectés</span>
                        <div class="p-1.5 rounded-lg bg-white/5 border border-white/10 text-slate-400">
                            <Users class="h-4 w-4" />
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-white tracking-tight font-mono">
                        {{ metrics.total_users_count }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Total des comptes enregistrés sur ARM.</p>
                </div>

                <!-- Deposits volume -->
                <div class="glass relative overflow-hidden rounded-2xl p-5 border border-white/5">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-slate-400">Dépôts Capitalisés</span>
                        <div class="p-1.5 rounded-lg bg-white/5 border border-white/10 text-slate-400">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-white tracking-tight font-mono truncate">
                        {{ formatXAF(metrics.total_deposits) }}
                    </h3>
                    <p class="text-[10px] text-slate-400 mt-1">Volume de dépôts validés cumulés.</p>
                </div>

                <!-- Active infrastructure servers -->
                <div class="glass relative overflow-hidden rounded-2xl p-5 border border-emerald-500/20 bg-emerald-500/[0.01]">
                    <div class="flex justify-between items-center mb-3">
                        <span class="text-[9px] font-extrabold uppercase tracking-widest text-emerald-400">Serveurs Actifs</span>
                        <div class="p-1.5 rounded-lg bg-emerald-500/10 border border-emerald-500/25 text-emerald-400 animate-pulse">
                            <Activity class="h-4 w-4" />
                        </div>
                    </div>
                    <h3 class="text-3xl font-black text-white tracking-tight font-mono">
                        {{ metrics.active_nodes_count }}
                    </h3>
                    <p class="text-[10px] text-emerald-400 font-mono mt-1">[ INFRASTRUCTURE STABLE ]</p>
                </div>
            </div>

            <!-- TABS NAVIGATION BAR -->
            <div class="flex flex-col md:flex-row gap-4 justify-between items-stretch md:items-center mt-2 border-b border-white/5 pb-4">
                <div class="flex flex-wrap bg-black/40 border border-white/5 p-1 rounded-xl w-fit">
                    <button 
                        @click="activeTab = 'transactions'" 
                        class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'transactions' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'"
                    >
                        Transactions ({{ pendingTransactions.length }})
                    </button>
                    <button 
                        @click="activeTab = 'users'" 
                        class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'users' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'"
                    >
                        Registre Utilisateurs ({{ users.length }})
                    </button>
                    <button 
                        @click="activeTab = 'giftcodes'" 
                        class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'giftcodes' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'"
                    >
                        Codes Cadeaux ({{ giftCodes.length }})
                    </button>
                    <button 
                        @click="activeTab = 'products'" 
                        class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'products' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'"
                    >
                        Catalogue Produits ({{ nodes.length + avipProducts.length }})
                    </button>
                    <button 
                        @click="activeTab = 'announcements'" 
                        class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'announcements' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'"
                    >
                        Annonces ({{ announcements.length }})
                    </button>
                    <button 
                        @click="activeTab = 'settings'" 
                        class="px-4 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider transition-all"
                        :class="activeTab === 'settings' ? 'bg-gradient-to-r from-purple-600 to-cyan-500 text-white shadow-[0_0_12px_rgba(6,182,212,0.4)]' : 'text-slate-400 hover:text-white'"
                    >
                        Paramètres Système
                    </button>
                </div>

                <!-- Live search bar for users -->
                <div v-if="activeTab === 'users'" class="relative max-w-xs w-full">
                    <Search class="h-3.5 w-3.5 text-slate-500 absolute left-3 top-2.5" />
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Filtrer par téléphone..." 
                        class="bg-black/50 border border-white/10 text-white font-mono text-xs pl-9 pr-4 h-9.5 w-full rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500"
                    />
                </div>
            </div>

            <!-- TAB CONTENT 1: TRANSACTION APPROVALS -->
            <div v-if="activeTab === 'transactions'" class="glass rounded-3xl p-5 border border-white/5 bg-black/10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                        <Clock class="h-4.5 w-4.5 text-purple-400" />
                        Transactions Financières en attente de validation
                    </h3>
                </div>

                <div v-if="pendingTransactions.length === 0" class="py-16 text-center text-xs text-slate-500 font-mono border border-dashed border-white/5 rounded-2xl">
                    [ INFOS ] : Aucune transaction financière en attente de visa administratif.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-left text-[9px] font-black uppercase tracking-widest text-slate-500">
                                <th class="pb-3 pl-2">Utilisateur</th>
                                <th class="pb-3">Référence</th>
                                <th class="pb-3">Type</th>
                                <th class="pb-3">Montant</th>
                                <th class="pb-3">Solde</th>
                                <th class="pb-3">Date</th>
                                <th class="pb-3 text-right pr-2">Signature</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-xs font-mono text-slate-300">
                            <tr v-for="tx in pendingTransactions" :key="tx.id" class="hover:bg-white/[0.01] transition-all">
                                <td class="py-3.5 pl-2 font-bold text-white">
                                    <div>{{ tx.user ? tx.user.phone : 'Inconnu' }}</div>
                                    <div v-if="tx.payment_method || tx.payment_phone" class="text-[10px] text-slate-500 font-normal">
                                        {{ tx.payment_method ? tx.payment_method.toUpperCase() : '' }}: {{ tx.payment_phone || '' }}
                                    </div>
                                </td>
                                <td class="py-3.5 text-slate-500">
                                    {{ tx.reference }}
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2.5 py-0.5 rounded text-[8px] font-black uppercase tracking-wider"
                                        :class="tx.type === 'deposit' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-amber-500/10 text-amber-400 border border-amber-500/20'"
                                    >
                                        {{ tx.type === 'deposit' ? 'Dépôt' : 'Retrait' }}
                                    </span>
                                </td>
                                <td class="py-3.5 font-black text-purple-400">
                                    {{ formatXAF(tx.amount) }}
                                </td>
                                <td class="py-3.5 text-slate-400">
                                    {{ tx.user ? formatXAF(tx.user.balance) : '0 XAF' }}
                                </td>
                                <td class="py-3.5 text-slate-500 text-[10px]">
                                    {{ new Date(tx.created_at).toLocaleString('fr-FR') }}
                                </td>
                                <td class="py-3.5 text-right pr-2 space-x-2 shrink-0">
                                    <button 
                                        @click="handleApproveTx(tx.id)"
                                        class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-black font-black uppercase tracking-wider text-[8px] rounded-lg transition-all shadow-[0_0_8px_rgba(16,185,129,0.2)]"
                                    >
                                        Valider
                                    </button>
                                    <button 
                                        @click="handleRejectTx(tx.id)"
                                        class="px-3 py-1.5 bg-rose-950/40 text-rose-400 hover:bg-rose-500 hover:text-black font-black uppercase tracking-wider text-[8px] rounded-lg border border-rose-500/30 transition-all"
                                    >
                                        Rejeter
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB CONTENT 2: USERS GRID -->
            <div v-else-if="activeTab === 'users'" class="glass rounded-3xl p-5 border border-white/5 bg-black/10">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                        <Users class="h-4.5 w-4.5 text-purple-400" />
                        Registre Global des investisseurs de l'infrastructure
                    </h3>
                </div>

                <div v-if="filteredUsers.length === 0" class="py-16 text-center text-xs text-slate-500 font-mono border border-dashed border-white/5 rounded-2xl">
                    [ INFOS ] : Aucun utilisateur trouvé correspondant aux critères.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-left text-[9px] font-black uppercase tracking-widest text-slate-500">
                                <th class="pb-3 pl-2">Téléphone / Compte</th>
                                <th class="pb-3">Solde Courant</th>
                                <th class="pb-3">VIP</th>
                                <th class="pb-3">AVIP</th>
                                <th class="pb-3">Tours Tirage</th>
                                <th class="pb-3">Triche/Rigging</th>
                                <th class="pb-3">Rôle</th>
                                <th class="pb-3 text-right pr-2">Configuration</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-xs font-mono text-slate-300">
                            <tr v-for="u in filteredUsers" :key="u.id" class="hover:bg-white/[0.01] transition-all">
                                <td class="py-3.5 pl-2 font-bold text-white flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="u.role === 'admin' ? 'bg-purple-500 animate-pulse' : 'bg-slate-500'"></span>
                                    {{ u.phone }}
                                </td>
                                <td class="py-3.5 font-black text-purple-400">
                                    {{ formatXAF(u.balance) }}
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2 py-0.5 rounded bg-purple-950/40 text-purple-300 border border-purple-700/20 text-[9px] font-bold">
                                        VIP {{ u.vip_level }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-slate-400">
                                    AVIP {{ u.avip_level }}
                                </td>
                                <td class="py-3.5 font-bold">
                                    {{ u.draw_spins }} lancers
                                </td>
                                <td class="py-3.5">
                                    <span v-if="u.next_spin_prize_index !== null" class="text-amber-400 font-bold uppercase tracking-wider text-[9px] px-1.5 py-0.2 rounded bg-amber-500/10 border border-amber-500/20">
                                        Index {{ u.next_spin_prize_index }}
                                    </span>
                                    <span v-else class="text-slate-500 text-[10px]">Aléatoire</span>
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[8px] uppercase tracking-widest font-black"
                                        :class="u.role === 'admin' ? 'bg-purple-500/20 text-purple-400 border border-purple-500/30' : 'bg-white/5 text-slate-500'"
                                    >
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right pr-2">
                                    <button 
                                        @click="openUserModal(u)"
                                        class="px-2.5 py-1 bg-white/5 border border-white/10 hover:bg-purple-600 hover:text-white rounded-lg text-[9px] font-black uppercase tracking-wider transition-all flex items-center gap-1 ml-auto"
                                    >
                                        <Edit3 class="w-2.5 h-2.5" /> Gérer
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB CONTENT 3: DYNAMIC GIFT CODES -->
            <div v-else-if="activeTab === 'giftcodes'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Gift Code creation panel -->
                <div class="glass rounded-3xl p-5 border border-white/5 bg-black/10 h-fit lg:col-span-1">
                    <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2 mb-5">
                        <PlusCircle class="h-4.5 w-4.5 text-purple-400" />
                        Générer un Code Cadeau
                    </h3>

                    <form @submit.prevent="handleCreateGiftCode" class="space-y-4 text-xs">
                        <div class="space-y-1.5">
                            <label class="text-[10px] text-slate-400 uppercase font-black tracking-wider block">Intitulé du Code</label>
                            <input 
                                v-model="giftCodeForm.code"
                                type="text"
                                required
                                placeholder="Ex: ARM-NEW-YEAR"
                                class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10.5 rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500 uppercase"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] text-slate-400 uppercase font-black tracking-wider block">Valeur en XAF</label>
                            <input 
                                v-model="giftCodeForm.amount"
                                type="number"
                                required
                                placeholder="Ex: 5000"
                                class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10.5 rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500"
                            />
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] text-slate-400 uppercase font-black tracking-wider block">Nombre d'usages maximum</label>
                            <input 
                                v-model="giftCodeForm.max_usages"
                                type="number"
                                required
                                placeholder="Ex: 50"
                                class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10.5 rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500"
                            />
                        </div>

                        <button 
                            type="submit"
                            :disabled="giftCodeForm.processing"
                            class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white text-[10px] font-black uppercase tracking-widest transition-all shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                        >
                            {{ giftCodeForm.processing ? 'GÉNÉRATION EN COURS...' : 'INJECTER DANS LA BDD' }}
                        </button>
                    </form>
                </div>

                <!-- Gift Codes Active list -->
                <div class="glass rounded-3xl p-5 border border-white/5 bg-black/10 lg:col-span-2">
                    <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2 mb-5">
                        <Gift class="h-4.5 w-4.5 text-purple-400" />
                        Codes Cadeaux actifs en Base de Données
                    </h3>

                    <div v-if="giftCodes.length === 0" class="py-16 text-center text-xs text-slate-500 font-mono border border-dashed border-white/5 rounded-2xl">
                        [ INFOS ] : Aucun code cadeau promotionnel enregistré dans la base de données.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="border-b border-white/5 text-left text-[9px] font-black uppercase tracking-widest text-slate-500">
                                    <th class="pb-3 pl-2">Code</th>
                                    <th class="pb-3">Crédit Injecté</th>
                                    <th class="pb-3">Usages Réalisés</th>
                                    <th class="pb-3">Créé Le</th>
                                    <th class="pb-3 text-right pr-2">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-white/5 text-xs font-mono text-slate-300">
                                <tr v-for="code in giftCodes" :key="code.id" class="hover:bg-white/[0.01] transition-all">
                                    <td class="py-3.5 pl-2 font-black text-white tracking-wide">
                                        {{ code.code }}
                                    </td>
                                    <td class="py-3.5 font-bold text-purple-400">
                                        {{ formatXAF(code.amount) }}
                                    </td>
                                    <td class="py-3.5 font-bold">
                                        <div class="flex items-center gap-2">
                                            <span :class="code.usages >= code.max_usages ? 'text-rose-400' : 'text-emerald-400'">
                                                {{ code.usages }} / {{ code.max_usages }}
                                            </span>
                                            <div class="w-16 h-1.5 rounded-full bg-white/5 overflow-hidden">
                                                <div class="h-full bg-purple-500" :style="`width: ${Math.min(100, (code.usages / code.max_usages) * 100)}%`"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3.5 text-slate-500 text-[10px]">
                                        {{ new Date(code.created_at).toLocaleDateString() }}
                                    </td>
                                    <td class="py-3.5 text-right pr-2">
                                        <button 
                                            @click="handleDeleteGiftCode(code.id)"
                                            class="p-1.5 bg-rose-950/20 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-black rounded-lg transition-all"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT 4: PRODUCT CATALOG (SOFT DELETES & VIP LIMITATIONS) -->
            <div v-else-if="activeTab === 'products'" class="space-y-6">
                <!-- Category 1: Standard server nodes -->
                <div class="glass rounded-3xl p-5 border border-white/5 bg-black/10">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                            <Cpu class="h-4.5 w-4.5 text-purple-400 animate-pulse" />
                            Registre des serveurs standards (Nodes)
                        </h3>
                        <button 
                            @click="showCreateNodeModal = true"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-black uppercase tracking-wider text-[8px] rounded-lg transition-all shadow-[0_0_8px_rgba(168,85,247,0.2)] flex items-center gap-1"
                        >
                            <Plus class="w-3.5 h-3.5" /> Ajouter un Serveur
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div v-for="node in nodes" :key="node.id" class="p-4 rounded-2xl border transition-all duration-300 flex flex-col justify-between"
                            :class="node.deleted_at ? 'border-rose-950 bg-rose-950/[0.01] opacity-60' : 'border-purple-500/10 hover:border-purple-500/30 bg-black/25'"
                        >
                            <div>
                                <div class="flex justify-between items-start gap-2 mb-3">
                                    <div>
                                        <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                            {{ node.name }}
                                            <span v-if="node.deleted_at" class="text-[7px] font-black px-1.5 py-0.2 rounded bg-rose-500/10 text-rose-400 border border-rose-500/25 uppercase font-mono">Soft Deleted</span>
                                        </h4>
                                        <span class="text-[8px] text-slate-500 uppercase tracking-widest font-mono">ID: {{ node.id }}</span>
                                    </div>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-wider font-mono"
                                        :class="node.active && !node.deleted_at ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                    >
                                        {{ node.active && !node.deleted_at ? 'ACTIF' : 'INACTIF' }}
                                    </span>
                                </div>

                                <div class="bg-black/50 border border-white/5 rounded-xl p-3 space-y-2 mb-4 text-[10px] font-mono">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Prix de location :</span>
                                        <span class="text-white font-bold">{{ formatXAF(node.amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Bénéfice/jour :</span>
                                        <span class="text-emerald-400 font-bold">+{{ formatXAF(node.generation_profit) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">VIP Minimum :</span>
                                        <span class="text-purple-400 font-black">VIP {{ node.technology_level }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Parrainage :</span>
                                        <span class="text-purple-400 font-bold">{{ formatXAF(node.referral_reward || 0) }}</span>
                                    </div>
                                    <div class="flex justify-between border-t border-white/5 pt-1.5 mt-1.5 text-[9px]">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Stock restant :</span>
                                        <span class="text-white font-bold">{{ node.stock_quantity !== null ? node.stock_quantity + ' unites' : 'Illimite' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Quota par compte :</span>
                                        <span class="text-white font-bold">{{ node.limited_purchase_count !== null ? node.limited_purchase_count + ' max' : 'Illimite' }}</span>
                                    </div>
                                </div>
                            </div>

                            <button 
                                @click="openNodeModal(node)"
                                class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1 shadow-[0_0_10px_rgba(168,85,247,0.2)]"
                            >
                                <Settings class="w-3 h-3" /> Configurer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category 2: AVIP Products -->
                <div class="glass rounded-3xl p-5 border border-white/5 bg-black/10">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                            <Zap class="h-4.5 w-4.5 text-purple-400 animate-pulse" />
                            Accélérateurs Supérieurs & Ravitaillement AVIP
                        </h3>
                        <button 
                            @click="showCreateAvipModal = true"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-black uppercase tracking-wider text-[8px] rounded-lg transition-all shadow-[0_0_8px_rgba(168,85,247,0.2)] flex items-center gap-1"
                        >
                            <Plus class="w-3.5 h-3.5" /> Ajouter un AVIP
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div v-for="p in avipProducts" :key="p.id" class="p-4 rounded-2xl border transition-all duration-300 flex flex-col justify-between"
                            :class="p.deleted_at ? 'border-rose-950 bg-rose-950/[0.01] opacity-60' : 'border-purple-500/10 hover:border-purple-500/30 bg-black/25'"
                        >
                            <div>
                                <div class="flex justify-between items-start gap-2 mb-3">
                                    <div>
                                        <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                            {{ p.name }}
                                            <span v-if="p.deleted_at" class="text-[7px] font-black px-1.5 py-0.2 rounded bg-rose-500/10 text-rose-400 border border-rose-500/25 uppercase font-mono">Soft Deleted</span>
                                        </h4>
                                        <span class="text-[8px] text-slate-500 uppercase tracking-widest font-mono">ID: {{ p.id }}</span>
                                    </div>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-wider font-mono"
                                        :class="p.active && !p.deleted_at ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                    >
                                        {{ p.active && !p.deleted_at ? 'ACTIF' : 'INACTIF' }}
                                    </span>
                                </div>

                                <p class="text-[10px] text-slate-400 leading-relaxed mb-3 line-clamp-2 min-h-[30px]">
                                    {{ p.description }}
                                </p>

                                <div class="bg-black/50 border border-white/5 rounded-xl p-3 space-y-2 mb-4 text-[10px] font-mono">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Montant Achat :</span>
                                        <span class="text-white font-bold">{{ formatXAF(p.amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Salaire Journalier :</span>
                                        <span class="text-emerald-400 font-bold">+{{ formatXAF(p.daily_salary) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">VIP Requis :</span>
                                        <span class="text-purple-400 font-black">VIP {{ p.required_vip_level }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Palier AVIP :</span>
                                        <span class="text-white font-bold">AVIP {{ p.avip_level }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Durée :</span>
                                        <span class="text-white font-bold">{{ p.duration || 7 }} Jours</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Parrainage :</span>
                                        <span class="text-purple-400 font-bold">{{ formatXAF(p.referral_reward || 0) }}</span>
                                    </div>
                                </div>
                            </div>

                            <button 
                                @click="openAvipModal(p)"
                                class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1 shadow-[0_0_10px_rgba(168,85,247,0.2)]"
                            >
                                <Settings class="w-3 h-3" /> Configurer
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Category 3: Vault Plans -->
                <div class="glass rounded-3xl p-5 border border-white/5 bg-black/10">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                            <Coins class="h-4.5 w-4.5 text-purple-400 animate-pulse" />
                            Produits de Coffre-fort & Épargne (Vault Plans)
                        </h3>
                        <button 
                            @click="showCreateVaultModal = true"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-black uppercase tracking-wider text-[8px] rounded-lg transition-all shadow-[0_0_8px_rgba(168,85,247,0.2)] flex items-center gap-1"
                        >
                            <Plus class="w-3.5 h-3.5" /> Ajouter un Vault
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <div v-for="v in vaultPlans" :key="v.id" class="p-4 rounded-2xl border transition-all duration-300 flex flex-col justify-between border-purple-500/10 hover:border-purple-500/30 bg-black/25">
                            <div>
                                <div class="flex justify-between items-start gap-2 mb-3">
                                    <div>
                                        <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                            {{ v.name }}
                                        </h4>
                                        <span class="text-[8px] text-slate-500 uppercase tracking-widest font-mono">ID: {{ v.id }}</span>
                                    </div>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-wider font-mono"
                                        :class="v.active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                    >
                                        {{ v.active ? 'ACTIF' : 'INACTIF' }}
                                    </span>
                                </div>

                                <div class="bg-black/50 border border-white/5 rounded-xl p-3 space-y-2 mb-4 text-[10px] font-mono">
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Montant Requis :</span>
                                        <span class="text-white font-bold">{{ formatXAF(v.fixed_investment_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Retour Total :</span>
                                        <span class="text-emerald-400 font-bold">+{{ formatXAF(v.fixed_return) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Intérêt Net :</span>
                                        <span class="text-emerald-400 font-bold">+{{ formatXAF(v.profit_amount) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Durée :</span>
                                        <span class="text-white font-bold">{{ v.duration }} Jours</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-slate-500 uppercase font-black text-[8px] tracking-wider">Type Versement :</span>
                                        <span class="font-black" :class="v.payout_type === 'daily' ? 'text-cyan-400' : 'text-purple-400'">
                                            {{ v.payout_type === 'daily' ? 'Journalier' : 'À l\'expiration' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <button 
                                @click="openVaultModal(v)"
                                class="w-full py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1 shadow-[0_0_10px_rgba(168,85,247,0.2)]"
                            >
                                <Settings class="w-3 h-3" /> Configurer
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT 5: ANNOUNCEMENTS CRUD -->
            <div v-else-if="activeTab === 'announcements'" class="space-y-6">
                <div class="glass rounded-3xl p-5 border border-white/5 bg-black/10">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2">
                            <MegaphoneIcon class="h-4.5 w-4.5 text-purple-400 animate-pulse" />
                            Gestion des Annonces & Actualités Système
                        </h3>
                        <button 
                            @click="showCreateAnnouncementModal = true"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white font-black uppercase tracking-wider text-[8px] rounded-lg transition-all shadow-[0_0_8px_rgba(168,85,247,0.2)] flex items-center gap-1"
                        >
                            <Plus class="w-3.5 h-3.5" /> Publier une Annonce
                        </button>
                    </div>

                    <div v-if="announcements.length === 0" class="py-16 text-center text-xs text-slate-500 font-mono border border-dashed border-white/5 rounded-2xl">
                        [ INFOS ] : Aucune annonce système publiée pour le moment.
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div v-for="ann in announcements" :key="ann.id" class="p-5 rounded-2xl border border-purple-500/10 bg-black/25 flex flex-col justify-between hover:border-purple-500/30 transition-all duration-300">
                            <div>
                                <div class="flex justify-between items-start gap-2 mb-3">
                                    <h4 class="text-xs font-bold text-white leading-snug">{{ ann.title }}</h4>
                                    <span class="text-[8px] font-black px-2 py-0.5 rounded uppercase tracking-wider font-mono shrink-0"
                                        :class="ann.active ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20'"
                                    >
                                        {{ ann.active ? 'ACTIF' : 'INACTIF' }}
                                    </span>
                                </div>
                                
                                <p class="text-[11px] text-slate-400 leading-relaxed mb-4 whitespace-pre-line line-clamp-3">
                                    {{ ann.content }}
                                </p>

                                <div v-if="ann.image_url" class="h-28 w-full rounded-xl overflow-hidden mb-4 border border-white/5">
                                    <img :src="ann.image_url" class="h-full w-full object-cover" />
                                </div>

                                <div class="flex flex-wrap gap-2 text-[9px] font-mono text-slate-500 mb-4">
                                    <span>Créé le: {{ new Date(ann.created_at).toLocaleDateString() }}</span>
                                    <span v-if="ann.link">• Lien: <a :href="ann.link" target="_blank" class="text-cyan-400 underline">{{ ann.link }}</a></span>
                                </div>
                            </div>

                            <div class="flex gap-2 border-t border-white/5 pt-3">
                                <button 
                                    @click="openAnnouncementModal(ann)"
                                    class="flex-1 py-2 bg-purple-600/10 hover:bg-purple-600 hover:text-white text-purple-400 border border-purple-500/20 rounded-xl text-[9px] font-black uppercase tracking-wider transition-all flex items-center justify-center gap-1"
                                >
                                    <Edit3 class="w-3 h-3" /> Configurer
                                </button>
                                <button 
                                    @click="handleDeleteAnnouncement(ann.id)"
                                    class="p-2 bg-rose-950/20 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-black rounded-xl transition-all"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB CONTENT 6: SYSTEM PARAMETERS -->
            <div v-else-if="activeTab === 'settings'" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- System Config Form (Left Column) -->
                <div class="lg:col-span-2 space-y-6">
                    <form @submit.prevent="handleUpdateSettings" class="space-y-6">
                        <!-- Card 1: Limits & Support links -->
                        <div class="glass rounded-3xl p-6 border border-white/5 bg-black/10 space-y-4">
                            <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2 border-b border-white/5 pb-3">
                                <Settings class="h-4.5 w-4.5 text-purple-400" />
                                Variables de Contrôle de l'Écosystème
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Minimum de Dépôt (XAF)</label>
                                    <input 
                                        v-model="settingsForm.min_deposit" 
                                        type="number" 
                                        required 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 h-11 rounded-xl focus:border-cyan-400 focus:ring-1 focus:ring-cyan-500 outline-none transition-all"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Minimum de Retrait (XAF)</label>
                                    <input 
                                        v-model="settingsForm.min_withdrawal" 
                                        type="number" 
                                        required 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 h-11 rounded-xl focus:border-cyan-400 focus:ring-1 focus:ring-cyan-500 outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Canal Support Telegram</label>
                                    <input 
                                        v-model="settingsForm.support_telegram" 
                                        type="url" 
                                        placeholder="https://t.me/..." 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 h-11 rounded-xl focus:border-cyan-400 focus:ring-1 focus:ring-cyan-500 outline-none transition-all"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Canal Support WhatsApp</label>
                                    <input 
                                        v-model="settingsForm.support_whatsapp" 
                                        type="url" 
                                        placeholder="https://wa.me/..." 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 h-11 rounded-xl focus:border-cyan-400 focus:ring-1 focus:ring-cyan-500 outline-none transition-all"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-2">
                                <div class="space-y-1.5">
                                    <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Spins de bienvenue par Parrainage</label>
                                    <input 
                                        v-model="settingsForm.lucky_draw_cost" 
                                        type="number" 
                                        required 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 h-11 rounded-xl focus:border-cyan-400 focus:ring-1 focus:ring-cyan-500 outline-none transition-all"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée de Génération Node (secondes)</label>
                                    <input 
                                        v-model="settingsForm.generation_duration" 
                                        type="number" 
                                        required 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 h-11 rounded-xl focus:border-cyan-400 focus:ring-1 focus:ring-cyan-500 outline-none transition-all"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <button 
                            type="submit" 
                            :disabled="settingsForm.processing"
                            class="w-full py-4 rounded-2xl bg-gradient-to-r from-purple-600 to-cyan-500 hover:from-purple-500 hover:to-cyan-400 text-white text-xs font-black uppercase tracking-widest transition-all shadow-[0_0_15px_rgba(6,182,212,0.3)] disabled:opacity-50"
                        >
                            {{ settingsForm.processing ? 'ENREGISTREMENT...' : 'SAUVEGARDER LA CONFIGURATION GLOBAL SYSTEM' }}
                        </button>
                    </form>
                </div>

                <!-- Right Column: Salaries Configurations (VIP 1 to 5) -->
                <div class="lg:col-span-1">
                    <div class="glass rounded-3xl p-6 border border-white/5 bg-black/10 space-y-4">
                        <h3 class="text-xs font-black uppercase tracking-wider text-white flex items-center gap-2 border-b border-white/5 pb-3">
                            <Coins class="h-4.5 w-4.5 text-purple-400 animate-pulse" />
                            Configuration des Salaires VIP
                        </h3>
                        <p class="text-[10px] text-slate-400 leading-relaxed uppercase tracking-wider">
                            Définit les dividendes journaliers automatiques réclamés par les investisseurs selon leur rang VIP.
                        </p>

                        <div class="space-y-4 pt-2">
                            <div v-for="level in [1, 2, 3, 4, 5]" :key="level" class="space-y-1.5 p-3 rounded-2xl border border-purple-500/5 bg-purple-950/[0.02]">
                                <div class="flex justify-between items-center mb-1">
                                    <span class="text-[10px] font-black text-white uppercase tracking-wider">Palier VIP {{ level }}</span>
                                    <span class="text-[9px] font-mono text-purple-400 font-bold">Actuel: {{ formatXAF(props.settings.vip_salaries[level] || 0) }}</span>
                                </div>
                                <div class="relative">
                                    <input 
                                        v-model="settingsForm.vip_salaries[level]" 
                                        type="number" 
                                        required 
                                        class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3.5 pr-12 h-10 rounded-xl focus:border-purple-400 outline-none"
                                    />
                                    <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-[9px] font-black text-slate-500 font-mono">XAF</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- MODAL OVERLAY 1: USER PROPERTIES EDITION & RIGGING -->
        <div v-if="selectedUser" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Settings class="w-4 h-4 text-purple-400" />
                            Gérer le Mineur
                        </h3>
                        <button @click="selectedUser = null" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <div class="mb-4 text-center">
                        <span class="text-[10px] text-slate-500 uppercase tracking-widest block font-bold font-mono">ID: {{ selectedUser.id }}</span>
                        <span class="text-sm font-mono font-black text-white block mt-0.5">{{ selectedUser.phone }}</span>
                    </div>

                    <form @submit.prevent="handleUpdateUser" class="space-y-3.5 text-xs">
                        
                        <!-- Balance -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Solde en XAF</label>
                            <input 
                                v-model="userEditForm.balance"
                                type="number"
                                required
                                class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500"
                            />
                        </div>

                        <!-- System Role -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Rôle Système</label>
                            <select 
                                v-model="userEditForm.role"
                                class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500"
                            >
                                <option value="user">Utilisateur (user)</option>
                                <option value="admin">Administrateur (admin)</option>
                            </select>
                        </div>

                        <!-- VIP & AVIP levels -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Niveau VIP</label>
                                <select 
                                    v-model="userEditForm.vip_level"
                                    class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl focus:outline-none"
                                >
                                    <option v-for="i in [0, 1, 2, 3, 4, 5]" :key="i" :value="i">VIP {{ i }}</option>
                                </select>
                            </div>
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Niveau AVIP</label>
                                <select 
                                    v-model="userEditForm.avip_level"
                                    class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl focus:outline-none"
                                >
                                    <option v-for="i in [0, 1, 2, 3]" :key="i" :value="i">AVIP {{ i }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Spin Wheels quota -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Lancers Tirage Disponibles</label>
                            <input 
                                v-model="userEditForm.draw_spins"
                                type="number"
                                required
                                class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl focus:outline-none focus:ring-1 focus:ring-purple-500"
                            />
                        </div>

                        <!-- RIGGING / PRE-DETERMINED OUTCOME SELECTOR -->
                        <div class="space-y-1.5 p-3.5 bg-amber-500/[0.02] border border-amber-500/25 rounded-2xl">
                            <label class="text-[9px] text-amber-400 uppercase font-black tracking-wider block flex items-center gap-1">
                                <Zap class="w-3.5 h-3.5 text-amber-400 animate-pulse" />
                                Rigging du Prochain Tirage
                            </label>
                            <p class="text-[8px] text-slate-400 leading-relaxed mb-1.5">Force la roue de l'utilisateur à s'arrêter sur le gain sélectionné lors de son PROCHAIN spin.</p>
                            <select 
                                v-model="userEditForm.next_spin_prize_index"
                                class="w-full bg-black/50 border border-amber-500/30 text-amber-400 font-mono text-xs pl-3 h-10 rounded-xl focus:outline-none"
                            >
                                <option :value="null">Aléatoire (Aucun rigging)</option>
                                <option value="0">Secteur 0 : 777 XAF (Hautement Probable)</option>
                                <option value="1">Secteur 1 : 1 777 XAF</option>
                                <option value="2">Secteur 2 : 7 777 XAF</option>
                                <option value="3">Secteur 3 : 77 777 XAF</option>
                                <option value="4">Secteur 4 : 177 777 XAF</option>
                                <option value="5">Secteur 5 : 777 777 XAF</option>
                                <option value="6">Secteur 6 : 1 777 777 XAF (JACKPOT FORCÉ)</option>
                            </select>
                        </div>

                        <!-- Actions Buttons -->
                        <div class="flex gap-2 pt-3 border-t border-white/5">
                            <button 
                                type="submit"
                                :disabled="userEditForm.processing"
                                class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                            >
                                Enregistrer
                            </button>
                            <button 
                                type="button"
                                @click="handleDeleteUser(selectedUser.id)"
                                class="px-3.5 py-3 bg-rose-950/30 border border-rose-500/30 text-rose-400 hover:bg-rose-500 hover:text-black font-bold uppercase tracking-wider text-[9px] rounded-xl transition-all"
                            >
                                Bannir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY 2: CONFIGURATION STANDARD NODE -->
        <div v-if="selectedNode" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Cpu class="w-4 h-4 text-purple-400" />
                            Configurer le Serveur
                        </h3>
                        <button @click="selectedNode = null" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleUpdateNode" class="space-y-3.5 text-xs">
                        
                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Intitulé du Serveur</label>
                            <input v-model="nodeForm.name" type="text" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="nodeForm.image_file" :src="getObjectUrl(nodeForm.image_file)" class="h-full w-full object-cover" />
                                        <img v-else-if="nodeForm.image_url" :src="nodeForm.image_url" class="h-full w-full object-cover" />
                                        <Cpu v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ nodeForm.image_file ? nodeForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    @change="(e: any) => nodeForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Rent Price -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Prix Location (XAF)</label>
                                <input v-model="nodeForm.amount" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Daily Profit -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Profit/Jour (XAF)</label>
                                <input v-model="nodeForm.generation_profit" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Referral Reward -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Parrainage (XAF)</label>
                                <input v-model="nodeForm.referral_reward" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Required VIP level -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">VIP Minimum Requis</label>
                                <select v-model="nodeForm.technology_level" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option v-for="i in [0, 1, 2, 3, 4, 5]" :key="i" :value="i">VIP {{ i }}</option>
                                </select>
                            </div>
                            <!-- Duration -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée de Contrat (jours)</label>
                                <input v-model="nodeForm.duration" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Stock limit -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Stock Limité (Unités)</label>
                                <input v-model="nodeForm.stock_quantity" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Purchase limit per user -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Quota Max / Compte</label>
                                <input v-model="nodeForm.limited_purchase_count" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <!-- Referral Constraint -->
                        <div class="space-y-1 bg-black/35 p-3 rounded-xl border border-white/5">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Filleuls Actifs Requis (Achat)</label>
                            <input v-model="nodeForm.required_active_referrals" type="number" placeholder="Aucun requis si 0" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Limited Offer Toggle -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="nodeForm.is_limited" type="checkbox" id="edit_node_limited" class="accent-rose-500 rounded cursor-pointer" />
                            <label for="edit_node_limited" class="text-[10px] text-rose-400 font-black cursor-pointer uppercase">Marquer comme Offre Limitée</label>
                        </div>

                        <!-- Restore and active controls -->
                        <div class="flex items-center justify-between py-1 bg-black/35 px-3 rounded-xl border border-white/5">
                            <label class="flex items-center space-x-2 text-[10px] text-slate-300 font-black cursor-pointer">
                                <input v-model="nodeForm.active" type="checkbox" class="accent-purple-500 rounded" />
                                <span>OFFRE ACTIVER DANS BOUTIQUE</span>
                            </label>
                            <label v-if="selectedNode.deleted_at" class="flex items-center space-x-2 text-[10px] text-emerald-400 font-black cursor-pointer">
                                <input v-model="nodeForm.restore" type="checkbox" class="accent-emerald-500 rounded" />
                                <span>RESTAURER LE SERVEUR</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-3 border-t border-white/5">
                            <button 
                                type="submit" 
                                class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                            >
                                Mettre à Jour
                            </button>
                            <button 
                                v-if="!selectedNode.deleted_at"
                                type="button" 
                                @click="handleDeleteNode(selectedNode.id)" 
                                class="px-3.5 py-3 bg-rose-950/30 border border-rose-500/30 text-rose-400 hover:bg-rose-500 hover:text-black font-bold uppercase tracking-wider text-[9px] rounded-xl transition-all"
                            >
                                Supprimer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY 3: CONFIGURATION ACCÉLÉRATEUR AVIP -->
        <div v-if="selectedAvip" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Zap class="w-4 h-4 text-purple-400" />
                            Configurer l'AVIP
                        </h3>
                        <button @click="selectedAvip = null" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleUpdateAvip" class="space-y-3.5 text-xs">
                        
                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Intitulé de la Licence AVIP</label>
                            <input v-model="avipForm.name" type="text" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Description Matérielle</label>
                            <textarea v-model="avipForm.description" rows="2" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 py-1.5 rounded-xl focus:outline-none"></textarea>
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="avipForm.image_file" :src="getObjectUrl(avipForm.image_file)" class="h-full w-full object-cover" />
                                        <img v-else-if="avipForm.image" :src="avipForm.image" class="h-full w-full object-cover" />
                                        <Zap v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ avipForm.image_file ? avipForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    @change="(e: any) => avipForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Cost -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Montant Achat (XAF)</label>
                                <input v-model="avipForm.amount" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Daily salary yield -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Dividende/Jour (XAF)</label>
                                <input v-model="avipForm.daily_salary" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Referral Reward -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Parrainage (XAF)</label>
                                <input v-model="avipForm.referral_reward" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Required VIP level -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">VIP Minimum Requis</label>
                                <select v-model="avipForm.required_vip_level" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option v-for="i in [1, 2, 3, 4, 5]" :key="i" :value="i">VIP {{ i }}</option>
                                </select>
                            </div>
                            <!-- AVIP level -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Niveau AVIP (1 à 5)</label>
                                <select v-model="avipForm.avip_level" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option v-for="i in [1, 2, 3, 4, 5]" :key="i" :value="i">AVIP {{ i }}</option>
                                </select>
                            </div>
                            <!-- Duration -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée (jours)</label>
                                <input v-model="avipForm.duration" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Stock limit -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Stock Limité (Unités)</label>
                                <input v-model="avipForm.stock_quantity" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Purchase limit per user -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Quota Max / Compte</label>
                                <input v-model="avipForm.limited_purchase_count" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <!-- Referral Constraint -->
                        <div class="space-y-1 bg-black/35 p-3 rounded-xl border border-white/5">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Filleuls Actifs Requis (Achat)</label>
                            <input v-model="avipForm.required_active_referrals" type="number" placeholder="Aucun requis si 0" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Limited Offer Toggle -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="avipForm.is_limited" type="checkbox" id="edit_avip_limited" class="accent-rose-500 rounded cursor-pointer" />
                            <label for="edit_avip_limited" class="text-[10px] text-rose-400 font-black cursor-pointer uppercase">Marquer comme Offre Limitée</label>
                        </div>

                        <!-- Restore & active status -->
                        <div class="flex items-center justify-between py-1 bg-black/35 px-3 rounded-xl border border-white/5">
                            <label class="flex items-center space-x-2 text-[10px] text-slate-300 font-black cursor-pointer">
                                <input v-model="avipForm.active" type="checkbox" class="accent-purple-500 rounded" />
                                <span>OFFRE ACTIVER DANS BOUTIQUE</span>
                            </label>
                            <label v-if="selectedAvip.deleted_at" class="flex items-center space-x-2 text-[10px] text-emerald-400 font-black cursor-pointer">
                                <input v-model="avipForm.restore" type="checkbox" class="accent-emerald-500 rounded" />
                                <span>RESTAURER LE SERVEUR</span>
                            </label>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-2 pt-3 border-t border-white/5">
                            <button 
                                type="submit" 
                                class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                            >
                                Mettre à Jour
                            </button>
                            <button 
                                v-if="!selectedAvip.deleted_at"
                                type="button" 
                                @click="handleDeleteAvip(selectedAvip.id)" 
                                class="px-3.5 py-3 bg-rose-950/30 border border-rose-500/30 text-rose-400 hover:bg-rose-500 hover:text-black font-bold uppercase tracking-wider text-[9px] rounded-xl transition-all"
                            >
                                Supprimer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY 4: CREATE STANDARD NODE -->
        <div v-if="showCreateNodeModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Plus class="w-4 h-4 text-purple-400" />
                            Créer un Nœud Serveur
                        </h3>
                        <button @click="showCreateNodeModal = false" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleCreateNode" class="space-y-3.5 text-xs">
                        
                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Intitulé du Serveur</label>
                            <input v-model="createNodeForm.name" type="text" required placeholder="Ex: Nœud Quantum" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="createNodeForm.image_file" :src="getObjectUrl(createNodeForm.image_file)" class="h-full w-full object-cover" />
                                        <Cpu v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ createNodeForm.image_file ? createNodeForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    required
                                    @change="(e: any) => createNodeForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Rent Price -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Prix Location (XAF)</label>
                                <input v-model="createNodeForm.amount" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Daily Profit -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Profit/Jour (XAF)</label>
                                <input v-model="createNodeForm.generation_profit" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Referral Reward -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Parrainage (XAF)</label>
                                <input v-model="createNodeForm.referral_reward" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Required VIP level -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">VIP Requis</label>
                                <select v-model="createNodeForm.technology_level" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option v-for="i in [0, 1, 2, 3, 4, 5]" :key="i" :value="i">VIP {{ i }}</option>
                                </select>
                            </div>
                            <!-- Duration -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée Contrat (jours)</label>
                                <input v-model="createNodeForm.duration" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Stock limit -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Stock Initial</label>
                                <input v-model="createNodeForm.stock_quantity" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Purchase limit per user -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Quota Max / Compte</label>
                                <input v-model="createNodeForm.limited_purchase_count" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <!-- Referral Constraint -->
                        <div class="space-y-1 bg-black/35 p-3 rounded-xl border border-white/5">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Filleuls Actifs Requis (Achat)</label>
                            <input v-model="createNodeForm.required_active_referrals" type="number" placeholder="Aucun requis si 0" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Limited Offer Toggle -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="createNodeForm.is_limited" type="checkbox" id="create_node_limited" class="accent-rose-500 rounded cursor-pointer" />
                            <label for="create_node_limited" class="text-[10px] text-rose-400 font-black cursor-pointer uppercase">Marquer comme Offre Limitée</label>
                        </div>

                        <!-- Active Switch -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="createNodeForm.active" type="checkbox" id="create_node_active" class="accent-purple-500 rounded cursor-pointer" />
                            <label for="create_node_active" class="text-[10px] text-slate-300 font-black cursor-pointer uppercase">Activer immédiatement dans la boutique</label>
                        </div>

                        <!-- Submit -->
                        <button 
                            type="submit" 
                            :disabled="createNodeForm.processing"
                            class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                        >
                            {{ createNodeForm.processing ? 'Création...' : 'Créer le Serveur' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY 5: CREATE AVIP PRODUCT -->
        <div v-if="showCreateAvipModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Plus class="w-4 h-4 text-purple-400" />
                            Créer un Équipement AVIP
                        </h3>
                        <button @click="showCreateAvipModal = false" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleCreateAvip" class="space-y-3.5 text-xs">
                        
                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Intitulé de l'Équipement</label>
                            <input v-model="createAvipForm.name" type="text" required placeholder="Ex: Accélérateur Quantique" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Description -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Description Matérielle</label>
                            <textarea v-model="createAvipForm.description" rows="2" placeholder="Description technique de l'accélérateur..." class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 py-1.5 rounded-xl focus:outline-none"></textarea>
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="createAvipForm.image_file" :src="getObjectUrl(createAvipForm.image_file)" class="h-full w-full object-cover" />
                                        <Zap v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ createAvipForm.image_file ? createAvipForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    required
                                    @change="(e: any) => createAvipForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Cost -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Montant Achat (XAF)</label>
                                <input v-model="createAvipForm.amount" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Daily Yield -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Salaire Quotidien (XAF)</label>
                                <input v-model="createAvipForm.daily_salary" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Referral Reward -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Parrainage (XAF)</label>
                                <input v-model="createAvipForm.referral_reward" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-3">
                            <!-- Required VIP level -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">VIP Minimum Requis</label>
                                <select v-model="createAvipForm.required_vip_level" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option v-for="i in [1, 2, 3, 4, 5]" :key="i" :value="i">VIP {{ i }}</option>
                                </select>
                            </div>
                            <!-- AVIP level -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Palier AVIP (1 à 5)</label>
                                <select v-model="createAvipForm.avip_level" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option v-for="i in [1, 2, 3, 4, 5]" :key="i" :value="i">AVIP {{ i }}</option>
                                </select>
                            </div>
                            <!-- Duration -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée (jours)</label>
                                <input v-model="createAvipForm.duration" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Stock limit -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Stock Initial</label>
                                <input v-model="createAvipForm.stock_quantity" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Purchase limit per user -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Quota Max / Compte</label>
                                <input v-model="createAvipForm.limited_purchase_count" type="number" placeholder="Illimité si vide" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <!-- Referral Constraint -->
                        <div class="space-y-1 bg-black/35 p-3 rounded-xl border border-white/5">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Filleuls Actifs Requis (Achat)</label>
                            <input v-model="createAvipForm.required_active_referrals" type="number" placeholder="Aucun requis si 0" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Limited Offer Toggle -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="createAvipForm.is_limited" type="checkbox" id="create_avip_limited" class="accent-rose-500 rounded cursor-pointer" />
                            <label for="create_avip_limited" class="text-[10px] text-rose-400 font-black cursor-pointer uppercase">Marquer comme Offre Limitée</label>
                        </div>

                        <!-- Active Switch -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="createAvipForm.active" type="checkbox" id="create_avip_active" class="accent-purple-500 rounded cursor-pointer" />
                            <label for="create_avip_active" class="text-[10px] text-slate-300 font-black cursor-pointer uppercase">Activer immédiatement dans la boutique</label>
                        </div>

                        <!-- Submit -->
                        <button 
                            type="submit" 
                            :disabled="createAvipForm.processing"
                            class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                        >
                            {{ createAvipForm.processing ? 'Création...' : 'Créer l\'Équipement' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY 6: CREATE ANNOUNCEMENT -->
        <div v-if="showCreateAnnouncementModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <MegaphoneIcon class="w-4 h-4 text-purple-400" />
                            Créer une Annonce
                        </h3>
                        <button @click="showCreateAnnouncementModal = false" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleCreateAnnouncement" class="space-y-3.5 text-xs">
                        <!-- Title -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Titre de l'Annonce</label>
                            <input v-model="announcementForm.title" type="text" required placeholder="Titre accrocheur..." class="w-full bg-black/50 border border-purple-500/20 text-white font-sans text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Content -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Contenu textuel</label>
                            <textarea v-model="announcementForm.content" rows="4" required placeholder="Tapez votre message ici..." class="w-full bg-black/50 border border-purple-500/20 text-white font-sans text-xs pl-3 py-2 rounded-xl focus:outline-none"></textarea>
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="announcementForm.image_file" :src="getObjectUrl(announcementForm.image_file)" class="h-full w-full object-cover" />
                                        <MegaphoneIcon v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ announcementForm.image_file ? announcementForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    @change="(e: any) => announcementForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <!-- Link (optional) -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Lien externe redirection (Optionnel)</label>
                            <input v-model="announcementForm.link" type="text" placeholder="https://..." class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Active status -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="announcementForm.active" type="checkbox" id="create_ann_active" class="accent-purple-500 rounded cursor-pointer" />
                            <label for="create_ann_active" class="text-[10px] text-slate-300 font-black cursor-pointer uppercase">Publier immédiatement</label>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="announcementForm.processing"
                            class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                        >
                            {{ announcementForm.processing ? 'Publication...' : 'Publier' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY 7: EDIT ANNOUNCEMENT -->
        <div v-if="selectedAnnouncement" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <MegaphoneIcon class="w-4 h-4 text-purple-400" />
                            Modifier l'Annonce
                        </h3>
                        <button @click="selectedAnnouncement = null" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleUpdateAnnouncement" class="space-y-3.5 text-xs">
                        <!-- Title -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Titre de l'Annonce</label>
                            <input v-model="announcementForm.title" type="text" required class="w-full bg-black/50 border border-purple-500/20 text-white font-sans text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Content -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Contenu textuel</label>
                            <textarea v-model="announcementForm.content" rows="4" required class="w-full bg-black/50 border border-purple-500/20 text-white font-sans text-xs pl-3 py-2 rounded-xl focus:outline-none"></textarea>
                        </div>

                        <!-- Image File Upload or URL -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="announcementForm.image_file" :src="getObjectUrl(announcementForm.image_file)" class="h-full w-full object-cover" />
                                        <img v-else-if="announcementForm.image_url" :src="announcementForm.image_url" class="h-full w-full object-cover" />
                                        <MegaphoneIcon v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ announcementForm.image_file ? announcementForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    @change="(e: any) => announcementForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <!-- Link (optional) -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Lien externe redirection (Optionnel)</label>
                            <input v-model="announcementForm.link" type="text" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Active status -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="announcementForm.active" type="checkbox" id="edit_ann_active" class="accent-purple-500 rounded cursor-pointer" />
                            <label for="edit_ann_active" class="text-[10px] text-slate-300 font-black cursor-pointer uppercase">Afficher l'annonce (Actif)</label>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="announcementForm.processing"
                            class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                        >
                            {{ announcementForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY: CREATE VAULT PLAN -->
        <div v-if="showCreateVaultModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Plus class="w-4 h-4 text-purple-400" />
                            Créer un Vault Plan
                        </h3>
                        <button @click="showCreateVaultModal = false" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleCreateVault" class="space-y-3.5 text-xs">
                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Nom du Vault Plan</label>
                            <input v-model="createVaultForm.name" type="text" required placeholder="Ex: Coffre Premium" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="createVaultForm.image_file" :src="getObjectUrl(createVaultForm.image_file)" class="h-full w-full object-cover" />
                                        <Coins v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ createVaultForm.image_file ? createVaultForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    @change="(e: any) => createVaultForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Investment Amount -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Montant Requis (XAF)</label>
                                <input v-model="createVaultForm.fixed_investment_amount" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Return Amount -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Retour Total (XAF)</label>
                                <input v-model="createVaultForm.fixed_return" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Duration -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée (jours)</label>
                                <input v-model="createVaultForm.duration" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Payout Type -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Versement</label>
                                <select v-model="createVaultForm.payout_type" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option value="on_expiration">À l'expiration</option>
                                    <option value="daily">Journalier</option>
                                </select>
                            </div>
                        </div>

                        <!-- Active Switch -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="createVaultForm.active" type="checkbox" id="create_vault_active" class="accent-purple-500 rounded cursor-pointer" />
                            <label for="create_vault_active" class="text-[10px] text-slate-300 font-black cursor-pointer uppercase">Activer immédiatement</label>
                        </div>

                        <button 
                            type="submit" 
                            :disabled="createVaultForm.processing"
                            class="w-full py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                        >
                            {{ createVaultForm.processing ? 'Création...' : 'Créer le Vault Plan' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- MODAL OVERLAY: EDIT VAULT PLAN -->
        <div v-if="selectedVault" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/85 backdrop-blur-md overflow-y-auto">
            <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/30 rounded-3xl overflow-hidden shadow-2xl animate-fadeInUp">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-5 border-b border-white/5 pb-3">
                        <h3 class="text-xs font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Settings class="w-4 h-4 text-purple-400" />
                            Modifier le Vault Plan
                        </h3>
                        <button @click="selectedVault = null" class="hover:rotate-90 transition-transform"><X class="w-5 h-5 text-slate-400" /></button>
                    </div>

                    <form @submit.prevent="handleUpdateVault" class="space-y-3.5 text-xs">
                        <!-- Name -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Nom du Vault Plan</label>
                            <input v-model="vaultForm.name" type="text" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                        </div>

                        <!-- Fichier Image Upload -->
                        <div class="space-y-1">
                            <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Fichier de l'image (Upload)</label>
                            <div class="relative flex items-center justify-between bg-black/50 border border-purple-500/20 rounded-xl p-2 h-16 hover:border-cyan-500/40 transition-all overflow-hidden group">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-12 rounded-lg bg-black border border-white/10 flex items-center justify-center overflow-hidden shrink-0">
                                        <img v-if="vaultForm.image_file" :src="getObjectUrl(vaultForm.image_file)" class="h-full w-full object-cover" />
                                        <img v-else-if="vaultForm.image_url" :src="vaultForm.image_url" class="h-full w-full object-cover" />
                                        <Coins v-else class="h-5 w-5 text-slate-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-[10px] font-bold text-white truncate max-w-[150px]">
                                            {{ vaultForm.image_file ? vaultForm.image_file.name : 'Aucun fichier choisi' }}
                                        </p>
                                        <p class="text-[8px] text-slate-500">Cliquez pour modifier</p>
                                    </div>
                                </div>
                                <input 
                                    type="file" 
                                    accept="image/*"
                                    @change="(e: any) => vaultForm.image_file = e.target.files[0]"
                                    class="absolute inset-0 opacity-0 cursor-pointer z-10"
                                />
                                <div class="px-2.5 py-1 bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 rounded-lg text-[9px] font-black uppercase tracking-wider group-hover:bg-cyan-500/20 transition-all font-mono">
                                    Parcourir
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Investment Amount -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Montant Requis (XAF)</label>
                                <input v-model="vaultForm.fixed_investment_amount" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Return Amount -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Retour Total (XAF)</label>
                                <input v-model="vaultForm.fixed_return" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Duration -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Durée (jours)</label>
                                <input v-model="vaultForm.duration" type="number" required class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl" />
                            </div>
                            <!-- Payout Type -->
                            <div class="space-y-1">
                                <label class="text-[9px] text-slate-400 uppercase font-black tracking-wider block">Versement</label>
                                <select v-model="vaultForm.payout_type" class="w-full bg-black/50 border border-purple-500/20 text-white font-mono text-xs pl-3 h-10 rounded-xl">
                                    <option value="on_expiration">À l'expiration</option>
                                    <option value="daily">Journalier</option>
                                </select>
                            </div>
                        </div>

                        <!-- Active Switch -->
                        <div class="flex items-center space-x-2 py-2 bg-black/35 px-3 rounded-xl border border-white/5">
                            <input v-model="vaultForm.active" type="checkbox" id="edit_vault_active" class="accent-purple-500 rounded cursor-pointer" />
                            <label for="edit_vault_active" class="text-[10px] text-slate-300 font-black cursor-pointer uppercase">Afficher l'offre (Actif)</label>
                        </div>

                        <div class="flex gap-2">
                            <button 
                                type="submit" 
                                :disabled="vaultForm.processing"
                                class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white font-bold uppercase tracking-wider text-[9px] rounded-xl shadow-[0_0_10px_rgba(168,85,247,0.3)]"
                            >
                                {{ vaultForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                            </button>
                            <button 
                                type="button" 
                                @click="handleDeleteVault(selectedVault.id)"
                                class="py-3 px-4 bg-rose-950/20 text-rose-400 border border-rose-500/20 hover:bg-rose-500 hover:text-black rounded-xl transition-all uppercase font-bold text-[9px]"
                            >
                                Supprimer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </AdminLayout>
</template>

<style scoped>
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeInUp {
    animation: fadeInUp 0.4s ease-out forwards;
}
</style>
