<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    FileText, 
    ShieldCheck, 
    Download, 
    Award, 
    Eye,
    X
} from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Présentation', href: '/presentation' },
];

const showLightbox = ref(false);
const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Présentation Officielle" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div ref="containerRef" class="relative w-full max-w-2xl mx-auto flex flex-col gap-6 pt-3 pb-24 px-4">
            
            <!-- Header Banner -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <FileText class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">Présentation de l'Entreprise</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">ARM Holding & Certification Légale</p>
                    </div>
                </div>
            </div>

            <!-- Enterprise Introduction Card -->
            <div data-animate="fade-up" data-delay="100" class="bg-gradient-to-b from-[#0a0416]/90 to-[#05020c]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative overflow-hidden">
                <div class="absolute -top-12 -right-12 w-28 h-28 bg-purple-500/10 rounded-full blur-2xl pointer-events-none"></div>
                
                <h3 class="text-xs font-black text-white uppercase tracking-wider mb-3 flex items-center gap-2 border-b border-purple-500/10 pb-3">
                    <Award class="h-4.5 w-4.5 text-purple-400" />
                    À propos d'ARM Holding
                </h3>

                <p class="text-[11px] text-slate-300 leading-relaxed font-sans mb-4">
                    <strong>ARM Holding</strong> est un leader mondial de l'innovation technologique décentralisée, spécialisé dans la mise à disposition d'infrastructures de calcul de haute performance et de serveurs d'intelligence artificielle. Notre mission est de connecter la puissance des puces de dernière génération à un écosystème d'investisseurs et d'utilisateurs à travers le monde.
                </p>
                
                <div class="grid grid-cols-2 gap-3 mt-4">
                    <div class="bg-black/30 border border-purple-500/5 p-3 rounded-2xl text-center shadow-md">
                        <span class="text-[8px] text-slate-500 uppercase tracking-widest block font-bold">Siège Social</span>
                        <span class="text-[10px] font-black text-white mt-1 block">Londres, Royaume-Uni</span>
                    </div>
                    <div class="bg-black/30 border border-purple-500/5 p-3 rounded-2xl text-center shadow-md">
                        <span class="text-[8px] text-slate-500 uppercase tracking-widest block font-bold">Secteur Principal</span>
                        <span class="text-[10px] font-black text-white mt-1 block">Infrastructure IA & Cloud Computing</span>
                    </div>
                </div>
            </div>

            <!-- Certification & Legal Document Section -->
            <div data-animate="fade-up" data-delay="200" class="bg-gradient-to-b from-[#0a0416]/90 to-[#05020c]/90 border border-purple-500/15 rounded-3xl p-5 shadow-2xl backdrop-blur-sm relative">
                
                <h3 class="text-xs font-black text-white uppercase tracking-wider mb-4 flex items-center gap-2 border-b border-purple-500/10 pb-3">
                    <ShieldCheck class="h-4.5 w-4.5 text-emerald-400" />
                    Autorisation Légale d'Exercer au Cameroun
                </h3>

                <p class="text-[11px] text-slate-300 leading-relaxed font-sans mb-5">
                    Afin d'assurer une transparence totale et de garantir le respect des normes juridiques locales, <strong>ARM Holding</strong> est dument certifié et autorisé à exercer ses activités de location de serveurs et de co-traitement informatique sur l'ensemble du territoire de la <strong>République du Cameroun</strong>. 
                </p>

                <!-- Certification details checklist -->
                <div class="space-y-2 bg-black/40 border border-white/5 rounded-2xl p-4.5 mb-5 text-[10.5px]">
                    <div class="flex items-center gap-2 text-slate-300">
                        <ShieldCheck class="h-4 w-4 text-emerald-400 shrink-0" />
                        <span>Contrat d'autorisation d'exercice officiel en République du Cameroun.</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-300">
                        <ShieldCheck class="h-4 w-4 text-emerald-400 shrink-0" />
                        <span>Comporte le sceau officiel et la signature d'enregistrement du Royaume-Uni.</span>
                    </div>
                    <div class="flex items-center gap-2 text-slate-300">
                        <ShieldCheck class="h-4 w-4 text-emerald-400 shrink-0" />
                        <span>Conforme aux régulations des technologies de l'information et de la communication.</span>
                    </div>
                </div>

                <!-- Glow Box Container for the Contract Image -->
                <div class="relative group rounded-2xl overflow-hidden border border-purple-500/30 bg-black/50 shadow-[0_0_25px_rgba(168,85,247,0.15)] hover:border-purple-400/50 transition-all duration-500 cursor-pointer" @click="showLightbox = true">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/10 to-transparent z-10"></div>
                    
                    <!-- Hover Visual Aids -->
                    <div class="absolute inset-0 flex items-center justify-center z-20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 bg-black/40">
                        <div class="px-4 py-2.5 rounded-xl bg-purple-600/90 text-white font-extrabold uppercase tracking-widest text-[9px] flex items-center gap-2 shadow-lg">
                            <Eye class="h-3.5 w-3.5" />
                            Agrandir en 4K
                        </div>
                    </div>

                    <img 
                        src="/images/contract.png" 
                        alt="ARM Holding Official UK-Cameroon Certificate" 
                        class="w-full h-auto object-cover opacity-90 group-hover:scale-[1.02] transition-transform duration-700"
                    />

                    <!-- Badge at bottom of contract inside layout -->
                    <div class="absolute bottom-4 left-4 right-4 z-10 flex justify-between items-center">
                        <span class="text-[9px] font-black uppercase bg-purple-500/20 border border-purple-500/30 px-3 py-1 rounded-full text-purple-300 tracking-wider">
                            Document Certifié Conforme
                        </span>
                        <a 
                            href="/images/contract.png" 
                            download="ARM_Holding_Certificate_Cameroon.png"
                            @click.stop
                            class="p-2 rounded-xl bg-black/60 border border-white/10 text-white/80 hover:bg-black hover:text-white transition-all shadow"
                            title="Télécharger le contrat"
                        >
                            <Download class="h-3.5 w-3.5" />
                        </a>
                    </div>
                </div>
            </div>

        </div>

        <!-- FULL-SCREEN LIGHTBOX MODAL -->
        <div 
            v-if="showLightbox" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/95 backdrop-blur-sm animate-fadeIn"
            @click="showLightbox = false"
        >
            <div class="relative max-w-4xl w-full max-h-[90vh] flex items-center justify-center" @click.stop>
                <!-- Close Button -->
                <button 
                    @click="showLightbox = false"
                    class="absolute -top-12 right-0 w-10 h-10 rounded-full bg-black/60 border border-white/10 flex items-center justify-center text-white hover:bg-black transition-all z-20"
                >
                    <X class="h-5 w-5" />
                </button>

                <img 
                    src="/images/contract.png" 
                    alt="ARM Holding UK-Cameroon Contract 4K" 
                    class="max-w-full max-h-[80vh] object-contain rounded-xl border border-white/10 shadow-2xl"
                />

                <div class="absolute -bottom-10 left-0 right-0 text-center">
                    <span class="text-[10px] text-slate-400 font-mono tracking-widest uppercase">
                        Tampon & Signature Officielle du Royaume-Uni (UK Companies House) • Autorisé pour le Cameroun
                    </span>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.animate-fadeIn {
    animation: fadeIn 0.3s ease-out forwards;
}
</style>
