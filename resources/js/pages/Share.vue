<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Copy, 
    Check, 
    Share2, 
    QrCode, 
    ArrowUpRight,
    Sparkles,
    UserPlus,
    Users
} from 'lucide-vue-next';

// --- PROPS ---
const props = defineProps<{
    stats: {
        referral_code: string;
        referral_link: string;
        referred_count: number;
        commissions_total: number;
        referrals_deposits_total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Partager', href: '/share' },
];

const copiedCode = ref(false);
const copiedLink = ref(false);

const copyToClipboard = async (text: string, type: 'code' | 'link') => {
    try {
        await navigator.clipboard.writeText(text);
        if (type === 'code') {
            copiedCode.value = true;
            setTimeout(() => copiedCode.value = false, 2000);
        } else {
            copiedLink.value = true;
            setTimeout(() => copiedLink.value = false, 2000);
        }
    } catch (err) {
        console.error('Erreur de copie', err);
    }
};

// QR Code API Generator URL
const qrUrl = computed(() => {
    return `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(props.stats.referral_link)}`;
});

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Partager" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24">
            
            <!-- HEADER: Partage Réseau Banner -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-cyan-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-cyan-500/10 shadow-lg mx-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-cyan-500/20 bg-cyan-950/20 flex items-center justify-center text-cyan-400">
                        <Share2 class="h-5 w-5" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">Partage Réseau</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Invitez des membres sur votre réseau AI</p>
                    </div>
                </div>
            </div>

            <!-- STATS CARDS: referral metrics -->
            <div data-animate="fade-up" data-delay="50" class="grid grid-cols-3 gap-2.5 mx-4">
                <!-- Filleuls Directs -->
                <div class="bg-[#05020c]/80 border border-cyan-500/20 rounded-2xl p-3 flex flex-col items-center text-center shadow-[0_0_15px_rgba(6,182,212,0.05)] hover:border-cyan-400/50 hover:shadow-[0_0_20px_rgba(6,182,212,0.15)] transition-all duration-300">
                    <div class="w-8 h-8 rounded-xl border border-cyan-500/20 bg-cyan-950/20 flex items-center justify-center text-cyan-400 mb-1.5 shrink-0">
                        <Users class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </div>
                    <span class="text-xs font-mono font-black text-cyan-400 leading-none">{{ stats.referred_count }}</span>
                    <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider mt-1.5 leading-none">Filleuls</span>
                </div>

                <!-- Commissions perçues -->
                <div class="bg-[#05020c]/80 border border-emerald-500/20 rounded-2xl p-3 flex flex-col items-center text-center shadow-[0_0_15px_rgba(16,185,129,0.05)] hover:border-emerald-400/50 hover:shadow-[0_0_20px_rgba(16,185,129,0.15)] transition-all duration-300">
                    <div class="w-8 h-8 rounded-xl border border-emerald-500/20 bg-emerald-950/20 flex items-center justify-center text-emerald-400 mb-1.5 shrink-0">
                        <Sparkles class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </div>
                    <span class="text-xs font-mono font-black text-emerald-400 leading-none truncate max-w-full">{{ new Intl.NumberFormat('fr-FR').format(stats.commissions_total) }}</span>
                    <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider mt-1.5 leading-none">Revenus (XAF)</span>
                </div>

                <!-- Volume Rechargé -->
                <div class="bg-[#05020c]/80 border border-purple-500/20 rounded-2xl p-3 flex flex-col items-center text-center shadow-[0_0_15px_rgba(168,85,247,0.05)] hover:border-purple-400/50 hover:shadow-[0_0_20px_rgba(168,85,247,0.15)] transition-all duration-300">
                    <div class="w-8 h-8 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400 mb-1.5 shrink-0">
                        <ArrowUpRight class="h-4.5 w-4.5" :stroke-width="2.5" />
                    </div>
                    <span class="text-xs font-mono font-black text-purple-400 leading-none truncate max-w-full">{{ new Intl.NumberFormat('fr-FR').format(stats.referrals_deposits_total) }}</span>
                    <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider mt-1.5 leading-none">Recharges (XAF)</span>
                </div>
            </div>

            <!-- MAIN CONTAINER: QR Code & Invitation Credentials -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#040916]/90 to-[#02050c]/90 border border-cyan-500/15 rounded-3xl p-6 shadow-2xl backdrop-blur-sm relative mx-4">
                
                <div class="flex flex-col items-center mb-6">
                    <span class="text-[10px] text-slate-400 font-black uppercase tracking-widest block text-center mb-4">Votre QR Code de Parrainage</span>
                    
                    <!-- Futuristic premium QR Frame -->
                    <div class="p-3.5 rounded-2xl bg-white border border-cyan-500/20 shadow-[0_0_25px_rgba(6,182,212,0.15)] flex items-center justify-center relative group">
                        <img :src="qrUrl" alt="Referral QR Code" class="w-44 h-44 rounded-lg pointer-events-none select-none" />
                        <div class="absolute inset-0 border-2 border-cyan-500/20 rounded-2xl pointer-events-none"></div>
                    </div>
                </div>

                <div class="space-y-4">
                    
                    <!-- Code de parrainage box -->
                    <div class="bg-black/40 border border-cyan-500/10 rounded-2xl p-4 flex flex-col gap-1.5 relative overflow-hidden shadow">
                        <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Mon Code de Parrainage</span>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-lg font-mono font-black text-cyan-400 tracking-wider">{{ stats.referral_code }}</span>
                            <button 
                                @click="copyToClipboard(stats.referral_code, 'code')"
                                class="px-4 py-2 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 font-extrabold text-[10px] uppercase hover:bg-cyan-500 hover:text-black transition-all duration-300"
                            >
                                <span v-if="copiedCode" class="flex items-center gap-1"><Check class="h-3 w-3" /> Copié</span>
                                <span v-else class="flex items-center gap-1"><Copy class="h-3 w-3" /> Copier</span>
                            </button>
                        </div>
                    </div>

                    <!-- Lien de parrainage box -->
                    <div class="bg-black/40 border border-cyan-500/10 rounded-2xl p-4 flex flex-col gap-1.5 relative overflow-hidden shadow">
                        <span class="text-[9px] text-slate-400 font-black uppercase tracking-widest">Mon Lien d'Invitation</span>
                        <div class="flex justify-between items-center mt-1 gap-2">
                            <span class="text-[9px] font-mono text-slate-300 truncate max-w-[200px] select-all">{{ stats.referral_link }}</span>
                            <button 
                                @click="copyToClipboard(stats.referral_link, 'link')"
                                class="px-4 py-2 rounded-xl bg-cyan-500/10 border border-cyan-500/30 text-cyan-400 font-extrabold text-[10px] uppercase hover:bg-cyan-500 hover:text-black transition-all duration-300 shrink-0"
                            >
                                <span v-if="copiedLink" class="flex items-center gap-1"><Check class="h-3 w-3" /> Copié</span>
                                <span v-else class="flex items-center gap-1"><Copy class="h-3 w-3" /> Copier</span>
                            </button>
                        </div>
                    </div>

                </div>

            </div>

            <!-- NETWORK SHARING BENEFIT -->
            <div data-animate="scale-up" data-delay="150" class="p-4 rounded-2xl border border-cyan-500/10 bg-cyan-950/5 relative overflow-hidden shadow flex items-start gap-3 mx-4">
                <Sparkles class="h-5 w-5 text-cyan-400 shrink-0 mt-0.5" :stroke-width="2.5" />
                <div>
                    <h3 class="text-xs font-black text-white uppercase tracking-wider">Avantage Parrainage</h3>
                    <p class="text-[10px] text-slate-400 leading-relaxed mt-1">
                        Invitez vos contacts à déployer leurs nœuds de calcul sur ARM. Recevez instantanément une commission de 10% sur leurs activations directes.
                    </p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
