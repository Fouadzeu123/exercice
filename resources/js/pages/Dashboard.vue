<script setup lang="ts">
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { isNative } from '@/plugins/capacitor';
import { Head, Link, usePage, router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

// --- Import des icônes Heroicons & Lucide ---
import {
  XMarkIcon as XIcon,
  FunnelIcon as FilterIcon,
  ComputerDesktopIcon as CpuIcon
} from '@heroicons/vue/24/outline';

import {
  Coins,
  CreditCard,
  CheckCircle,
  Users,
  Trophy,
  Share2,
  Box,
  Bell,
  BrainCircuit,
  TrendingUp,
  Volume2,
  Radio,
  FileText,
  AlertTriangle
} from 'lucide-vue-next';

import { t } from '@/utils/trans';
import axios from 'axios';

// --- TYPES & PROPS ---
const props = defineProps<{
    activeUserNodesCount?: number;
    nodes: Array<{
        id: number;
        name: string;
        amount: number;
        generation_profit: number;
        technology_level: number;
        duration: number;
        stock_quantity: number | null;
        max_purchase_limit?: number;
        image_url?: string;
    }>;
    vaultPlans?: Array<{
        id: number;
        name: string;
        fixed_investment_amount: string;
        fixed_return: string;
        profit_amount: string;
        duration: number;
        image: string | null;
        active: boolean;
    }>;
    avipProducts?: Array<{
        id: number;
        name: string;
        description: string;
        amount: number;
        daily_salary: number;
        required_vip_level: number;
        avip_level: number;
        image: string;
        active: boolean;
    }>;
    stats?: {
        total_generated: number;
        vip_level: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'tableau de bord', href: '/dashboard' }];
const page = usePage();
const user = computed(() => page.props.auth.user);

// --- DONNÉES DYNAMIQUES ---
const withdrawals = ref([
    { phone: '**** 33411', amount: 4700, time: 'à l\'instant' },
    { phone: '**** 96599', amount: 141000, time: 'il y a 2 min' },
    { phone: '**** 11204', amount: 15000, time: 'il y a 5 min' },
    { phone: '**** 44002', amount: 5000, time: 'il y a 8 min' },
    { phone: '**** 88123', amount: 23500, time: 'il y a 12 min' },
    { phone: '**** 55992', amount: 47000, time: 'il y a 15 min' },
]);

const tickerText = "ARM NEURAL LINK : L'infrastructure de puces IA Neoverse V3 redéfinit la puissance décentralisée de demain  •  SALAIRE VIP : Réclamez votre dividende énergétique quotidien en activant votre console  •  ROUE COSMIQUE : Participez au tirage au sort exclusif de 1 777 777 XAF  •  ACCÉLÉRATEURS AVIP : Louez des processeurs haute performance adaptés à votre niveau VIP  •  ";

const allowedAmounts = [1500, 5000, 15000, 47000, 141000, 235000, 470000];
const phones = ['**** 8821', '**** 1029', '**** 4412', '**** 9938', '**** 2211', '**** 7765', '**** 3340'];

let liveInterval: number | null = null;

const addLiveWithdrawal = () => {
    const randomPhone = phones[Math.floor(Math.random() * phones.length)];
    const randomAmount = allowedAmounts[Math.floor(Math.random() * allowedAmounts.length)];
    withdrawals.value.unshift({ phone: randomPhone, amount: randomAmount, time: 'à l\'instant' });
    if (withdrawals.value.length > 10) withdrawals.value.pop();
};

// --- ETAT ---
const showRentModal = ref<any | null>(null);
const activeCategory = ref('node');
const showDashboardErrorModal = ref(false);
const dashboardErrorMessage = ref('');

// --- ETATS POINTAGE & INVITATION ---
const showInviteModal = ref(false);
const inviteCopied = ref(false);
const checkinProcessing = ref(false);
const showCheckinSuccessModal = ref(false);
const checkinError = ref('');
const showCheckinErrorModal = ref(false);

const handleDailyCheckin = async () => {
    if (checkinProcessing.value) return;
    checkinProcessing.value = true;
    try {
        const res = await axios.post('/daily-checkin');
        if (res.data.success) {
            showCheckinSuccessModal.value = true;
            router.reload({ only: ['auth'] });
        }
    } catch (e: any) {
        checkinError.value = e.response?.data?.error || "Échec de la synchronisation du nœud principal.";
        showCheckinErrorModal.value = true;
    } finally {
        checkinProcessing.value = false;
    }
};

const copyReferralLink = () => {
    const link = window.location.origin + '/register?ref=' + (user.value?.referral_code || '');
    navigator.clipboard.writeText(link);
    inviteCopied.value = true;
    setTimeout(() => inviteCopied.value = false, 2000);
};

const copyReferralCode = () => {
    navigator.clipboard.writeText(user.value?.referral_code || '');
    inviteCopied.value = true;
    setTimeout(() => inviteCopied.value = false, 2000);
};

const referralLink = computed(() => {
    if (typeof window !== 'undefined') {
        return window.location.origin + '/register?ref=' + (user.value?.referral_code || '');
    }
    return 'https://arm-holding.com/register?ref=' + (user.value?.referral_code || '');
});

const isLoading = ref(true);

const combinedProducts = computed(() => {
    const mappedNodes = props.nodes.map(n => ({
        ...n,
        isVault: false,
        isAvip: false,
        required_active_referrals: n.required_active_referrals ?? 0,
        category: n.technology_level >= 4 ? 'avip' : (n.is_limited ? 'limited' : 'node')
    }));
    const mappedVaults = (props.vaultPlans || []).map(v => ({
        id: v.id,
        name: v.name,
        amount: parseFloat(v.fixed_investment_amount),
        generation_profit: parseFloat(v.profit_amount) / v.duration,
        technology_level: 0,
        duration: v.duration,
        stock_quantity: null,
        isVault: true,
        isAvip: false,
        fixed_return: v.fixed_return,
        profit_amount: v.profit_amount,
        required_active_referrals: 0,
        category: 'vault'
    }));
    const mappedAvips = (props.avipProducts || []).map(a => ({
        id: a.id,
        name: a.name,
        amount: a.amount,
        generation_profit: a.daily_salary,
        technology_level: 0,
        duration: 7,
        stock_quantity: a.stock_quantity !== null ? Number(a.stock_quantity) : null,
        limited_purchase_count: a.limited_purchase_count !== null ? Number(a.limited_purchase_count) : null,
        isVault: false,
        isAvip: true,
        is_limited: !!a.is_limited,
        required_active_referrals: a.required_active_referrals ?? 0,
        category: a.is_limited ? 'limited' : 'avip',
        image_url: a.image,
        description: a.description,
        required_vip_level: a.required_vip_level,
        avip_level: a.avip_level,
    }));

    if (activeCategory.value === 'all') {
        return [...mappedNodes, ...mappedAvips, ...mappedVaults];
    }
    if (activeCategory.value === 'node') {
        return mappedNodes.filter(n => n.technology_level >= 0 && n.technology_level <= 3);
    }
    if (activeCategory.value === 'avip') {
        return [
            ...mappedAvips,
            ...mappedNodes.filter(n => n.technology_level >= 4)
        ];
    }
    if (activeCategory.value === 'limited') {
        return [
            ...mappedNodes.filter(n => n.is_limited),
            ...mappedAvips.filter(a => a.is_limited)
        ];
    }
    if (activeCategory.value === 'vault') {
        return mappedVaults;
    }
    return [];
});

// --- LOGIQUE GENERATION (REMOVED - HANDLED IN DEDICATED CONSOLE VIEW)
// --- LOCATION ---
const rentForm = useForm({});
const confirmRentNode = (node: any) => {
    rentForm.post(`/nodes/${node.id}/rent`, {
        onSuccess: () => { showRentModal.value = null; router.reload(); },
        onError: (err: any) => {
            dashboardErrorMessage.value = err.error || "Solde insuffisant pour louer ce nœud.";
            showDashboardErrorModal.value = true;
        }
    });
};
const confirmRentVault = (vault: any) => {
    rentForm.post(`/vaults/${vault.id}/invest`, {
        onSuccess: () => { showRentModal.value = null; router.reload(); },
        onError: (err: any) => {
            dashboardErrorMessage.value = err.error || "Solde insuffisant pour investir dans ce coffre.";
            showDashboardErrorModal.value = true;
        }
    });
};

// --- UTILS ---
const formatXAF = (val: number | string) => new Intl.NumberFormat('fr-FR').format(Number(val)) + ' xaf';
const FALLBACK_IMG = 'https://images.unsplash.com/photo-1587202372775-e229f172b9d7?w=400&auto=format';

// Convert relative /uploads/ paths to absolute URLs (needed for mobile WebViews)
const resolveImageUrl = (url: string | null | undefined): string | null => {
    if (!url) return null;
    if (url.startsWith('http://') || url.startsWith('https://')) return url;

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

    if (url.startsWith('/')) {
        return cleanBaseUrl + url;
    }
    return cleanBaseUrl + '/' + url;
};

const getProductImage = (node: any) => resolveImageUrl(node.image || node.image_url) || FALLBACK_IMG;
const onImgError = (e: Event, fallback: string) => {
    const img = e.target as HTMLImageElement;
    if (img.src !== fallback) img.src = fallback;
};

// --- PARTICULES LUMINEUSES GLOBALES ---
let canvas: HTMLCanvasElement | null = null;
let ctx: CanvasRenderingContext2D | null = null;
let particlesAnimationId: number | null = null;
let particles: Array<{ x: number; y: number; vx: number; vy: number; size: number; color: string }> = [];

const initCosmicParticles = () => {
  canvas = document.getElementById('cosmicDashboardParticles') as HTMLCanvasElement;
  if (!canvas) return;
  ctx = canvas.getContext('2d');
  if (!ctx) return;

  const resize = () => {
    if (!canvas) return;
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
  };
  window.addEventListener('resize', resize);
  resize();

  const particleCount = 80;
  particles = [];
  for (let i = 0; i < particleCount; i++) {
    particles.push({
      x: Math.random() * canvas.width,
      y: Math.random() * canvas.height,
      vx: (Math.random() - 0.5) * 0.4,
      vy: (Math.random() - 0.5) * 0.4,
      size: Math.random() * 2 + 1,
      color: `rgba(168, 85, 247, ${Math.random() * 0.5 + 0.3})`
    });
  }

  const animate = () => {
    if (!ctx || !canvas) return;
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    for (let p of particles) {
      p.x += p.vx;
      p.y += p.vy;
      if (p.x < 0) p.x = canvas.width;
      if (p.x > canvas.width) p.x = 0;
      if (p.y < 0) p.y = canvas.height;
      if (p.y > canvas.height) p.y = 0;
    }

    // Lignes entre particules proches
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);
        if (dist < 120) {
          const opacity = (1 - dist / 120) * 0.15;
          ctx.strokeStyle = `rgba(168, 85, 247, ${opacity})`;
          ctx.lineWidth = 0.5;
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          ctx.stroke();
        }
      }
    }

    for (let p of particles) {
      ctx.beginPath();
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      ctx.fillStyle = p.color;
      ctx.fill();
    }

    particlesAnimationId = requestAnimationFrame(animate);
  };

  animate();
};

// --- REDIRECTION DETAILS PRODUIT ---
const viewProductDetails = (node: any) => {
    const type = node.isVault ? 'vault' : (node.isAvip ? 'avip' : 'node');
    router.visit(`/products/${type}/${node.id}`);
};

const showCheckinConsoleModal = ref(false);
const handleCheckinTrigger = () => {
    showCheckinConsoleModal.value = false;
    handleDailyCheckin();
};

// Reveal animation
const { containerRef } = useRevealAnimation();

const isMobileApp = ref(false);

onMounted(() => {
    isMobileApp.value = isNative();
    liveInterval = window.setInterval(addLiveWithdrawal, 3500);
    if (!isMobileApp.value) {
        initCosmicParticles();
    }
    setTimeout(() => {
        isLoading.value = false;
    }, 750);
});

onUnmounted(() => {
    if (liveInterval) clearInterval(liveInterval);
    if (particlesAnimationId) cancelAnimationFrame(particlesAnimationId);
    document.body.style.overflow = '';
});

// Watch to freeze scroll on modal activation
watch(
    [showCheckinConsoleModal, showInviteModal, showCheckinSuccessModal, showCheckinErrorModal, showDashboardErrorModal],
    ([newCheckin, newInvite, newSuccess, newError, newGlobalError]) => {
        if (newCheckin || newInvite || newSuccess || newError || newGlobalError) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
);
</script>

<template>
    <Head title="arm holding" />
    <AppLayout :breadcrumbs="breadcrumbs" class="bg-gradient-to-b from-[#05020c] to-[#0e061b] text-gray-200 selection:bg-purple-500 selection:text-white">

        <!-- fond d'écran sobre avec particules cosmiques -->
        <div class="fixed inset-0 z-0 pointer-events-none overflow-hidden">
            <img src="https://images.unsplash.com/photo-1558494949-ef526b0042a0?q=80&w=2070&auto=format&fit=crop" class="absolute inset-0 w-full h-full object-cover opacity-20" alt="background">
            <div class="absolute inset-0 bg-gradient-to-b from-[#05020c]/80 via-[#05020c]/60 to-[#0e061b]/90"></div>
            <!-- Global cosmic particles -->
            <canvas v-if="!isMobileApp" id="cosmicDashboardParticles" class="absolute inset-0 w-full h-full opacity-45"></canvas>
            <!-- cercles lumineux statiques -->
            <div class="absolute top-20 left-1/4 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-1/4 w-96 h-96 bg-fuchsia-500/5 rounded-full blur-3xl"></div>
        </div>

        <div ref="containerRef" class="relative z-10 min-h-screen pb-24">

            <!-- SKELETON SCREEN LOADING -->
            <div v-if="isLoading" class="space-y-6 px-4 pt-6">
                <!-- Shimmering Header -->
                <div class="flex justify-between items-end mb-4 pt-4">
                    <div class="space-y-2 flex-1">
                        <div class="h-6 w-36 skeleton-shimmer"></div>
                        <div class="h-4 w-28 skeleton-shimmer"></div>
                    </div>
                    <div class="h-10 w-28 skeleton-shimmer"></div>
                </div>

                <!-- Shimmering Ticker -->
                <div class="h-10 w-full rounded-xl skeleton-shimmer mb-4"></div>

                <!-- Shimmering Banner -->
                <div class="h-52 w-full rounded-2xl skeleton-shimmer mb-6 border border-white/5"></div>

                <!-- Shimmering Icon Grid -->
                <div class="bg-[#0f071d]/60 border border-white/10 rounded-2xl p-4 grid grid-cols-4 gap-4">
                    <div v-for="i in 8" :key="i" class="flex flex-col items-center gap-2 py-2">
                        <div class="h-10 w-10 rounded-full skeleton-shimmer"></div>
                        <div class="h-3 w-12 skeleton-shimmer"></div>
                    </div>
                </div>

                <!-- Shimmering Active Node -->
                <div class="h-28 w-full rounded-2xl skeleton-shimmer border border-white/5 mb-6"></div>

                <!-- Shimmering Marketplace Header -->
                <div class="flex justify-between items-center mb-4">
                    <div class="h-5 w-44 skeleton-shimmer"></div>
                    <div class="h-5 w-5 skeleton-shimmer"></div>
                </div>

                <!-- Shimmering Filters -->
                <div class="flex gap-2 mb-4 overflow-hidden">
                    <div v-for="i in 5" :key="i" class="h-8 w-20 rounded-lg skeleton-shimmer shrink-0"></div>
                </div>

                <!-- Shimmering Product Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="i in 2" :key="i" class="h-72 rounded-3xl skeleton-shimmer border border-white/5"></div>
                </div>
            </div>

            <!-- DYNAMIC DASHBOARD DATA -->
            <div v-else class="transition-all duration-500 ease-out">
                <div data-animate="fade-down" class="p-5 pt-8 flex justify-between items-end mb-4">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-gray-100">arm holding</h1>
                        <p class="text-xs text-cyan-400 font-mono mt-1">système en ligne v4.8</p>
                    </div>
                </div>

            <!-- BANDEAU DÉFILANT -->
            <div data-animate="fade-up" data-delay="100" class="mx-4 mb-4 bg-black/40 backdrop-blur-sm border border-cyan-500/30 rounded-xl py-2 overflow-hidden relative glow-border">
                <div class="flex items-center gap-3 absolute left-0 top-0 h-full bg-gradient-to-r from-[#05020c] to-transparent w-12 z-10"></div>
                <div class="flex items-center gap-3 absolute right-0 top-0 h-full bg-gradient-to-l from-[#05020c] to-transparent w-12 z-10"></div>
                <div class="flex items-center gap-2 animate-marquee whitespace-nowrap">
                    <Volume2 class="w-4 h-4 text-cyan-400 inline-block mx-2 shrink-0" />
                    <span class="text-xs text-gray-300 font-mono">{{ tickerText }}</span>
                    <Volume2 class="w-4 h-4 text-cyan-400 inline-block mx-2 shrink-0" />
                    <span class="text-xs text-gray-300 font-mono">{{ tickerText }}</span>
                </div>
            </div>

            <!-- bannière image -->
            <div data-animate="scale-up" data-delay="200" class="relative h-52 overflow-hidden mb-6 rounded-2xl border border-cyan-500/10 glow-border mx-4">
                <img src="/images/logo.jpg" class="absolute inset-0 w-full h-full object-cover opacity-40" alt="ARM Holding Logo">
                <div class="absolute inset-0 bg-gradient-to-t from-[#05020c] via-transparent to-black/60"></div>
                <div class="absolute inset-0 pointer-events-none">
                    <div class="absolute w-1 h-1 bg-cyan-400 rounded-full opacity-60 animate-pulse" style="top: 20%; left: 20%;"></div>
                    <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-cyan-500/50 to-transparent animate-scan"></div>
                </div>
                <div class="absolute inset-0 z-10 flex flex-col items-center justify-center text-center">
                    <div class="text-[10px] text-cyan-400 font-mono mb-1.5 tracking-widest uppercase animate-pulse">statut serveur</div>
                    <div class="text-lg font-black text-gray-100 uppercase tracking-wider">opérationnel</div>
                </div>
            </div>

            <!-- Main Responsive 3-Column Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 px-4 mb-8">

                <!-- Left Column (Main Panel) - occupies 2 columns on desktop -->
                <div class="lg:col-span-2 space-y-6 flex flex-col justify-start">

                    <!-- grille d'icônes -->
                    <div data-animate="fade-up" data-delay="300" class="w-full bg-[#0f071d]/60 border border-white/10 rounded-2xl p-4 shadow-xl backdrop-blur-sm glow-border">
                        <div class="grid grid-cols-4 gap-3">
                            <Link href="/recharger" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <Coins class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">recharger</span>
                            </Link>
                            <Link href="/retirer" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <CreditCard class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">retirer</span>
                            </Link>
                            <button @click="showCheckinConsoleModal = true" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105 disabled:opacity-50">
                                <CheckCircle class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">pointage</span>
                            </button>
                            <Link href="/team" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <Users class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">équipe</span>
                            </Link>
                            <Link href="/vip" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <Trophy class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">vip</span>
                            </Link>
                            <Link href="/share" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <Share2 class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">inviter</span>
                            </Link>
                            <Link href="/presentation" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <FileText class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">Présentation</span>
                            </Link>
                            <Link href="/announcements" class="flex flex-col items-center justify-center gap-1.5 py-2 group transition-transform hover:scale-105">
                                <Bell class="w-5.5 h-5.5 text-cyan-400 group-hover:text-cyan-300 transition-colors" :stroke-width="2.6" />
                                <span class="text-[10px] font-medium text-cyan-400 group-hover:text-gray-100 select-none">news</span>
                            </Link>
                        </div>
                    </div>

                    <!-- section génération -->
                    <div data-animate="fade-up" data-delay="400" class="w-full bg-gradient-to-br from-[#0f071d]/90 via-[#150a2b]/80 to-[#0c0517]/90 border border-cyan-500/20 rounded-2xl p-5 relative overflow-hidden backdrop-blur-sm transition-all duration-300 hover:shadow-cyan-500/10 hover:shadow-xl hover:border-cyan-500/30 group glow-border">
                        <div class="absolute top-0 right-0 p-4 opacity-10 pointer-events-none group-hover:scale-110 transition-transform duration-500">
                            <BrainCircuit class="w-32 h-32 text-cyan-500 animate-pulse" />
                        </div>
                        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="h-2 w-2 rounded-full bg-cyan-400 animate-pulse"></span>
                                    <h3 class="text-xs font-black text-cyan-400 uppercase tracking-widest font-mono">Console de Co-traitement AI</h3>
                                </div>
                                <div class="text-sm font-extrabold text-white tracking-wide">
                                    {{ activeUserNodesCount && activeUserNodesCount > 0 ? `${activeUserNodesCount} serveur(s) de calcul connecté(s)` : 'Aucun serveur actif détecté' }}
                                </div>
                                <div class="text-xs text-gray-400 mt-1">
                                    Rendement cumulé :
                                    <span class="text-emerald-400 font-bold font-mono">
                                        +{{ formatXAF(stats?.daily_profit_rate || 0) }} / jour
                                    </span>
                                </div>
                            </div>
                            <div class="shrink-0 flex items-center w-full sm:w-auto">
                                <Link
                                    href="/generate"
                                    class="w-full text-center px-5 py-3 bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-400 text-black text-[10px] font-black uppercase tracking-wider rounded-xl transition-all shadow-[0_0_15px_rgba(6,182,212,0.35)] hover:shadow-[0_0_25px_rgba(6,182,212,0.6)]"
                                >
                                    Gérer mes Serveurs & Gains →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Cosmic Lucky Draw Promo Banner -->
                    <div data-animate="fade-up" data-delay="420" class="w-full bg-gradient-to-r from-purple-950/70 via-cyan-950/90 to-[#05020c]/70 border border-cyan-500/40 rounded-2xl p-5 relative overflow-hidden backdrop-blur-sm transition-all duration-300 hover:shadow-cyan-500/20 hover:shadow-lg glow-border">
                        <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,_var(--tw-gradient-stops))] from-cyan-500/10 via-transparent to-transparent opacity-60"></div>
                        <div class="absolute top-1/2 right-4 -translate-y-1/2 text-cyan-500/20 pointer-events-none">
                            <Trophy class="w-20 h-20 rotate-12 animate-pulse" />
                        </div>
                        <div class="relative z-10 flex flex-col sm:flex-row items-center justify-between gap-4">
                            <div class="text-center sm:text-left">
                                <div class="flex items-center justify-center sm:justify-start gap-2 mb-1">
                                    <span class="px-2 py-0.5 bg-cyan-400 text-black text-[9px] font-black uppercase tracking-widest rounded leading-none">EXCLUSIF</span>
                                    <span class="text-xs font-bold text-cyan-400 font-mono">Roue de la Fortune</span>
                                </div>
                                <h4 class="text-sm font-extrabold text-white uppercase tracking-wider">TIRAGE COSMIQUE ARM</h4>
                                <p class="text-[10px] text-gray-400 mt-1 leading-relaxed max-w-[280px]">
                                    Tentez votre chance pour remporter jusqu'à <span class="text-cyan-400 font-bold font-mono">1 777 777 XAF</span> !
                                </p>
                            </div>
                            <Link href="/tirage" class="w-full sm:w-auto px-5 py-2 bg-gradient-to-r from-cyan-600 to-purple-600 hover:from-cyan-500 hover:to-purple-500 text-white text-[10px] font-black uppercase tracking-widest rounded-xl transition-all shadow-[0_0_15px_rgba(6,182,212,0.4)] text-center">
                                LANCER LA ROUE
                            </Link>
                        </div>
                    </div>

                    <!-- BANDEAU DÉFILANT DES PRODUITS & RENDEMENTS -->
                    <div data-animate="fade-up" data-delay="450" class="w-full bg-[#0c0f1d]/50 backdrop-blur-sm border border-cyan-500/20 rounded-xl py-2.5 overflow-hidden relative glow-border">
                        <div class="flex items-center gap-3 absolute left-0 top-0 h-full bg-gradient-to-r from-[#05020c] to-transparent w-10 z-10"></div>
                        <div class="flex items-center gap-3 absolute right-0 top-0 h-full bg-gradient-to-l from-[#05020c] to-transparent w-10 z-10"></div>
                        <div class="flex items-center gap-6 animate-marquee whitespace-nowrap">
                            <div v-for="p in combinedProducts" :key="p.id + '-' + p.isVault" class="inline-flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                                <span class="text-[9px] font-black uppercase text-gray-200 font-mono">{{ p.name }}</span>
                                <span class="text-[9px] font-extrabold text-cyan-400 font-mono">{{ formatXAF(p.amount) }}</span>
                                <span class="text-[9px] font-bold text-emerald-400 font-mono">(+{{ formatXAF(p.generation_profit) }}/j)</span>
                                <span class="text-[9px] text-slate-500 font-bold">|</span>
                            </div>
                            <!-- Duplication pour défilement continu infini -->
                            <div v-for="p in combinedProducts" :key="'dup-' + p.id + '-' + p.isVault" class="inline-flex items-center gap-2">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 shrink-0"></span>
                                <span class="text-[9px] font-black uppercase text-gray-200 font-mono">{{ p.name }}</span>
                                <span class="text-[9px] font-extrabold text-cyan-400 font-mono">{{ formatXAF(p.amount) }}</span>
                                <span class="text-[9px] font-bold text-emerald-400 font-mono">(+{{ formatXAF(p.generation_profit) }}/j)</span>
                                <span class="text-[9px] text-slate-500 font-bold">|</span>
                            </div>
                        </div>
                    </div>

                    <!-- marketplace (SANS PARTICULES) -->
                    <div data-animate="fade-up" data-delay="500" class="w-full">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-gray-100 font-bold text-sm">marché des serveurs & coffres</h3>
                            <FilterIcon class="w-4 h-4 text-cyan-500 animate-pulse" />
                        </div>

                        <!-- filtres (Grille 2x2 avec couleurs distinctes premium) -->
                        <div class="grid grid-cols-2 gap-3 mb-6">
                            <button @click="activeCategory = 'node'" class="flex flex-col items-center justify-center p-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all border duration-300 h-14 shadow-md" :class="activeCategory === 'node' ? 'bg-purple-600 text-white border-purple-500 shadow-[0_0_10px_rgba(168,85,247,0.4)]' : 'bg-[#0f071d]/60 text-gray-400 border-white/5 hover:bg-white/10 hover:border-white/10'">
                                <span>Node</span>
                            </button>
                            <button @click="activeCategory = 'avip'" class="flex flex-col items-center justify-center p-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all border duration-300 h-14 shadow-md" :class="activeCategory === 'avip' ? 'bg-cyan-600 text-white border-cyan-500 shadow-[0_0_10px_rgba(6,182,212,0.4)]' : 'bg-[#0f071d]/60 text-gray-400 border-white/5 hover:bg-white/10 hover:border-white/10'">
                                <span>AVIP</span>
                            </button>
                            <button @click="activeCategory = 'limited'" class="flex flex-col items-center justify-center p-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all border duration-300 h-14 shadow-md" :class="activeCategory === 'limited' ? 'bg-yellow-600 text-black border-yellow-500 shadow-[0_0_10px_rgba(234,179,8,0.4)]' : 'bg-[#0f071d]/60 text-gray-400 border-white/5 hover:bg-white/10 hover:border-white/10'">
                                <span>Limité</span>
                            </button>
                            <button @click="activeCategory = 'vault'" class="flex flex-col items-center justify-center p-4 rounded-xl text-xs font-black uppercase tracking-wider transition-all border duration-300 h-14 shadow-md" :class="activeCategory === 'vault' ? 'bg-emerald-600 text-white border-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]' : 'bg-[#0f071d]/60 text-gray-400 border-white/5 hover:bg-white/10 hover:border-white/10'">
                                <span>Vault</span>
                            </button>
                        </div>

                        <!-- cartes produit -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div v-for="(node, idx) in combinedProducts" :key="node.id + '-' + node.isVault + '-' + (node.isAvip ?? false)" @click="viewProductDetails(node)" class="group relative bg-[#0a0416] border border-[#00f3ff]/15 rounded-3xl overflow-hidden hover:border-[#00f3ff]/40 transition-all duration-300 cursor-pointer shadow-lg hover:shadow-cyan-500/10 flex flex-col justify-between animate-fadeInUp" :style="{ animationDelay: (idx * 0.05) + 's' }">
                                <!-- Top Header Info -->
                                <div class="p-3 bg-[#0e071d] flex items-center justify-between border-b border-white/5">
                                    <span class="text-[15px] font-black text-white uppercase tracking-wider truncate max-w-[70%]">
                                        {{ node.isAvip ? t('Unité d\'Accélération AVIP', 'AVIP Acceleration Unit') : (node.isVault ? t('Vault d\'Épargne ARM', 'ARM Savings Vault') : t('Location Carte Unique ' + node.name, 'Rental Single Card ' + node.name)) }}
                                    </span>
                                    <!-- Duration Badge -->
                                    <span class="text-[12px] font-black bg-cyan-500 text-black px-3.5 py-1.5 rounded-xl uppercase tracking-wider shadow-md">
                                        {{ node.isAvip ? t('Illimité', 'Unlimited') : (node.isVault ? node.duration + ' ' + t('Jours', 'Days') : node.duration + ' ' + t('Jours', 'Days')) }}
                                    </span>
                                </div>

                                <!-- GPU Image Area -->
                                <div class="w-full h-44 overflow-hidden relative bg-black/20">
                                    <!-- Floating Limited Offer Badge -->
                                    <div v-if="node.is_limited" class="absolute top-3 left-3 z-10 px-2.5 py-1 bg-rose-600/90 backdrop-blur-sm border border-rose-400/30 text-white text-[8px] font-mono font-black uppercase tracking-widest rounded-lg flex items-center gap-1 shadow-lg animate-pulse">
                                        <AlertTriangle class="w-3.5 h-3.5 text-yellow-300" />
                                        <span>Offre Limitée</span>
                                    </div>
                                    <!-- Floating Active Referrals Constraint Badge -->
                                    <div v-if="node.required_active_referrals > 0" class="absolute top-3 right-3 z-10 px-2.5 py-1 bg-yellow-500/95 backdrop-blur-sm border border-yellow-400/30 text-black text-[8px] font-mono font-black uppercase tracking-wider rounded-lg flex items-center gap-1 shadow-lg">
                                        <Users class="w-3.5 h-3.5 text-black" />
                                        <span>Filleuls: {{ node.required_active_referrals }}</span>
                                    </div>
                                    <!-- Show server image or custom vault graphics image -->
                                    <img
                                        :src="node.isVault ? 'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=400&auto=format' : getProductImage(node)"
                                        @error="(e: Event) => onImgError(e, FALLBACK_IMG)"
                                        class="w-full h-full object-cover opacity-85 group-hover:opacity-100 transition-all duration-500 group-hover:scale-105"
                                        :alt="node.name"
                                    >
                                </div>

                                <!-- Brand Purple/Cyan Banner (Premium ARM design) -->
                                <div class="bg-gradient-to-r from-cyan-600 via-purple-600 to-indigo-800 px-4 py-2.5 flex items-center gap-3 justify-between text-white">
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <img src="/images/logo.jpg" class="h-4.5 w-4.5 rounded object-cover border border-white/20 shrink-0 shadow-sm" alt="Logo" />
                                        <div class="bg-black/40 text-cyan-200 font-extrabold text-[7px] px-1 py-0.5 rounded border border-cyan-400/20 leading-none shrink-0 tracking-tighter font-mono" v-if="!node.isVault && !node.isAvip">AI CPU</div>
                                        <div class="bg-black/40 text-purple-200 font-extrabold text-[7px] px-1 py-0.5 rounded border border-purple-400/20 leading-none shrink-0 tracking-tighter font-mono" v-else-if="node.isAvip">AVIP CORE</div>
                                        <div class="bg-black/40 text-emerald-300 font-extrabold text-[7px] px-1 py-0.5 rounded border border-emerald-400/20 leading-none shrink-0 tracking-tighter font-mono" v-else>STAKING</div>
                                    </div>
                                    <div class="text-xs font-black text-white uppercase tracking-wider overflow-hidden flex-1 font-mono text-right max-w-[150px] shrink-0">
                                        <span class="animate-marquee-text inline-block leading-none">
                                            {{ node.isVault ? node.name : node.name.replace(/Location\s+/gi, '').replace(/Carte\s+Unique\s+/gi, '').toUpperCase() }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Stats Grid 2x2 with borders -->
                                <div class="grid grid-cols-2 bg-[#0e071d]/50">
                                    <!-- Stock / Req VIP -->
                                    <div class="p-3 border-r border-b border-white/5 flex items-center justify-between gap-1 text-[11.5px] uppercase tracking-wider">
                                        <span class="text-slate-400 font-bold text-[11px]">{{ node.isAvip ? (node.stock_quantity !== null ? t('Qté en stock:', 'Stock Qty:') : t('Requis:', 'Required:')) : (node.isVault ? t('Garantie:', 'Guaranteed:') : t('Qté en stock:', 'Stock Qty:')) }}</span>
                                        <span class="text-cyan-400 font-black font-mono text-[11px]">{{ node.isAvip ? (node.stock_quantity !== null ? node.stock_quantity : 'VIP ' + node.required_vip_level) : (node.isVault ? '100% SEC' : (node.stock_quantity ?? 'Illimité')) }}</span>
                                    </div>
                                    <!-- Purchase Limit / AVIP Rank -->
                                    <div class="p-3 border-b border-white/5 flex items-center justify-between gap-1 text-[11.5px] uppercase tracking-wider">
                                        <span class="text-slate-400 font-bold text-[11px]">{{ node.isAvip ? (node.stock_quantity !== null ? t("Lim. d'achat:", 'Limit:') : t('Rang AVIP:', 'AVIP Rank:')) : (node.isVault ? t('Contrat:', 'Contract:') : t("Lim. d'achat:", 'Limit:')) }}</span>
                                        <span class="text-cyan-400 font-black font-mono text-[11px]">{{ node.isAvip ? (node.stock_quantity !== null ? (node.limited_purchase_count ?? 'Illimité') : 'AVIP ' + node.avip_level) : (node.isVault ? 'LOCK TERM' : (node.limited_purchase_count ?? 'Illimité')) }}</span>
                                    </div>
                                    <!-- Total Revenue / Daily Yield -->
                                    <div class="p-3 border-r border-white/5 flex flex-col gap-1">
                                        <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">{{ node.isAvip ? t('Salaire journalier', 'Daily salary') : (node.isVault ? t('Retour final', 'Final return') : t('Revenu total', 'Total revenue')) }}</span>
                                        <span class="text-[14.5px] font-black text-emerald-400 font-mono tracking-tight">{{ node.isAvip ? '+' + formatXAF(node.generation_profit) + '/j' : (node.isVault ? formatXAF(node.fixed_return) : formatXAF(node.generation_profit * node.duration)) }}</span>
                                    </div>
                                    <!-- Rental Price / License Fee -->
                                    <div class="p-3 flex flex-col gap-1">
                                        <span class="text-[8px] text-slate-500 font-bold uppercase tracking-widest">{{ node.isAvip ? t('Frais Licence', 'License Fee') : (node.isVault ? t('Dépôt requis', 'Required deposit') : t('Montant location', 'Rental fee')) }}</span>
                                        <span class="text-[14.5px] font-black text-yellow-400 font-mono tracking-tight">{{ formatXAF(node.amount) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (Sidebar) - occupies 1 column on desktop -->
                <div class="lg:col-span-1 space-y-6 flex flex-col justify-start">

                    <!-- En direct (Live withdrawals feed) -->
                    <div data-animate="fade-up" data-delay="200" class="w-full">

                        <!-- header with scintillating cyan icon -->
                        <div class="flex items-center gap-3 mb-4">
                            <div class="relative flex items-center justify-center">
                                <span class="absolute inline-flex h-6 w-6 rounded-full bg-cyan-500/30 animate-ping"></span>
                                <div class="relative w-8 h-8 rounded-full bg-cyan-950/40 border border-cyan-400/40 flex items-center justify-center text-cyan-400 shadow-[0_0_15px_rgba(6,182,212,0.7)] animate-pulse">
                                    <Radio class="w-4.5 h-4.5" :stroke-width="2.5" />
                                </div>
                            </div>
                            <h3 class="text-gray-100 font-black text-sm uppercase tracking-wider">En direct</h3>
                        </div>

                        <!-- Live withdrawals container (Taller sidebar format) -->
                        <div class="bg-[#0f071d]/80 border border-cyan-500/20 rounded-2xl p-4 h-[440px] overflow-hidden relative backdrop-blur-sm glow-border shadow-[0_0_20px_rgba(6,182,212,0.05)]">
                            <div class="absolute top-0 left-0 right-0 h-8 bg-gradient-to-b from-[#0f071d] to-transparent z-10"></div>
                            <div class="absolute bottom-0 left-0 right-0 h-8 bg-gradient-to-t from-[#0f071d] to-transparent z-10"></div>
                            <div class="space-y-2.5 h-full overflow-y-auto scroll-smooth no-scrollbar">
                                <div v-for="(wth, index) in withdrawals" :key="index" class="flex justify-between items-center bg-[#0a0416] border border-cyan-500/5 p-3 rounded-lg shrink-0 transition-all duration-300 hover:translate-x-1 hover:border-cyan-500/30">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-cyan-500/10 border border-cyan-500/20 flex items-center justify-center text-cyan-400">
                                            <CreditCard class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div class="text-[10px] font-medium text-gray-200 font-mono">{{ wth.phone }}</div>
                                            <div class="text-[9px] text-gray-500">{{ wth.time }}</div>
                                        </div>
                                    </div>
                                    <div class="text-emerald-400 font-bold text-xs font-mono">+{{ formatXAF(wth.amount) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
            </div>
        </div>

        <!-- Modale Console de Pointage (Daily Sync Core) -->
        <Teleport to="body">
            <div v-if="showCheckinConsoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0e071d] border border-purple-500/25 rounded-2xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="flex justify-between items-center mb-6">
                            <span class="text-xs font-black text-purple-400 uppercase tracking-widest font-mono">Console pointage</span>
                            <button @click="showCheckinConsoleModal = false" class="hover:rotate-90 transition-transform"><XIcon class="w-5 h-5 text-gray-400" /></button>
                        </div>

                        <!-- Holographic CPU spinning core -->
                        <div class="relative w-32 h-32 mx-auto my-6 flex items-center justify-center">
                            <div class="absolute inset-0 rounded-full border border-purple-500/20 border-dashed animate-spin" style="animation-duration: 15s;"></div>
                            <div class="absolute w-24 h-24 rounded-full border-2 border-purple-500/30 border-dotted animate-spin" style="animation-duration: 8s; animation-direction: reverse;"></div>
                            <div class="absolute w-16 h-16 rounded-full bg-purple-500/10 border border-purple-500/40 flex items-center justify-center text-purple-400 shadow-[0_0_20px_rgba(168,85,247,0.3)] animate-pulse">
                                <BrainCircuit class="w-8 h-8" :stroke-width="2.5" />
                            </div>
                        </div>

                        <h3 class="text-base font-black text-white uppercase tracking-wider">Synchronisation Quotidienne</h3>
                        <p class="text-[10px] text-slate-400 mt-2 leading-relaxed">
                            Connectez votre console de calcul au réseau de semi-conducteurs ARM pour valider votre activité matérielle du jour.
                        </p>

                        <!-- Bonus detail -->
                        <div class="my-5 p-4 rounded-xl bg-black/40 border border-white/5">
                            <span class="text-[9px] text-slate-500 uppercase tracking-widest block font-bold">Crédit Énergétique</span>
                            <span class="text-xl font-mono font-black text-purple-400 mt-1 block">+77 FCFA</span>
                            <span class="text-[8px] text-slate-500 font-mono block mt-1">[ Prêt pour la synchronisation ]</span>
                        </div>

                        <!-- Sync Trigger button -->
                        <button
                            @click="handleCheckinTrigger"
                            :disabled="checkinProcessing"
                            class="w-full py-3.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold uppercase tracking-widest text-xs transition-all shadow-[0_0_15px_rgba(168,85,247,0.4)] flex items-center justify-center gap-2"
                        >
                            <CheckCircle class="w-4 h-4 shrink-0" :class="checkinProcessing ? 'animate-spin' : ''" :stroke-width="2.5" />
                            {{ checkinProcessing ? 'SYNCHRONISATION...' : 'SYNCHRONISER LA CONSOLE' }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- modale d'invitation -->
        <Teleport to="body">
            <div v-if="showInviteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0f0f1a] border border-cyan-500/30 rounded-2xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-gray-100 flex items-center gap-2">
                                <Share2 class="w-5 h-5 text-cyan-400" />
                                Partage Réseau AI
                            </h3>
                            <button @click="showInviteModal = false" class="hover:rotate-90 transition-transform"><XIcon class="w-5 h-5 text-gray-400" /></button>
                        </div>

                        <p class="text-xs text-gray-400 mb-6 font-mono leading-relaxed">
                            Développez votre maillage d'infrastructure en parrainant de nouveaux nœuds de calcul semi-conducteurs.
                        </p>

                        <!-- Code Parrainage Box -->
                        <div class="bg-black/30 p-4 rounded-xl border border-white/5 mb-4 relative overflow-hidden">
                            <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Mon Code de Parrainage</div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-lg font-mono font-black text-cyan-400 tracking-wider">{{ user?.referral_code || 'SYS-ERR' }}</span>
                                <button @click="copyReferralCode" class="px-3 py-1.5 bg-cyan-950 text-cyan-400 border border-cyan-800 rounded-lg text-[10px] font-bold font-mono hover:bg-cyan-500 hover:text-black transition-all">
                                    {{ inviteCopied ? 'Copié !' : 'Copier' }}
                                </button>
                            </div>
                        </div>

                        <!-- Lien Parrainage Box -->
                        <div class="bg-black/30 p-4 rounded-xl border border-white/5 mb-6 relative overflow-hidden">
                            <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Mon Lien de Parrainage</div>
                            <div class="flex justify-between items-center mt-2 gap-2">
                                <span class="text-[9px] font-mono text-gray-300 truncate max-w-[200px]">{{ referralLink }}</span>
                                <button @click="copyReferralLink" class="px-3 py-1.5 bg-cyan-950 text-cyan-400 border border-cyan-800 rounded-lg text-[10px] font-bold font-mono hover:bg-cyan-500 hover:text-black transition-all shrink-0">
                                    {{ inviteCopied ? 'Copié !' : 'Copier Link' }}
                                </button>
                            </div>
                        </div>

                        <button @click="showInviteModal = false" class="w-full py-3 rounded-xl bg-cyan-500 text-white font-bold hover:bg-cyan-400 transition-all shadow-[0_0_15px_rgba(6,182,212,0.4)]">
                            Fermer le Panel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- modale de succès pointage -->
        <Teleport to="body">
            <div v-if="showCheckinSuccessModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0f0f1a] border border-emerald-500/30 rounded-2xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="absolute right-[-10%] top-[-10%] w-32 h-32 bg-emerald-500/10 rounded-full blur-3xl"></div>
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 mb-6 mx-auto animate-bounce shadow-[0_0_20px_rgba(16,185,129,0.2)]">
                            <CheckCircle class="h-8 w-8" />
                        </div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider">Synchronisation Complétée</h3>
                        <p class="text-xs text-gray-400 mt-2 font-mono leading-relaxed">
                            Le nœud principal de votre console AI s'est synchronisé avec succès sur la grille globale ARM.
                        </p>

                        <div class="my-6 p-4 rounded-xl bg-black/40 border border-emerald-500/15">
                            <div class="text-[10px] text-gray-500 uppercase tracking-widest font-bold">Énergie Captée</div>
                            <div class="text-2xl font-mono font-black text-emerald-400 mt-1">+77 FCFA</div>
                            <div class="text-[9px] text-gray-500 font-mono mt-1">[ Injecté dans le portefeuille ]</div>
                        </div>

                        <button @click="showCheckinSuccessModal = false" class="w-full py-3 rounded-xl bg-emerald-500 text-black font-extrabold uppercase tracking-wider text-xs hover:bg-emerald-400 transition-all shadow-[0_0_15px_rgba(16,185,129,0.4)]">
                            Consolider le Système
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- modale d'erreur pointage -->
        <Teleport to="body">
            <div v-if="showCheckinErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0f0f1a] border border-rose-500/30 rounded-2xl overflow-hidden shadow-2xl animate-fadeInUp glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 mx-auto animate-pulse">
                            <XIcon class="h-8 w-8" />
                        </div>
                        <h3 class="text-lg font-black text-white uppercase tracking-wider">Erreur de Protocole</h3>
                        <p class="text-xs text-rose-400 mt-4 font-mono leading-relaxed bg-rose-950/20 border border-rose-500/20 p-3.5 rounded-xl">
                            {{ checkinError }}
                        </p>

                        <button @click="showCheckinErrorModal = false" class="w-full mt-6 py-3 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold uppercase tracking-wider text-xs hover:bg-rose-500 hover:text-black transition-all">
                            Fermer le Log
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- modale d'erreur dashboard globale -->
        <Teleport to="body">
            <div v-if="showDashboardErrorModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-md overflow-hidden" @touchmove.prevent>
                <div class="w-full max-w-sm bg-[#0a0514] border border-rose-500/30 rounded-2xl overflow-hidden shadow-2xl glow-border">
                    <div class="p-6 text-center relative z-10">
                        <div class="h-16 w-16 rounded-full bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 mb-6 mx-auto animate-pulse">
                            <XIcon class="h-8 w-8" />
                        </div>
                        <h3 class="text-base font-black text-white uppercase tracking-wider font-mono">Alerte Système</h3>
                        <p class="text-xs text-rose-400 mt-4 font-mono leading-relaxed bg-rose-950/20 border border-rose-500/20 p-3.5 rounded-xl">
                            {{ dashboardErrorMessage }}
                        </p>

                        <button @click="showDashboardErrorModal = false" class="w-full mt-6 py-3 rounded-xl bg-rose-500/20 text-rose-300 border border-rose-500/30 font-bold uppercase tracking-wider text-xs hover:bg-rose-500 hover:text-black transition-all">
                            Fermer la console
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </AppLayout>
</template>

<style scoped>
@keyframes float { 0%,100% { transform: translateY(0) translateX(0); opacity: 0.3; } 50% { transform: translateY(-20px) translateX(10px); opacity: 0.8; } }
@keyframes scan { 0% { top: 0%; opacity: 0; } 10% { opacity: 1; } 90% { opacity: 1; } 100% { top: 100%; opacity: 0; } }
@keyframes marquee { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
@keyframes marquee-text-fast { 0% { transform: translateX(0%); } 100% { transform: translateX(-50%); } }
@keyframes borderGlow { 0% { box-shadow: 0 0 2px rgba(168,85,247,0.2),0 0 4px rgba(168,85,247,0.1); border-color: rgba(168,85,247,0.3); } 50% { box-shadow: 0 0 8px rgba(168,85,247,0.6),0 0 12px rgba(168,85,247,0.3); border-color: rgba(168,85,247,0.8); } 100% { box-shadow: 0 0 2px rgba(168,85,247,0.2),0 0 4px rgba(168,85,247,0.1); border-color: rgba(168,85,247,0.3); } }
.glow-border { animation: borderGlow 3s ease-in-out infinite; border-style: solid; border-width: 1px; }
.animate-float { animation: float 6s ease-in-out infinite; }
.animate-scan { animation: scan 3s linear infinite; }
.animate-marquee { animation: marquee 20s linear infinite; }
.animate-fadeInUp { animation: fadeInUp 0.5s ease-out forwards; }
.animate-marquee-text-fast { animation: marquee-text-fast 4s linear infinite; display: inline-block; padding-right: 2rem; }
@keyframes marquee-text {
    0% { transform: translateX(0); }
    50% { transform: translateX(-25%); }
    100% { transform: translateX(0); }
}
.animate-marquee-text {
    display: inline-block;
    white-space: nowrap;
    animation: marquee-text 7s linear infinite;
}
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
.group:hover { box-shadow: 0 0 20px rgba(168,85,247,0.2); border-color: rgba(168,85,247,0.4); }
</style>
