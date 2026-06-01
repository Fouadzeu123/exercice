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

// Plugin Vue — s'enregistre automatiquement au démarrage
export const CapacitorPlugin = {
    install(_app: App) {
        if (!isNative()) return;

        // Configure la StatusBar immédiatement
        const initStatusBar = async () => {
            try {
                const { StatusBar, Style } = await import('@capacitor/status-bar');
                await StatusBar.setStyle({ style: Style.Dark });
                await StatusBar.setBackgroundColor({ color: '#05020c' });
                await StatusBar.show();
            } catch (e) {
                // silently fail on web
            }
        };

        // Cache le splash screen après que l'app est prête
        const hideSplash = async () => {
            try {
                const { SplashScreen } = await import('@capacitor/splash-screen');
                await SplashScreen.hide({ fadeOutDuration: 500 });
            } catch (e) {
                // silently fail on web
            }
        };

        initStatusBar();

        // Donne à Inertia le temps de charger la première page
        setTimeout(hideSplash, 1500);
    },
};
