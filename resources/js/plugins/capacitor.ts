/**
 * Plugin Capacitor pour ARM Holding
 * - Cache le splash screen après le chargement de l'application
 * - Configure la barre de statut en mode cosmique
 * - Fournit des utilitaires de retour haptique
 */
import type { App } from 'vue';

// Détecte si l'app tourne dans Capacitor (natif) ou dans un navigateur
export const isNative = (): boolean => {
    return typeof (window as any).Capacitor !== 'undefined' && (window as any).Capacitor.isNative;
};

// Utilitaires Haptics — retour tactile sur les actions clés
export const hapticSuccess = async (): Promise<void> => {
    if (!isNative()) return;
    try {
        const { Haptics, ImpactStyle } = await import('@capacitor/haptics');
        await Haptics.impact({ style: ImpactStyle.Medium });
    } catch {}
};

export const hapticLight = async (): Promise<void> => {
    if (!isNative()) return;
    try {
        const { Haptics, ImpactStyle } = await import('@capacitor/haptics');
        await Haptics.impact({ style: ImpactStyle.Light });
    } catch {}
};

export const hapticError = async (): Promise<void> => {
    if (!isNative()) return;
    try {
        const { Haptics, NotificationType } = await import('@capacitor/haptics');
        await Haptics.notification({ type: NotificationType.Error });
    } catch {}
};

// Helper pour afficher un toast dynamique au style cyberpunk futuriste
const showNativeToast = (message: string) => {
    const existingToast = document.getElementById('native-cyber-toast');
    if (existingToast) {
        existingToast.remove();
    }

    const toast = document.createElement('div');
    toast.id = 'native-cyber-toast';
    toast.className = 'fixed bottom-24 left-1/2 -translate-x-1/2 z-[9999] px-5 py-3 rounded-xl border border-cyan-500/30 bg-[#05020c]/90 text-cyan-400 font-mono text-xs tracking-wider uppercase shadow-[0_0_20px_rgba(6,182,212,0.3)] backdrop-blur-md transition-all duration-300 opacity-0 translate-y-4 pointer-events-none';
    toast.innerText = message;
    
    document.body.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translate(-50%, 0)';
    });
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translate(-50%, 16px)';
        setTimeout(() => {
            toast.remove();
        }, 300);
    }, 2000);
};

// Plugin Vue — s'enregistre automatiquement au démarrage
export const CapacitorPlugin = {
    install(_app: App) {
        // Configure la StatusBar dès que possible
        const initStatusBar = async () => {
            if (!isNative()) return;
            try {
                const { StatusBar, Style } = await import('@capacitor/status-bar');
                await StatusBar.setStyle({ style: Style.Dark });
                await StatusBar.setBackgroundColor({ color: '#05020c' });
                await StatusBar.show();
            } catch (e) {
                // silently fail on web
            }
        };

        // Configuration du bouton retour matériel (Android)
        const initBackButton = async () => {
            if (!isNative()) return;
            try {
                const { App: CapApp } = await import('@capacitor/app');
                const { router } = await import('@inertiajs/vue3');
                
                const exitPaths = ['/dashboard', '/login', '/register', '/'];
                let lastTimeBackButtonWasPressed = 0;
                const timePeriodToExit = 2000;

                CapApp.addListener('backButton', async () => {
                    const currentPath = window.location.pathname;

                    if (exitPaths.includes(currentPath)) {
                        const currentTime = new Date().getTime();
                        if (currentTime - lastTimeBackButtonWasPressed < timePeriodToExit) {
                            CapApp.exitApp();
                        } else {
                            lastTimeBackButtonWasPressed = currentTime;
                            showNativeToast("Appuyez à nouveau pour quitter");
                            
                            // Retour tactile haptique discret
                            try {
                                const { Haptics, ImpactStyle } = await import('@capacitor/haptics');
                                await Haptics.impact({ style: ImpactStyle.Light });
                            } catch {}
                        }
                    } else {
                        // S'il y a d'autres pages dans l'historique
                        if (window.history.length > 1) {
                            window.history.back();
                        } else {
                            // Sinon redirige vers le tableau de bord principal
                            router.visit('/dashboard');
                        }
                    }
                });
            } catch (e) {
                // silently fail
            }
        };

        // Cache le splash screen après que l'app est prête
        const hideSplash = async () => {
            if (!isNative()) return;
            try {
                const { SplashScreen } = await import('@capacitor/splash-screen');
                await SplashScreen.hide({ fadeOutDuration: 500 });
            } catch (e) {
                // silently fail on web
            }
        };

        // Essaye d'initialiser la StatusBar et le bouton retour (immédiatement ou après un léger délai)
        if (isNative()) {
            initStatusBar();
            initBackButton();
        } else {
            setTimeout(initStatusBar, 500);
            setTimeout(initStatusBar, 1500);
        }

        // Donne à l'application le temps de se charger et cache le Splash Screen
        // Plus de blocage grâce à de multiples tentatives et l'auto-hide natif
        setTimeout(hideSplash, 1000);
        setTimeout(hideSplash, 2000);
        setTimeout(hideSplash, 3500);
    },
};
