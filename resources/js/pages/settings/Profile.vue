<script setup lang="ts">
import { ref, computed, watch, onUnmounted } from 'vue';
import { Head, Link, usePage, useForm, router } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    User,
    Wallet, 
    ArrowUpRight, 
    Gift, 
    TrendingUp, 
    Smartphone, 
    Share2, 
    Box,
    Users,
    Lock,
    LogOut,
    CreditCard,
    Bell,
    Download,
    FileText,
    Target,
    Send,
    Shield,
    Languages,
    Copy,
    Check,
    Globe,
    ChevronRight,
    CircleCheck,
    Coins,
    Sparkles,
    Play
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Mon Profil', href: '/settings/profile' },
];

const giftCodeForm = useForm({ code: '' });
const giftSuccessMsg = ref('');
const showInfoModal = ref<string | null>(null);

const claimGift = () => {
    giftSuccessMsg.value = '';
    giftCodeForm.clearErrors();
    
    giftCodeForm.post('/gift/claim', {
        onSuccess: () => {
            const flashSuccess = page.props.flash?.success;
            giftSuccessMsg.value = flashSuccess || 'Félicitations ! Injection d\'énergie réseau complétée avec succès.';
            giftCodeForm.reset();
            setTimeout(() => {
                giftSuccessMsg.value = '';
            }, 5000);
        }
    });
};

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num);
};

// Clipboard invitation code copy
const inviteCopied = ref(false);
const copyReferralCode = () => {
    navigator.clipboard.writeText(user.value?.referral_code || '');
    inviteCopied.value = true;
    setTimeout(() => inviteCopied.value = false, 2000);
};

// Dynamic statistics computed using completed transactions if available
const totalRecharged = computed(() => {
    return user.value.transactions?.filter(t => t.type === 'deposit' && t.status === 'completed')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0) || 0;
});

const totalWithdrawn = computed(() => {
    return Math.abs(user.value.transactions?.filter(t => t.type === 'withdrawal' && t.status === 'completed')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0)) || 0;
});

const rewardBalance = computed(() => {
    return user.value.transactions?.filter(t => (t.type === 'earnings' || t.type === 'salary') && t.status === 'completed')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0) || 0;
});

const todayRevenue = computed(() => {
    const today = new Date().toDateString();
    return user.value.transactions?.filter(t => {
        if (t.status !== 'completed') return false;
        if (t.type !== 'earnings' && t.type !== 'salary') return false;
        return new Date(t.created_at).toDateString() === today;
    }).reduce((sum, t) => sum + parseFloat(t.amount), 0) || 0;
});

const totalRevenue = computed(() => {
    return user.value.transactions?.filter(t => (t.type === 'earnings' || t.type === 'salary') && t.status === 'completed')
        .reduce((sum, t) => sum + parseFloat(t.amount), 0) || 0;
});

// Menu grid elements as requested
const menuGrid = [
    { title: 'Commandes', icon: FileText, href: '/commandes', color: 'text-purple-400' },
    { title: 'Gains', icon: TrendingUp, href: '/gains', color: 'text-purple-400' },
    { title: 'Numéros mobiles', icon: CreditCard, href: '/settings/mobile-numbers', color: 'text-purple-400' },
    { title: 'Équipe', icon: Users, href: '/team', color: 'text-purple-400' },
    { title: 'Inviter', icon: Share2, href: '/share', color: 'text-purple-400' },
    { title: 'Coffre au Trésor', icon: Box, href: '/coffre-tresor', color: 'text-purple-400' },
    { title: 'Code PIN', icon: Shield, href: '/settings/withdrawal-password', color: 'text-purple-400' },
    { title: 'Chaîne', icon: Send, href: 'https://t.me/arm_holding', color: 'text-purple-400', isExternal: true },
    { title: 'Alertes', icon: Bell, href: '/announcements', color: 'text-purple-400' },
    { title: 'Tirage', icon: Target, action: 'tirage', color: 'text-purple-400' },
    { title: 'Télécharger', icon: Download, action: 'download', color: 'text-purple-400' },
    { title: 'Mot de passe', icon: Lock, href: '/settings/security', color: 'text-purple-400' }
];

const handleItemClick = (item: any) => {
    if (item.action === 'tirage') {
        router.visit('/tirage');
    } else if (item.action === 'download') {
        // Déclenche directement le téléchargement de l'APK
        const a = document.createElement('a');
        a.href = '/downloads/arm-holding.apk';
        a.download = 'arm-holding.apk';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    } else if (item.isExternal) {
        window.open(item.href, '_blank');
    } else if (item.href) {
        router.visit(item.href);
    }
};

const handleLogout = () => {
    router.post('/logout');
};

const { containerRef } = useRevealAnimation();

// Watch to freeze scroll on modal activation
watch(showInfoModal, (newVal) => {
    if (newVal) {
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
    <Head title="Profil" />
    <AppLayout :breadcrumbs="breadcrumbs" class="bg-black">
        <div class="relative min-h-screen text-white pb-28 pt-4">
            
            <!-- Glow background items -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
                <div class="absolute top-[5%] left-[10%] w-72 h-72 rounded-full bg-purple-500/5 blur-[100px]"></div>
                <div class="absolute top-[40%] right-[5%] w-80 h-80 rounded-full bg-purple-600/5 blur-[120px]"></div>
            </div>
 
            <!-- Profile Content Wrapper -->
            <div ref="containerRef" class="relative z-10 w-full max-w-xl mx-auto px-4 flex flex-col gap-5">
                
                <!-- HEADER: User Profile Info Banner -->
                <div data-animate="fade-down" class="flex items-center justify-between mt-3 bg-gradient-to-r from-cyan-950/20 via-black/40 to-transparent p-3 rounded-2xl border border-cyan-500/10">
                    <div class="flex items-center gap-3">
                        <!-- Neon AI brain / Neural avatar -->
                        <div class="relative shrink-0">
                            <div class="w-16 h-16 rounded-full border-2 border-cyan-400/80 bg-black overflow-hidden shadow-[0_0_15px_rgba(6,182,212,0.4)] flex items-center justify-center">
                                <img src="/images/neural_ai_avatar.png" alt="Node Neural AI" class="w-full h-full object-cover opacity-90" />
                            </div>
                            <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-cyan-500 rounded-full border-2 border-black flex items-center justify-center shadow-lg">
                                <Check class="h-3 w-3 text-black font-black" :stroke-width="2.5" />
                            </div>
                        </div>
                        
                        <!-- User phone, level badge, referral code -->
                        <div class="flex flex-col">
                            <h2 class="text-lg font-black text-cyan-400 tracking-wide font-mono select-all">
                                {{ user.phone }}
                            </h2>
                            <div 
                                @click="showInfoModal = 'level'" 
                                class="mt-1 flex items-center gap-1 border border-cyan-500/30 bg-cyan-950/30 text-cyan-400 font-extrabold text-[9px] px-2 py-0.5 rounded-full w-max cursor-pointer hover:bg-cyan-500/10 transition-colors uppercase tracking-wider animate-pulse"
                            >
                                <span>{{ user.role === 'admin' ? 'ADMINISTRATEUR' : 'MEMBRE VIP ' + (user.vip_level !== undefined ? user.vip_level : 0) }}</span>
                                <ChevronRight class="h-2.5 w-2.5" :stroke-width="2.5" />
                            </div>
                            
                            <!-- Invitation code -->
                            <div 
                                @click="copyReferralCode" 
                                class="mt-1 text-[10px] text-white/70 font-bold tracking-wide flex items-center gap-1.5 cursor-pointer hover:text-cyan-400 transition-colors"
                            >
                                <span>Code d'invitation <span class="text-cyan-400 font-mono underline font-bold">{{ user.referral_code }}</span></span>
                                <component :is="inviteCopied ? Check : Copy" class="h-3 w-3 text-cyan-400 shrink-0" :stroke-width="2.5" />
                            </div>
                        </div>
                    </div>

                    <!-- Language selector -->
                    <div class="border border-white/10 bg-white/5 px-2.5 py-1 rounded-xl text-[10px] font-bold tracking-wide flex items-center gap-1.5 hover:border-cyan-400/50 transition-colors cursor-pointer uppercase select-none">
                        <Globe class="h-3.5 w-3.5 text-cyan-400" :stroke-width="2.5" />
                        <span>FR</span>
                    </div>
                </div>

                <!-- STATS 1: Solde total & Revenu total -->
                <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#060b13] to-[#02050c] border border-cyan-500/15 rounded-2xl px-6 py-5 flex items-center justify-between shadow-lg">
                    <!-- Column 1: Solde total -->
                    <div class="flex flex-col w-1/2 text-center border-r border-cyan-500/10 pr-2">
                        <span class="text-[20px] font-black text-cyan-400 tracking-tight font-mono">
                            {{ formatXAF(user.balance) }}
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1.5">Solde total</span>
                    </div>
                    <!-- Column 2: Revenu total -->
                    <div class="flex flex-col w-1/2 text-center pl-2">
                        <span class="text-[20px] font-black text-emerald-400 tracking-tight font-mono">
                            {{ formatXAF(totalRevenue) }}
                        </span>
                        <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-1.5">Revenu total</span>
                    </div>
                </div>

                <!-- STATS 2: Sub-statistics 2x2 Grid -->
                <div data-animate="fade-up" data-delay="150" class="bg-[#070a13] border border-purple-500/10 rounded-2xl overflow-hidden shadow-lg">
                    <div class="grid grid-cols-2">
                        <!-- Top Left -->
                        <div class="p-4 flex flex-col text-center border-r border-b border-purple-500/10">
                            <span class="text-sm font-black text-white font-mono">{{ formatXAF(totalRecharged) }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-1">Compte de recharge</span>
                        </div>
                        <!-- Top Right -->
                        <div class="p-4 flex flex-col text-center border-b border-purple-500/10">
                            <span class="text-sm font-black text-white font-mono">{{ formatXAF(rewardBalance) }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-1">Compte de récompense</span>
                        </div>
                        <!-- Bottom Left -->
                        <div class="p-4 flex flex-col text-center border-r border-purple-500/10">
                            <span class="text-sm font-black text-white font-mono">{{ formatXAF(totalWithdrawn) }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-1">Retrait total</span>
                        </div>
                        <!-- Bottom Right -->
                        <div class="p-4 flex flex-col text-center">
                            <span class="text-sm font-black text-white font-mono">{{ formatXAF(todayRevenue) }}</span>
                            <span class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-1">Revenu d'aujourd'hui</span>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS: Recharge, Retirer, Recharges, Retraits -->
                <div data-animate="fade-up" data-delay="200" class="grid grid-cols-4 gap-2.5">
                    <!-- Recharger -->
                    <Link 
                        href="/recharger"
                        class="flex flex-col items-center justify-center gap-1.5 bg-[#070a13] border border-purple-500/10 py-3 rounded-2xl shadow hover:border-purple-400/50 hover:bg-purple-950/10 transition-all group"
                    >
                        <div class="w-10 h-10 rounded-full border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400 shadow-[0_0_10px_rgba(168,85,247,0.1)] group-hover:scale-105 transition-transform">
                            <Coins class="h-5 w-5" :stroke-width="2.5" />
                        </div>
                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wide">Recharger</span>
                    </Link>

                    <!-- Retirer -->
                    <Link 
                        href="/retirer"
                        class="flex flex-col items-center justify-center gap-1.5 bg-[#070a13] border border-purple-500/10 py-3 rounded-2xl shadow hover:border-purple-400/50 hover:bg-purple-950/10 transition-all group"
                    >
                        <div class="w-10 h-10 rounded-full border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400 shadow-[0_0_10px_rgba(168,85,247,0.1)] group-hover:scale-105 transition-transform">
                            <CreditCard class="h-5 w-5" :stroke-width="2.5" />
                        </div>
                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wide">Retirer</span>
                    </Link>

                    <!-- Recharges -->
                    <Link 
                        href="/recharger"
                        class="flex flex-col items-center justify-center gap-1.5 bg-[#070a13] border border-purple-500/10 py-3 rounded-2xl shadow hover:border-purple-400/50 hover:bg-purple-950/10 transition-all group"
                    >
                        <div class="w-10 h-10 rounded-full border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400 shadow-[0_0_10px_rgba(168,85,247,0.1)] group-hover:scale-105 transition-transform">
                            <TrendingUp class="h-5 w-5" :stroke-width="2.5" />
                        </div>
                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wide">Recharges</span>
                    </Link>

                    <!-- Retraits -->
                    <Link 
                        href="/retirer"
                        class="flex flex-col items-center justify-center gap-1.5 bg-[#070a13] border border-purple-500/10 py-3 rounded-2xl shadow hover:border-purple-400/50 hover:bg-purple-950/10 transition-all group"
                    >
                        <div class="w-10 h-10 rounded-full border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400 shadow-[0_0_10px_rgba(168,85,247,0.1)] group-hover:scale-105 transition-transform">
                            <Smartphone class="h-5 w-5" :stroke-width="2.5" />
                        </div>
                        <span class="text-[10px] font-bold text-purple-400 uppercase tracking-wide">Retraits</span>
                    </Link>
                </div>

                <!-- REDEEM GIFT CODE: Échanger le Code Cadeau -->
                <div data-animate="fade-up" data-delay="250" class="bg-gradient-to-r from-[#060b13] to-[#07140f] border border-emerald-500/15 rounded-2xl p-4.5 shadow-lg relative overflow-hidden">
                    <div class="absolute right-[-10%] top-[-10%] w-24 h-24 bg-emerald-500/5 rounded-full blur-2xl"></div>
                    
                    <div class="flex items-center gap-3">
                        <!-- Round emerald gift icon badge -->
                        <div class="w-11 h-11 shrink-0 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 shadow-[0_0_12px_rgba(16,185,129,0.2)]">
                            <Gift class="h-5 w-5 shrink-0" :stroke-width="2.5" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-xs font-black text-white uppercase tracking-wider">Échanger le Code Cadeau</h3>
                            <p class="text-[9px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Entrez le code pour réclamer les récompenses</p>
                        </div>
                    </div>

                    <!-- Gift Success Banner -->
                    <div v-if="giftSuccessMsg" class="mt-3.5 p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-[10px] rounded-xl font-bold font-mono flex items-center gap-2 animate-pulse shadow-[0_0_15px_rgba(16,185,129,0.1)]">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                        <span>{{ giftSuccessMsg }}</span>
                    </div>

                    <!-- Gift Error Banner -->
                    <div v-if="giftCodeForm.errors.code" class="mt-3.5 p-3 bg-rose-500/10 border border-rose-500/30 text-rose-400 text-[10px] rounded-xl font-bold font-mono flex items-center gap-2 shadow-[0_0_15px_rgba(244,63,94,0.1)] animate-shake">
                        <span class="h-1.5 w-1.5 rounded-full bg-rose-400 shrink-0"></span>
                        <span>{{ giftCodeForm.errors.code }}</span>
                    </div>

                    <!-- Form input and Claim Button -->
                    <form @submit.prevent="claimGift" class="mt-4 flex items-center gap-2.5">
                        <input 
                            v-model="giftCodeForm.code"
                            type="text" 
                            placeholder="ENTREZ LE CODE CADEAU" 
                            class="flex-1 bg-black/60 border border-emerald-500/20 rounded-xl px-4 h-11 text-white placeholder:text-white/20 text-center focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-400/20 transition-all text-xs font-black uppercase font-mono tracking-wider"
                        />
                        <button 
                            type="submit" 
                            :disabled="giftCodeForm.processing"
                            class="bg-emerald-500/10 border border-emerald-500/30 hover:bg-emerald-500 hover:text-black hover:shadow-[0_0_15px_rgba(16,185,129,0.4)] text-emerald-400 font-extrabold px-5 h-11 rounded-xl text-xs uppercase tracking-widest transition-all duration-300 disabled:opacity-50 shrink-0"
                        >
                            {{ giftCodeForm.processing ? '...' : 'Réclamer' }}
                        </button>
                    </form>
                </div>

                <!-- NAVIGATION GRID: Grid of 12 items + stand-alone Quitter button -->
                <div data-animate="fade-up" data-delay="300" class="bg-[#070a13]/80 border border-purple-500/10 rounded-3xl p-4.5 shadow-lg backdrop-blur-sm relative">
                    <div class="grid grid-cols-4 gap-y-6 gap-x-2">
                        <div 
                            v-for="item in menuGrid" 
                            :key="item.title"
                            @click="handleItemClick(item)"
                            class="flex flex-col items-center justify-center gap-2 text-center group cursor-pointer"
                        >
                            <div class="w-12 h-12 rounded-2xl border border-purple-500/10 bg-purple-950/5 flex items-center justify-center group-hover:border-purple-400/40 group-hover:bg-purple-500/5 group-hover:shadow-[0_0_15px_rgba(168,85,247,0.1)] transition-all duration-300">
                                <component :is="item.icon" class="h-5.5 w-5.5 text-purple-400" :stroke-width="2.3" />
                            </div>
                            <span class="text-[10px] font-extrabold text-slate-300 group-hover:text-white transition-colors tracking-wide leading-tight select-none">
                                {{ item.title }}
                            </span>
                        </div>

                        <!-- Quitter (Orange button standalone at the bottom left) -->
                        <div 
                            @click="handleLogout"
                            class="flex flex-col items-center justify-center gap-2 text-center group cursor-pointer"
                        >
                            <div class="w-12 h-12 rounded-2xl border border-orange-500/20 bg-orange-950/5 flex items-center justify-center group-hover:border-orange-500/50 group-hover:bg-orange-500/5 group-hover:shadow-[0_0_15px_rgba(249,115,22,0.1)] transition-all duration-300">
                                <LogOut class="h-5.5 w-5.5 text-orange-500" :stroke-width="2.3" />
                            </div>
                            <span class="text-[10px] font-extrabold text-orange-500 transition-colors tracking-wide leading-tight select-none">
                                Quitter
                            </span>
                        </div>
                    </div>
                </div>

            </div>

            <!-- INFORMATION DIALOGS / MODALS -->
            <!-- Tirage modal -->
            <Teleport to="body">
                <div v-if="showInfoModal === 'tirage'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                    <div class="w-full max-w-xs bg-[#0f111a] border border-purple-500/30 rounded-2xl overflow-hidden shadow-2xl relative">
                        <div class="p-6 text-center">
                            <div class="w-12 h-12 rounded-full border border-purple-500/30 bg-purple-950/20 flex items-center justify-center text-purple-400 mb-4 mx-auto animate-bounce">
                                <Target class="h-6 w-6 animate-spin" :stroke-width="2.5" />
                            </div>
                            <h3 class="text-sm font-black text-white uppercase tracking-wider">Tirage au Sort VIP</h3>
                            <p class="text-[10px] text-slate-400 mt-2 font-mono leading-relaxed bg-black/40 border border-purple-500/10 p-3 rounded-xl">
                                Le tirage au sort hebdomadaire est réservé aux membres VIP. Prochain lancement dans 48 heures.
                            </p>
                            <button 
                                @click="showInfoModal = null" 
                                class="mt-5 w-full py-2.5 rounded-xl bg-purple-500 text-black font-extrabold uppercase tracking-widest text-[10px] hover:bg-purple-400 transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)]"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Download modal -->
            <Teleport to="body">
                <div v-if="showInfoModal === 'download'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                    <div class="w-full max-w-xs bg-[#0f111a] border border-purple-500/30 rounded-2xl overflow-hidden shadow-2xl relative">
                        <div class="p-6 text-center">
                            <div class="w-12 h-12 rounded-full border border-purple-500/30 bg-purple-950/20 flex items-center justify-center text-purple-400 mb-4 mx-auto">
                                <Download class="h-6 w-6" :stroke-width="2.5" />
                            </div>
                            <h3 class="text-sm font-black text-white uppercase tracking-wider">Téléchargement</h3>
                            <p class="text-[10px] text-slate-400 mt-2 font-mono leading-relaxed bg-black/40 border border-purple-500/10 p-3 rounded-xl">
                                L'application Android native (.APK) est disponible pour une expérience optimisée ultra-fluide.
                            </p>
                            <a 
                                href="/downloads/arm-holding.apk" 
                                download
                                class="mt-5 block text-center w-full py-2.5 rounded-xl bg-purple-500 text-black font-extrabold uppercase tracking-widest text-[10px] hover:bg-purple-400 transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)]"
                            >
                                Télécharger l'APK
                            </a>
                            <button 
                                @click="showInfoModal = null" 
                                class="mt-2.5 w-full py-2.5 rounded-xl bg-white/5 border border-white/10 text-white font-bold uppercase tracking-widest text-[10px] hover:bg-white/10 transition-all"
                            >
                                Plus tard
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Level badge explanation modal -->
            <Teleport to="body">
                <div v-if="showInfoModal === 'level'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                    <div class="w-full max-w-xs bg-[#0f111a] border border-purple-500/30 rounded-2xl overflow-hidden shadow-2xl relative">
                        <div class="p-6 text-center">
                            <div class="w-12 h-12 rounded-full border border-purple-500/30 bg-purple-950/20 flex items-center justify-center text-purple-400 mb-4 mx-auto">
                                <Sparkles class="h-6 w-6" :stroke-width="2.5" />
                            </div>
                            <h3 class="text-sm font-black text-white uppercase tracking-wider">Niveau du Compte</h3>
                            <div class="text-[10px] text-slate-400 mt-2 font-mono leading-relaxed bg-black/40 border border-purple-500/10 p-3 rounded-xl text-left space-y-2">
                                <div class="flex justify-between border-b border-white/5 pb-1"><span class="font-bold">Niveau Actuel:</span><span class="text-purple-400 font-extrabold uppercase">VIP {{ user.vip_level || 1 }}</span></div>
                                <div class="flex justify-between"><span class="font-bold">Commission Réseau:</span><span class="text-purple-400 font-extrabold font-mono">10% - 5% - 2%</span></div>
                            </div>
                            <button 
                                @click="showInfoModal = null" 
                                class="mt-5 w-full py-2.5 rounded-xl bg-purple-500 text-black font-extrabold uppercase tracking-widest text-[10px] hover:bg-purple-400 transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)]"
                            >
                                Fermer
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <!-- Assistant modal dialogue -->
            <Teleport to="body">
                <div v-if="showInfoModal === 'assistant'" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                    <div class="w-full max-w-xs bg-[#0f111a] border border-purple-500/30 rounded-2xl overflow-hidden shadow-2xl relative">
                        <div class="p-6 text-center">
                            <div class="w-16 h-16 rounded-full border border-purple-400 bg-slate-900 overflow-hidden mb-4 mx-auto shadow-[0_0_15px_rgba(168,85,247,0.5)]">
                                <img src="/images/avatar_assistant.png" alt="ARM Assistant" class="w-full h-full object-cover" />
                            </div>
                            <h3 class="text-sm font-black text-purple-400 uppercase tracking-wider">Assistant IA ARM</h3>
                            <p class="text-[10px] text-slate-300 mt-2 font-mono leading-relaxed bg-black/40 border border-purple-500/10 p-3 rounded-xl text-left">
                                "Bonjour ! Je suis votre assistante d'infrastructure réseau. Vos serveurs tournent à plein régime avec un taux d'efficacité de 99.8%. Avez-vous réclamé vos profits journaliers ?"
                            </p>
                            <div class="flex gap-2 mt-5">
                                <Link 
                                    href="/generate" 
                                    class="flex-1 py-2.5 rounded-xl bg-purple-500 text-black font-extrabold uppercase tracking-widest text-[9px] hover:bg-purple-400 transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)] flex items-center justify-center gap-1"
                                    @click="showInfoModal = null"
                                >
                                    <Play class="h-3 w-3 fill-current" />
                                    Console AI
                                </Link>
                                <button 
                                    @click="showInfoModal = null" 
                                    class="flex-1 py-2.5 rounded-xl bg-white/5 border border-white/10 text-white font-bold uppercase tracking-widest text-[9px] hover:bg-white/10 transition-all"
                                >
                                    Revenir
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Teleport>

        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}
.animate-shake {
    animation: shake 0.3s ease-in-out 2;
}
</style>