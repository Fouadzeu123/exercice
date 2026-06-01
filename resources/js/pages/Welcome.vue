<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ShieldCheck, ArrowRight, Server, Phone, LogIn } from 'lucide-vue-next';
</script>

<template>
    <Head title="ARM HOLDING - Infrastructure AI" />
    <div class="min-h-screen bg-[#05020c] text-white flex flex-col font-sans relative overflow-x-hidden pb-12">
        <!-- Ambient Grid Background -->
        <div class="absolute inset-0 bg-grid opacity-15 pointer-events-none z-0"></div>
        <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-purple-500/10 blur-[120px] pointer-events-none"></div>
        <div class="absolute top-1/2 -right-40 w-[450px] h-[450px] rounded-full bg-fuchsia-500/5 blur-[150px] pointer-events-none"></div>

        <!-- Header -->
        <header class="w-full max-w-7xl mx-auto px-6 py-5 flex items-center justify-between border-b border-purple-500/10 backdrop-blur-md bg-black/20 z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-fuchsia-600 flex items-center justify-center font-bold text-white text-base shadow-[0_0_20px_rgba(168,85,247,0.4)] animate-pulse">
                    ARM
                </div>
                <div>
                    <h2 class="text-base font-black tracking-widest text-transparent bg-clip-text bg-gradient-to-r from-purple-400 to-fuchsia-500 font-mono">
                        ARM HOLDING
                    </h2>
                    <p class="text-[9px] text-purple-400/60 uppercase tracking-widest font-mono">Plateforme d'Investissement</p>
                </div>
            </div>
            
            <div class="flex items-center gap-1.5 font-mono text-[9px] px-3 py-1 rounded-full bg-purple-950/40 border border-purple-500/20 text-purple-400">
                <span class="h-1.5 w-1.5 rounded-full bg-purple-400 animate-ping"></span>
                <span>PLATEFORME ACTIVE</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-grow w-full max-w-xl mx-auto px-6 flex flex-col justify-center gap-8 mt-10 z-10">
            
            <!-- Server Image Card -->
            <div class="relative group">
                <!-- Glowing ambient shadow -->
                <div class="absolute -inset-1 rounded-2xl bg-gradient-to-r from-purple-500 via-violet-600 to-fuchsia-600 opacity-25 blur-lg transition duration-1000"></div>
                
                <div class="relative rounded-2xl border border-purple-500/20 bg-black/60 p-4 backdrop-blur-xl overflow-hidden shadow-[0_0_30px_rgba(168,85,247,0.03)]">
                    
                    <!-- Server Image Frame with Bubbles -->
                    <div class="relative h-60 rounded-xl overflow-hidden bg-slate-950 border border-purple-500/10">
                        <img 
                            src="/images/cyber_server_hero.png" 
                            alt="Serveur d'Investissement ARM" 
                            class="w-full h-full object-cover opacity-90 object-center transition-transform duration-700 group-hover:scale-102"
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

                        <!-- Animated Scanning Line -->
                        <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-purple-500 to-transparent shadow-[0_0_12px_rgba(168,85,247,0.8)] animate-scanner pointer-events-none"></div>
                    </div>

                    <!-- Welcome Title & Copy -->
                    <div class="text-center space-y-2 mt-4">
                        <h1 class="text-xl font-bold tracking-normal text-white uppercase">
                            Infrastructure Serveur AI
                        </h1>
                        <p class="text-xs text-slate-300 leading-relaxed px-2">
                            Prenez part à la puissance de calcul des serveurs de dernière génération et recevez des revenus journaliers stables.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Call to Actions -->
            <div class="flex flex-col gap-4">
                <Link 
                    href="/register" 
                    class="group relative flex items-center justify-center gap-2 w-full py-4 bg-purple-600 text-white font-extrabold text-xs uppercase tracking-wider rounded-xl hover:bg-purple-500 transition-all duration-300 shadow-[0_0_20px_rgba(168,85,247,0.35)] hover:shadow-[0_0_30px_rgba(168,85,247,0.6)]"
                >
                    <span>CRÉER UN COMPTE</span>
                    <ArrowRight class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </Link>
                
                <Link 
                    href="/login" 
                    class="flex items-center justify-center gap-2 w-full py-4 bg-transparent border border-purple-500/30 text-purple-400 font-bold text-xs uppercase tracking-wider rounded-xl hover:bg-purple-950/20 hover:border-purple-500/60 hover:text-white transition-all duration-300"
                >
                    <LogIn class="h-4 w-4" />
                    <span>SE CONNECTER</span>
                </Link>
            </div>

        </main>
        
        <!-- Footer -->
        <footer class="w-full text-center mt-auto font-mono text-[9px] text-slate-500 tracking-wider">
            ARM HOLDING &copy; 2026. TOUS DROITS RÉSERVÉS.
        </footer>
    </div>
</template>

<style scoped>
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
    animation: scanner 4.5s linear infinite;
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
.bubble:nth-child(1) { left: 10%; width: 12px; height: 12px; animation-duration: 5s; animation-delay: 0s; }
.bubble:nth-child(2) { left: 25%; width: 8px; height: 8px; animation-duration: 7s; animation-delay: 1.5s; }
.bubble:nth-child(3) { left: 42%; width: 18px; height: 18px; animation-duration: 6s; animation-delay: 0.5s; }
.bubble:nth-child(4) { left: 55%; width: 10px; height: 10px; animation-duration: 8s; animation-delay: 2s; }
.bubble:nth-child(5) { left: 70%; width: 14px; height: 14px; animation-duration: 5.5s; animation-delay: 1s; }
.bubble:nth-child(6) { left: 85%; width: 7px; height: 7px; animation-duration: 6.5s; animation-delay: 3s; }
.bubble:nth-child(7) { left: 30%; width: 11px; height: 11px; animation-duration: 7.5s; animation-delay: 2.5s; }
.bubble:nth-child(8) { left: 78%; width: 9px; height: 9px; animation-duration: 6.8s; animation-delay: 0.8s; }

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
        transform: translateY(-260px) scale(0.5);
        opacity: 0;
    }
}
</style>
