<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const isAuthLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isAuthLoading.value = false;
    }, 550);
});
</script>

<template>
    <div class="relative flex min-h-screen flex-col items-center justify-center p-4 md:p-8 overflow-hidden bg-black">
        
        <!-- Shimmering Vertical Purple Lines Background -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <!-- Clean Background Grid -->
            <div class="absolute inset-0 bg-grid opacity-15"></div>
            
            <!-- Purple vertical lines scrolling down -->
            <div class="vertical-line" style="left: 8%; animation-delay: 0s; animation-duration: 9s;"></div>
            <div class="vertical-line" style="left: 24%; animation-delay: 2.5s; animation-duration: 7s;"></div>
            <div class="vertical-line" style="left: 42%; animation-delay: 4.8s; animation-duration: 10s;"></div>
            <div class="vertical-line" style="left: 58%; animation-delay: 1.2s; animation-duration: 8s;"></div>
            <div class="vertical-line" style="left: 76%; animation-delay: 3.5s; animation-duration: 11s;"></div>
            <div class="vertical-line" style="left: 90%; animation-delay: 5.2s; animation-duration: 8.5s;"></div>
            
            <!-- Soft ambient purple glows -->
            <div class="absolute top-[20%] left-[20%] w-96 h-96 rounded-full bg-purple-500/5 blur-[120px] animate-pulse-glow"></div>
            <div class="absolute bottom-[20%] right-[20%] w-[350px] h-[350px] rounded-full bg-purple-500/5 blur-[150px] animate-pulse-glow" style="animation-duration: 6s;"></div>
        </div>

        <!-- Main Form Wrapper (Uniform for Mobile & Desktop) -->
        <div class="w-full max-w-md z-10 my-4 relative group">
            <!-- Soft glowing background shadow around the card -->
            <div class="absolute -inset-0.5 rounded-2xl bg-gradient-to-r from-purple-500/20 to-fuchsia-500/20 opacity-40 blur-md pointer-events-none"></div>
            
            <div class="relative rounded-2xl border border-purple-500/20 bg-black/85 backdrop-blur-xl overflow-hidden shadow-[0_0_50px_rgba(0,0,0,0.6)]">
                
                <!-- TOP SECTION: Server Image with Bubbles & Scanner -->
                <div class="relative h-48 w-full border-b border-purple-500/20 bg-slate-950 overflow-hidden">
                    <img 
                        src="/images/cyber_server_hero.png" 
                        alt="Infrastructure Serveur ARM" 
                        class="w-full h-full object-cover opacity-90 object-center"
                    />
                    
                    <!-- Floating Bubbles Over Image -->
                    <div class="bubbles-container">
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                        <div class="bubble"></div>
                    </div>
                    
                    <!-- Scanner laser beam -->
                    <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-purple-400 to-transparent shadow-[0_0_12px_rgba(168,85,247,0.8)] animate-scanner pointer-events-none"></div>
                </div>
                
                <!-- BOTTOM SECTION: Form and Brand -->
                <div class="p-6 md:p-8">
                    <!-- Brand Area -->
                    <div class="flex flex-col items-center gap-2 mb-6">
                        <Link :href="home()" class="group flex flex-col items-center gap-1.5">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-purple-500/20 bg-black/40 transition-transform duration-500 group-hover:scale-105 overflow-hidden">
                                <img src="/images/logo.jpg" class="h-full w-full object-cover" alt="ARM Logo" />
                            </div>
                            <span class="sr-only">{{ title }}</span>
                        </Link>
                        <div class="text-center">
                            <h2 class="text-sm font-bold tracking-wider text-white uppercase font-sans">{{ title }}</h2>
                            <p class="text-[10px] text-purple-400/80 font-medium tracking-wide mt-0.5 uppercase">{{ description }}</p>
                        </div>
                    </div>

                    <!-- Slots for form with Shimmer Skeleton Screen -->
                    <div v-if="isAuthLoading" class="space-y-5 animate-pulse">
                        <!-- Input 1 -->
                        <div class="space-y-2">
                            <div class="h-4 w-28 bg-purple-500/10 border border-purple-500/20 rounded-md skeleton-shimmer"></div>
                            <div class="h-12 w-full bg-purple-500/5 border border-purple-500/10 rounded-xl skeleton-shimmer"></div>
                        </div>
                        <!-- Input 2 -->
                        <div class="space-y-2">
                            <div class="h-4 w-28 bg-purple-500/10 border border-purple-500/20 rounded-md skeleton-shimmer"></div>
                            <div class="h-12 w-full bg-purple-500/5 border border-purple-500/10 rounded-xl skeleton-shimmer"></div>
                        </div>
                        <!-- Button -->
                        <div class="h-12 w-full bg-purple-500/20 border border-purple-500/30 rounded-xl skeleton-shimmer mt-6"></div>
                    </div>
                    <slot v-else />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Vertical Purple Shimmering Lines CSS */
.vertical-line {
    position: absolute;
    top: -170px;
    width: 1px;
    height: 170px;
    background: linear-gradient(to bottom, transparent, rgba(168, 85, 247, 0.4) 70%, transparent);
    animation: flowDown 9s linear infinite;
    opacity: 0;
}

@keyframes flowDown {
    0% {
        transform: translateY(0);
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        transform: translateY(115vh);
        opacity: 0;
    }
}

/* Horizontal Laser scanner */
@keyframes scanner {
    0% {
        top: 0%;
        opacity: 0.1;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    100% {
        top: 100%;
        opacity: 0.1;
    }
}

.animate-scanner {
    animation: scanner 5s linear infinite;
}

/* Bubbles Animation styles */
.bubbles-container {
    position: absolute;
    inset: 0;
    pointer-events: none;
    overflow: hidden;
    z-index: 10;
}
.bubble {
    position: absolute;
    bottom: -20px;
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.3);
    border-radius: 50%;
    animation: rise 6s infinite ease-in;
}
/* Differ bubble sizes, positions and delays for a natural look */
.bubble:nth-child(1) { left: 8%; width: 10px; height: 10px; animation-duration: 5.5s; animation-delay: 0s; }
.bubble:nth-child(2) { left: 22%; width: 6px; height: 6px; animation-duration: 7s; animation-delay: 1.5s; }
.bubble:nth-child(3) { left: 38%; width: 14px; height: 14px; animation-duration: 6.2s; animation-delay: 0.4s; }
.bubble:nth-child(4) { left: 52%; width: 8px; height: 8px; animation-duration: 8s; animation-delay: 2s; }
.bubble:nth-child(5) { left: 68%; width: 12px; height: 12px; animation-duration: 5.8s; animation-delay: 0.9s; }
.bubble:nth-child(6) { left: 82%; width: 6px; height: 6px; animation-duration: 6.8s; animation-delay: 3s; }
.bubble:nth-child(7) { left: 28%; width: 9px; height: 9px; animation-duration: 7.6s; animation-delay: 2.5s; }
.bubble:nth-child(8) { left: 74%; width: 8px; height: 8px; animation-duration: 7.1s; animation-delay: 0.7s; }

@keyframes rise {
    0% {
        transform: translateY(0) scale(1);
        opacity: 0;
    }
    10% {
        opacity: 0.5;
    }
    90% {
        opacity: 0.5;
    }
    100% {
        transform: translateY(-210px) scale(0.6);
        opacity: 0;
    }
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

.bg-grid {
    background-size: 40px 40px;
    background-image: 
        linear-gradient(to right, rgba(168, 85, 247, 0.04) 1px, transparent 1px),
        linear-gradient(to bottom, rgba(168, 85, 247, 0.04) 1px, transparent 1px);
}
</style>
