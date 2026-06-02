<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { isNative } from '@/plugins/capacitor';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import BottomNav from '@/components/BottomNav.vue';
import { Link, router } from '@inertiajs/vue3';
import { Cpu, Play, Sparkles, X, MessageSquareCode, Send, Image, ArrowLeft, Download, Smartphone } from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const isPageLoading = ref(true);

// ── Bannière de téléchargement de l'application ──
// Affichée une fois par session (stockée dans localStorage)
const showDownloadBanner = ref(false);
const dismissDownloadBanner = () => {
    showDownloadBanner.value = false;
    localStorage.setItem('arm_app_banner_dismissed', '1');
};

// Draggable assistant avatar state
const isDragging = ref(false);
const posX = ref(300);
const posY = ref(600);
const startX = ref(0);
const startY = ref(0);
const showAssistantModal = ref(false);
let dragMoved = false;

// Chat support state
const chatMessages = ref<Array<{ sender: 'user' | 'assistant', text: string, time: string }>>([
    { sender: 'assistant', text: 'Bonjour ! Comment puis-je vous aider aujourd\'hui sur la plateforme ARM Holding ?', time: '06:25' }
]);
const inputMessage = ref('');

const sendChatMessage = () => {
    if (!inputMessage.value.trim()) return;
    const now = new Date();
    const timeStr = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
    
    // Add user message
    chatMessages.value.push({
        sender: 'user',
        text: inputMessage.value,
        time: timeStr
    });
    
    const textSent = inputMessage.value;
    inputMessage.value = '';

    // Auto-scroll logic if chat-container is present
    setTimeout(() => {
        const el = document.getElementById('chat-body-container');
        if (el) el.scrollTop = el.scrollHeight;
    }, 50);

    // Simple reply logic
    setTimeout(() => {
        let reply = "Vos nœuds de calcul fonctionnent à plein régime. N'hésitez pas à réclamer vos gains journaliers dans l'onglet Console.";
        if (textSent.toLowerCase().includes('retrait') || textSent.toLowerCase().includes('withdraw') || textSent.toLowerCase().includes('retirer')) {
            reply = "Pour effectuer un retrait, assurez-vous d'avoir loué au moins un serveur de calcul, configuré un numéro de retrait MTN/Orange valide, et défini un Code PIN dans vos paramètres.";
        } else if (textSent.toLowerCase().includes('recharger') || textSent.toLowerCase().includes('depot') || textSent.toLowerCase().includes('dépôt')) {
            reply = "Vous pouvez alimenter votre portefeuille via MTN MoMo, Orange Money ou Crypto USDT TRC-20 directement depuis l'onglet Recharger de votre profil.";
        } else if (textSent.toLowerCase().includes('bonjour') || textSent.toLowerCase().includes('salut')) {
            reply = "Bonjour ! Je suis l'assistante réseau ARM. Quelle est votre requête concernant l'infrastructure ?";
        }
        
        chatMessages.value.push({
            sender: 'assistant',
            text: reply,
            time: new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
        });

        // Auto-scroll logic after assistant replies
        setTimeout(() => {
            const el = document.getElementById('chat-body-container');
            if (el) el.scrollTop = el.scrollHeight;
        }, 50);
    }, 1200);
};

// Page entrance animation state
const pageLoaded = ref(false);

onMounted(() => {
    // Position at bottom-right, ABOVE the bottom nav (which is ~72px tall)
    posX.value = window.innerWidth - 72;
    posY.value = window.innerHeight - 150;
    
    // Trigger page entrance animation
    requestAnimationFrame(() => {
        setTimeout(() => {
            pageLoaded.value = true;
            isPageLoading.value = false;
        }, 800);
    });

    // Événements du routeur Inertia pour ré-afficher le skeleton à chaque navigation
    router.on('start', () => {
        isPageLoading.value = true;
    });
    router.on('finish', () => {
        setTimeout(() => {
            isPageLoading.value = false;
        }, 700);
    });

    // Affiche la bannière de téléchargement 2 secondes après chargement
    // seulement si l'utilisateur ne l'a pas déjà fermée et n'est pas dans l'app native
    const alreadyDismissed = localStorage.getItem('arm_app_banner_dismissed');
    if (!alreadyDismissed && !isNative()) {
        setTimeout(() => {
            showDownloadBanner.value = true;
        }, 2000);
    }
});

const startDrag = (event: MouseEvent | TouchEvent) => {
    isDragging.value = true;
    dragMoved = false;
    const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX;
    const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;
    startX.value = clientX - posX.value;
    startY.value = clientY - posY.value;
    
    document.addEventListener('mousemove', onDrag);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', onDrag, { passive: false });
    document.addEventListener('touchend', endDrag);
};

const onDrag = (event: MouseEvent | TouchEvent) => {
    if (!isDragging.value) return;
    if (event.cancelable) event.preventDefault();
    const clientX = 'touches' in event ? event.touches[0].clientX : event.clientX;
    const clientY = 'touches' in event ? event.touches[0].clientY : event.clientY;
    
    let newX = clientX - startX.value;
    let newY = clientY - startY.value;
    
    const padding = 10;
    const size = 56; // 14rem/56px avatar size
    const maxW = window.innerWidth - size - padding;
    const maxH = window.innerHeight - size - padding;
    
    newX = Math.max(padding, Math.min(newX, maxW));
    newY = Math.max(padding, Math.min(newY, maxH));
    
    posX.value = newX;
    posY.value = newY;
    dragMoved = true;
};

const endDrag = () => {
    isDragging.value = false;
    document.removeEventListener('mousemove', onDrag);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchmove', onDrag);
    document.removeEventListener('touchend', endDrag);
};

const handleAssistantClick = () => {
    if (!dragMoved) {
        showAssistantModal.value = true;
    }
};
</script>

<template>
    <AppShell variant="header" class="bg-[#05020c] text-white min-h-screen flex flex-col relative overflow-hidden">
        
        <!-- GLOBAL BACKGROUND: Shimmering Cosmic Purple Vertical Lines & Glow Radial Blurs -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden select-none">
            <div class="absolute inset-0 bg-grid opacity-10"></div>
            <div class="vertical-line" style="left: 8%; animation-delay: 0s; animation-duration: 9s;"></div>
            <div class="vertical-line" style="left: 24%; animation-delay: 2.5s; animation-duration: 7s;"></div>
            <div class="vertical-line" style="left: 42%; animation-delay: 4.8s; animation-duration: 10s;"></div>
            <div class="vertical-line" style="left: 58%; animation-delay: 1.2s; animation-duration: 8s;"></div>
            <div class="vertical-line" style="left: 76%; animation-delay: 3.5s; animation-duration: 11s;"></div>
            <div class="vertical-line" style="left: 90%; animation-delay: 5.2s; animation-duration: 8.5s;"></div>
            <!-- Glow points -->
            <div class="absolute top-[10%] left-[20%] w-96 h-96 rounded-full bg-purple-500/8 blur-[120px]"></div>
            <div class="absolute bottom-[20%] right-[10%] w-[450px] h-[450px] rounded-full bg-violet-500/8 blur-[160px]"></div>
            <!-- PURPLE LUMINOUS CIRCLES for brightness -->
            <div class="absolute top-[5%] left-[50%] w-64 h-64 rounded-full bg-purple-400/10 blur-[100px] animate-pulse-slow"></div>
            <div class="absolute top-[35%] right-[15%] w-80 h-80 rounded-full bg-violet-500/8 blur-[130px]"></div>
            <div class="absolute bottom-[10%] left-[5%] w-72 h-72 rounded-full bg-purple-400/9 blur-[110px] animate-pulse-slow"></div>
            <div class="absolute top-[60%] left-[40%] w-56 h-56 rounded-full bg-fuchsia-500/8 blur-[90px]"></div>
            <div class="absolute bottom-[40%] right-[30%] w-48 h-48 rounded-full bg-violet-500/10 blur-[80px] animate-pulse-slow"></div>
        </div>

        <!-- MAIN LAYOUT CONTENT with page entrance animation and Skeleton Loaders -->
        <AppContent variant="header" class="relative z-10 flex-1 overflow-x-hidden pb-24 px-4 py-6 max-w-xl md:max-w-3xl mx-auto w-full">
            <!-- SHIMMERING SKELETON SCREEN loader -->
            <div v-if="isPageLoading" class="space-y-6 animate-pulse select-none">
                <!-- Header Area -->
                <div class="flex justify-between items-center mb-6">
                    <div class="space-y-2">
                        <div class="h-6 w-32 bg-purple-500/10 border border-purple-500/20 rounded-xl skeleton-shimmer"></div>
                        <div class="h-3.5 w-24 bg-purple-500/5 border border-purple-500/10 rounded-lg skeleton-shimmer"></div>
                    </div>
                    <div class="h-9 w-24 bg-purple-500/15 border border-purple-500/20 rounded-xl skeleton-shimmer"></div>
                </div>

                <!-- Big Tech Card / Banner Shimmer -->
                <div class="h-44 w-full bg-purple-500/5 border border-purple-500/15 rounded-3xl skeleton-shimmer relative overflow-hidden">
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-500/10 to-transparent -translate-x-full animate-shimmer"></div>
                </div>

                <!-- 2 Column quick specs Shimmer -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="h-24 bg-purple-500/5 border border-purple-500/10 rounded-2xl skeleton-shimmer relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-500/10 to-transparent -translate-x-full animate-shimmer"></div>
                    </div>
                    <div class="h-24 bg-purple-500/5 border border-purple-500/10 rounded-2xl skeleton-shimmer relative overflow-hidden">
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-purple-500/10 to-transparent -translate-x-full animate-shimmer"></div>
                    </div>
                </div>

                <!-- List/Rows Shimmer -->
                <div class="bg-[#0e071d]/60 border border-purple-500/10 rounded-2xl p-4 space-y-3.5">
                    <div v-for="i in 3" :key="i" class="flex justify-between items-center h-12 bg-black/40 border border-white/5 px-4 rounded-xl skeleton-shimmer relative overflow-hidden">
                        <div class="h-3.5 w-24 bg-purple-500/10 border border-purple-500/20 rounded-lg"></div>
                        <div class="h-3.5 w-16 bg-emerald-500/10 border border-emerald-500/20 rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT SLOT -->
            <div 
                v-else
                class="transition-all duration-500 ease-out"
                :class="pageLoaded ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
            >
                <slot />
            </div>
        </AppContent>
        <BottomNav />

        <!-- ── BANNIÈRE DE TÉLÉCHARGEMENT (apparaît après connexion) ── -->
        <Teleport to="body">
            <Transition name="slide-up">
                <div
                    v-if="showDownloadBanner"
                    class="fixed bottom-20 left-0 right-0 z-[55] px-3 pb-1"
                >
                    <div class="relative max-w-lg mx-auto rounded-2xl overflow-hidden border border-purple-500/30 bg-[#0a0118]/95 backdrop-blur-xl shadow-[0_-4px_30px_rgba(168,85,247,0.2)]"
                    >
                        <!-- Glow top border accent -->
                        <div class="absolute top-0 left-0 right-0 h-[2px] bg-gradient-to-r from-transparent via-purple-500 to-transparent opacity-80"></div>

                        <div class="flex items-center gap-3 p-3.5">
                            <!-- App icon area -->
                            <div class="relative shrink-0">
                                <div class="w-12 h-12 rounded-xl overflow-hidden shadow-[0_0_15px_rgba(168,85,247,0.4)] border border-purple-500/20">
                                    <img src="/images/logo.jpg" alt="ARM Holding Logo" class="w-full h-full object-cover" />
                                </div>
                                <!-- Badge android -->
                                <span class="absolute -top-1 -right-1 w-4 h-4 rounded-full bg-emerald-500 border-2 border-[#0a0118] flex items-center justify-center">
                                    <span class="text-[6px] font-black text-white">▲</span>
                                </span>
                            </div>

                            <!-- Text content -->
                            <div class="flex-1 min-w-0">
                                <p class="text-[11px] font-black text-white uppercase tracking-wider truncate">ARM Holding — App Mobile</p>
                                <p class="text-[9px] text-slate-400 font-mono mt-0.5">Téléchargez l'app pour une expérience optimale</p>
                            </div>

                            <!-- Download button -->
                            <a
                                href="/downloads/arm-holding.apk"
                                download
                                @click="dismissDownloadBanner"
                                class="shrink-0 flex items-center gap-1.5 px-3 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white text-[10px] font-extrabold uppercase tracking-wider hover:from-purple-500 hover:to-fuchsia-500 transition-all shadow-[0_0_12px_rgba(168,85,247,0.3)] active:scale-95"
                            >
                                <Download class="h-3.5 w-3.5" />
                                Installer
                            </a>

                            <!-- Close button -->
                            <button
                                @click="dismissDownloadBanner"
                                class="shrink-0 w-7 h-7 rounded-lg bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 hover:text-white hover:bg-white/10 transition-all"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- DRAGGABLE FLOATING CYBER ASSISTANT AVATAR (GLOBAL) — z-[60] to stay ABOVE bottombar (z-50) -->
        <div 
            :style="{ left: posX + 'px', top: posY + 'px' }"
            class="fixed z-[60] transition-shadow select-none cursor-pointer"
            @mousedown="startDrag"
            @touchstart="startDrag"
            @click="handleAssistantClick"
        >
            <div class="relative">
                <!-- Glowing purple ring -->
                <div class="absolute -inset-1 rounded-full bg-gradient-to-r from-purple-400 to-fuchsia-600 opacity-60 blur-[3px]"></div>
                <div class="relative w-14 h-14 rounded-full border-2 border-purple-400 overflow-hidden shadow-[0_0_15px_rgba(168,85,247,0.6)] bg-[#05020c] flex items-center justify-center">
                    <img src="/images/avatar_assistant.png" alt="ARM AI Assistant" class="w-full h-full object-cover pointer-events-none select-none" />
                </div>
            </div>
        </div>

        <!-- GLOBAL INTERACTIVE CHAT OVERLAY -->
        <div v-if="showAssistantModal" class="fixed inset-0 z-50 flex items-center justify-center p-0 md:p-4 bg-black/90 backdrop-blur-md animate-fadeIn">
            <div class="w-full max-w-md h-full md:h-[80vh] md:max-h-[700px] bg-[#05020c] border border-purple-500/20 rounded-none md:rounded-3xl overflow-hidden shadow-2xl flex flex-col relative">
                
                <!-- Chat Header -->
                <div class="p-4 bg-[#0e071d] border-b border-purple-500/10 flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-3">
                        <button 
                            @click="showAssistantModal = false"
                            class="w-9 h-9 rounded-full bg-black/40 border border-white/5 flex items-center justify-center text-white hover:bg-black/80 transition-all"
                        >
                            <ArrowLeft class="h-4.5 w-4.5" />
                        </button>
                        
                        <div class="relative">
                            <div class="w-10 h-10 rounded-full border border-purple-400 bg-slate-900 overflow-hidden shadow-[0_0_10px_rgba(168,85,247,0.4)]">
                                <img src="/images/avatar_assistant.png" alt="ARM Assistant" class="w-full h-full object-cover" />
                            </div>
                            <span class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-emerald-500 rounded-full border-2 border-[#0e071d]"></span>
                        </div>

                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-xs font-black text-white uppercase tracking-wider">Service</span>
                                <span class="text-[8px] font-black text-emerald-400 uppercase tracking-widest flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-ping"></span>
                                    Temps Réel
                                </span>
                            </div>
                            <span class="text-[9px] text-slate-400 font-mono">team_leader</span>
                        </div>
                    </div>

                    <button 
                        @click="showAssistantModal = false"
                        class="p-2 hover:bg-white/5 rounded-lg text-slate-400 hover:text-white transition-colors"
                    >
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <!-- Chat Messages Body -->
                <div 
                    id="chat-body-container"
                    class="flex-1 p-5 overflow-y-auto space-y-4 bg-gradient-to-b from-[#05020c] to-[#0a0416] scroll-smooth"
                >
                    <div 
                        v-for="(msg, idx) in chatMessages" :key="idx"
                        class="flex flex-col max-w-[80%]"
                        :class="msg.sender === 'user' ? 'ml-auto items-end' : 'mr-auto items-start'"
                    >
                        <!-- Bubble -->
                        <div 
                            class="p-3.5 rounded-2xl text-[11px] leading-relaxed shadow-lg font-sans"
                            :class="msg.sender === 'user' 
                                ? 'bg-gradient-to-r from-purple-500 to-fuchsia-600 text-white font-extrabold rounded-tr-none shadow-[0_0_15px_rgba(168,85,247,0.25)]' 
                                : 'bg-[#0e071d] border border-purple-500/10 text-slate-200 rounded-tl-none'"
                        >
                            {{ msg.text }}
                        </div>
                        <span class="text-[8px] text-slate-500 font-mono mt-1 px-1">
                            {{ msg.time }}
                        </span>
                    </div>
                </div>

                <!-- Chat Input Footer -->
                <div class="p-4 bg-[#0a0416] border-t border-purple-500/10 flex items-center gap-3 shrink-0">
                    <button class="w-10 h-10 rounded-xl bg-black/40 border border-white/5 flex items-center justify-center text-purple-400 hover:text-purple-300 transition-colors">
                        <Image class="h-4.5 w-4.5" />
                    </button>

                    <form @submit.prevent="sendChatMessage" class="flex-1 flex items-center gap-2 relative">
                        <div class="relative flex-1">
                            <input 
                                type="text"
                                v-model="inputMessage"
                                placeholder="Tapez un message..."
                                class="w-full bg-[#120924] border border-purple-500/20 rounded-full py-2.5 pl-4 pr-9 text-[11px] text-white placeholder:text-white/20 outline-none focus:border-purple-400/50 transition-all font-sans"
                            />
                            <!-- Purple indicator dot from screenshot -->
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 w-2 h-2 rounded-full bg-violet-500 shadow-[0_0_6px_rgba(139,92,246,0.6)]"></span>
                        </div>

                        <button 
                            type="submit"
                            class="w-10 h-10 rounded-full bg-purple-600 text-white flex items-center justify-center hover:bg-purple-500 transition-all shadow-[0_0_12px_rgba(168,85,247,0.3)]"
                        >
                            <Send class="h-4 w-4 fill-current" />
                        </button>
                    </form>
                </div>

            </div>
        </div>

    </AppShell>
</template>

<style>
/* Global style rules for Shimmering Vertical lines background */
.bg-grid {
    background-size: 40px 40px;
    background-image: 
        linear-gradient(to right, rgba(168, 85, 247, 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.04) 1px, transparent 1px);
}
.vertical-line {
    position: absolute;
    top: -170px;
    width: 1px;
    height: 170px;
    background: linear-gradient(to bottom, transparent, rgba(168, 85, 247, 0.35) 70%, transparent);
    animation: flowDown 9s linear infinite;
    opacity: 0;
}
@keyframes flowDown {
    0% { transform: translateY(0); opacity: 0; }
    10% { opacity: 0.8; }
    90% { opacity: 0.8; }
    100% { transform: translateY(115vh); opacity: 0; }
}
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fadeInUp {
    animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
/* Animation slide-up pour la bannière de téléchargement */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
.slide-up-enter-to,
.slide-up-leave-from {
    transform: translateY(0);
    opacity: 1;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.animate-fadeIn {
    animation: fadeIn 0.2s ease-out forwards;
}
@keyframes pulse-slow {
    0%, 100% { opacity: 0.5; }
    50% { opacity: 1; }
}
.animate-pulse-slow {
    animation: pulse-slow 6s ease-in-out infinite;
}
@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}
.animate-shimmer {
    animation: shimmer 1.8s infinite linear;
}
.skeleton-shimmer {
    background-size: 200% 100%;
    background-image: linear-gradient(90deg, rgba(168, 85, 247, 0.05) 25%, rgba(168, 85, 247, 0.18) 37%, rgba(168, 85, 247, 0.05) 63%);
    animation: shimmer-swipe 1.6s infinite ease-in-out;
}
@keyframes shimmer-swipe {
    0% { background-position: -200% 0; }
    100% { background-position: 200% 0; }
}
</style>
