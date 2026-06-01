<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/appearance';
import type { BreadcrumbItem } from '@/types';
import { 
    Palette, 
    Moon, 
    Sun, 
    Monitor, 
    Volume2,
    Bell,
    Eye,
    Type
} from 'lucide-vue-next';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Paramètres d\'Apparence',
        href: edit(),
    },
];

// Local storage for preferences
const theme = ref<'light' | 'dark' | 'auto'>(localStorage.getItem('theme') as any || 'dark');
const accentColor = ref(localStorage.getItem('accentColor') || 'violet');
const fontSize = ref(localStorage.getItem('fontSize') || 'normal');
const soundEnabled = ref(localStorage.getItem('soundEnabled') !== 'false');
const animationsEnabled = ref(localStorage.getItem('animationsEnabled') !== 'false');
const notificationsEnabled = ref(localStorage.getItem('notificationsEnabled') !== 'false');

const accentColors = [
    { name: 'Violet', value: 'violet', class: 'from-violet-500 to-purple-600' },
    { name: 'Cyan', value: 'cyan', class: 'from-cyan-500 to-blue-600' },
    { name: 'Emerald', value: 'emerald', class: 'from-emerald-500 to-green-600' },
    { name: 'Rose', value: 'rose', class: 'from-rose-500 to-pink-600' },
    { name: 'Orange', value: 'orange', class: 'from-orange-500 to-red-600' },
    { name: 'Blue', value: 'blue', class: 'from-blue-500 to-indigo-600' },
];

const fontSizes = [
    { name: 'Petit', value: 'small', label: 'Aa' },
    { name: 'Normal', value: 'normal', label: 'Aa' },
    { name: 'Grand', value: 'large', label: 'Aa' },
];

const updateTheme = (newTheme: string) => {
    theme.value = newTheme as any;
    localStorage.setItem('theme', newTheme);
    document.documentElement.classList.toggle('dark', newTheme === 'dark');
};

const updateAccentColor = (color: string) => {
    accentColor.value = color;
    localStorage.setItem('accentColor', color);
};

const updateFontSize = (size: string) => {
    fontSize.value = size;
    localStorage.setItem('fontSize', size);
    const root = document.documentElement;
    if (size === 'small') root.style.fontSize = '14px';
    else if (size === 'large') root.style.fontSize = '18px';
    else root.style.fontSize = '16px';
};

const toggleSound = () => {
    soundEnabled.value = !soundEnabled.value;
    localStorage.setItem('soundEnabled', soundEnabled.value.toString());
};

const toggleAnimations = () => {
    animationsEnabled.value = !animationsEnabled.value;
    localStorage.setItem('animationsEnabled', animationsEnabled.value.toString());
};

const toggleNotifications = () => {
    notificationsEnabled.value = !notificationsEnabled.value;
    localStorage.setItem('notificationsEnabled', notificationsEnabled.value.toString());
};

const resetToDefaults = () => {
    if (confirm('Réinitialiser tous les paramètres d\'apparence par défaut ?')) {
        theme.value = 'dark';
        accentColor.value = 'violet';
        fontSize.value = 'normal';
        soundEnabled.value = true;
        animationsEnabled.value = true;
        notificationsEnabled.value = true;
        localStorage.clear();
        window.location.reload();
    }
};
</script>

<template>
    <AppLayout :breadcrumbItems="breadcrumbItems">
        <Head title="Paramètres d'Apparence" />

        <h1 class="sr-only">Paramètres d'Apparence</h1>

        <SettingsLayout>
            <div class="space-y-8">
                <!-- Header -->
                <div class="flex items-center gap-3 border-b border-white/5 pb-6">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-violet-500/20 to-purple-500/20 border border-violet-500/30">
                        <Palette class="h-6 w-6 text-violet-400" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-white">Paramètres d'Apparence</h2>
                        <p class="text-xs text-muted-foreground mt-1">Personnalisez l'apparence et le comportement de la plateforme</p>
                    </div>
                </div>

                <!-- Theme Section -->
                <div class="p-5 rounded-xl border border-white/10 bg-gradient-to-br from-white/5 to-transparent space-y-4">
                    <div class="flex items-center gap-2 mb-4">
                        <Monitor class="h-5 w-5 text-purple-400" :stroke-width="2.5" />
                        <h3 class="text-lg font-bold text-white">Thème</h3>
                    </div>
                    <p class="text-xs text-white/60">Choisissez entre mode clair, sombre ou automatique</p>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            @click="updateTheme('light')"
                            :class="[
                                'p-4 rounded-lg border-2 transition-all duration-300 flex flex-col items-center gap-2',
                                theme === 'light' 
                                    ? 'border-purple-500/50 bg-purple-500/10' 
                                    : 'border-white/10 bg-white/5 hover:border-white/20'
                            ]"
                        >
                            <Sun class="h-5 w-5 text-yellow-400" :stroke-width="2.5" />
                            <span class="text-xs font-bold text-white">Clair</span>
                        </button>
                        
                        <button
                            @click="updateTheme('dark')"
                            :class="[
                                'p-4 rounded-lg border-2 transition-all duration-300 flex flex-col items-center gap-2',
                                theme === 'dark' 
                                    ? 'border-purple-500/50 bg-purple-500/10' 
                                    : 'border-white/10 bg-white/5 hover:border-white/20'
                            ]"
                        >
                            <Moon class="h-5 w-5 text-slate-400" :stroke-width="2.5" />
                            <span class="text-xs font-bold text-white">Sombre</span>
                        </button>
                        
                        <button
                            @click="updateTheme('auto')"
                            :class="[
                                'p-4 rounded-lg border-2 transition-all duration-300 flex flex-col items-center gap-2',
                                theme === 'auto' 
                                    ? 'border-purple-500/50 bg-purple-500/10' 
                                    : 'border-white/10 bg-white/5 hover:border-white/20'
                            ]"
                        >
                            <Monitor class="h-5 w-5 text-blue-400" :stroke-width="2.5" />
                            <span class="text-xs font-bold text-white">Auto</span>
                        </button>
                    </div>
                </div>

                <!-- Accent Color Section -->
                <div class="p-5 rounded-xl border border-white/10 bg-gradient-to-br from-white/5 to-transparent space-y-4">
                    <div class="flex items-center gap-2 mb-4">
                        <Palette class="h-5 w-5 text-violet-400" :stroke-width="2.5" />
                        <h3 class="text-lg font-bold text-white">Couleur Accentuée</h3>
                    </div>
                    <p class="text-xs text-white/60">Sélectionnez la couleur accentuée pour la plateforme</p>
                    
                    <div class="grid grid-cols-3 md:grid-cols-6 gap-3">
                        <button
                            v-for="color in accentColors"
                            :key="color.value"
                            @click="updateAccentColor(color.value)"
                            :class="[
                                'p-4 rounded-lg border-2 transition-all duration-300 flex flex-col items-center gap-1',
                                accentColor === color.value 
                                    ? 'border-white/50 ring-2 ring-white/20' 
                                    : 'border-white/10 hover:border-white/20'
                            ]"
                        >
                            <div :class="`w-8 h-8 rounded-full bg-gradient-to-br ${color.class}`"></div>
                            <span class="text-[10px] font-bold text-white text-center">{{ color.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Font Size Section -->
                <div class="p-5 rounded-xl border border-white/10 bg-gradient-to-br from-white/5 to-transparent space-y-4">
                    <div class="flex items-center gap-2 mb-4">
                        <Type class="h-5 w-5 text-emerald-400" :stroke-width="2.5" />
                        <h3 class="text-lg font-bold text-white">Taille de Police</h3>
                    </div>
                    <p class="text-xs text-white/60">Ajustez la taille de police pour une meilleure lisibilité</p>
                    
                    <div class="grid grid-cols-3 gap-3">
                        <button
                            v-for="size in fontSizes"
                            :key="size.value"
                            @click="updateFontSize(size.value)"
                            :class="[
                                'p-4 rounded-lg border-2 transition-all duration-300 flex flex-col items-center gap-2',
                                fontSize === size.value 
                                    ? 'border-purple-500/50 bg-purple-500/10' 
                                    : 'border-white/10 bg-white/5 hover:border-white/20'
                            ]"
                        >
                            <span 
                                class="font-bold text-white transition-all"
                                :style="size.value === 'small' ? 'font-size: 12px' : size.value === 'large' ? 'font-size: 20px' : 'font-size: 16px'"
                            >
                                {{ size.label }}
                            </span>
                            <span class="text-[10px] font-bold text-white/60">{{ size.name }}</span>
                        </button>
                    </div>
                </div>

                <!-- Display Options -->
                <div class="p-5 rounded-xl border border-white/10 bg-gradient-to-br from-white/5 to-transparent space-y-4">
                    <div class="flex items-center gap-2 mb-4">
                        <Eye class="h-5 w-5 text-orange-400" :stroke-width="2.5" />
                        <h3 class="text-lg font-bold text-white">Options d'Affichage</h3>
                    </div>

                    <!-- Animations Toggle -->
                    <div class="flex items-center justify-between p-3 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 transition-colors">
                        <div>
                            <p class="text-sm font-bold text-white">Animations</p>
                            <p class="text-xs text-white/60">Activer les animations et les transitions</p>
                        </div>
                        <button
                            @click="toggleAnimations"
                            :class="[
                                'relative w-12 h-6 rounded-full transition-colors duration-300',
                                animationsEnabled 
                                    ? 'bg-gradient-to-r from-purple-500 to-fuchsia-600' 
                                    : 'bg-white/10'
                            ]"
                        >
                            <span :class="[
                                'absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-300',
                                animationsEnabled ? 'translate-x-6' : ''
                            ]"></span>
                        </button>
                    </div>

                    <!-- Sound Toggle -->
                    <div class="flex items-center justify-between p-3 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 transition-colors">
                        <div>
                            <p class="text-sm font-bold text-white">Effets Sonores</p>
                            <p class="text-xs text-white/60">Jouer les sons lors des interactions</p>
                        </div>
                        <button
                            @click="toggleSound"
                            :class="[
                                'relative w-12 h-6 rounded-full transition-colors duration-300',
                                soundEnabled 
                                    ? 'bg-gradient-to-r from-purple-500 to-fuchsia-600' 
                                    : 'bg-white/10'
                            ]"
                        >
                            <span :class="[
                                'absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-300',
                                soundEnabled ? 'translate-x-6' : ''
                            ]"></span>
                        </button>
                    </div>

                    <!-- Notifications Toggle -->
                    <div class="flex items-center justify-between p-3 rounded-lg border border-white/10 bg-white/5 hover:bg-white/10 transition-colors">
                        <div>
                            <p class="text-sm font-bold text-white">Notifications</p>
                            <p class="text-xs text-white/60">Recevoir les notifications de la plateforme</p>
                        </div>
                        <button
                            @click="toggleNotifications"
                            :class="[
                                'relative w-12 h-6 rounded-full transition-colors duration-300',
                                notificationsEnabled 
                                    ? 'bg-gradient-to-r from-purple-500 to-fuchsia-600' 
                                    : 'bg-white/10'
                            ]"
                        >
                            <span :class="[
                                'absolute top-1 left-1 w-4 h-4 bg-white rounded-full transition-transform duration-300',
                                notificationsEnabled ? 'translate-x-6' : ''
                            ]"></span>
                        </button>
                    </div>
                </div>

                <!-- Reset Section -->
                <div class="p-4 rounded-lg border border-red-500/20 bg-red-500/5">
                    <button
                        @click="resetToDefaults"
                        class="w-full px-4 py-2 rounded-lg border border-red-500/30 hover:border-red-500/50 text-red-400 hover:text-red-300 font-bold text-sm transition-all duration-300 uppercase tracking-wider"
                    >
                        Réinitialiser aux Paramètres par Défaut
                    </button>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
