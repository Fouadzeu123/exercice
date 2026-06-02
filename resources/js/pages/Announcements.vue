<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import {
    Megaphone,
    Clock,
    AlertTriangle,
    CheckCircle2,
    Zap,
    Bell,
    X,
    Server,
    Cpu,
    ArrowRight,
    ChevronLeft,
    ChevronRight
} from 'lucide-vue-next';

const props = defineProps<{
    announcements: Array<{
        id: number;
        title: string;
        content: string;
        type: 'info' | 'warning' | 'success' | 'alert';
        created_at: string;
        active: boolean;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de Bord', href: '/dashboard' },
    { title: 'Actualités Tech', href: '/announcements' },
];

const selectedAnnouncement = ref<any | null>(null);

const getTypeIcon = (type: string) => {
    switch (type) {
        case 'warning': return AlertTriangle;
        case 'success': return CheckCircle2;
        case 'alert': return Zap;
        default: return Bell;
    }
};

const getTypeColors = (type: string) => {
    switch (type) {
        case 'warning': return { text: 'text-orange-400', badge: 'bg-orange-500/10 border-orange-500/20 text-orange-400' };
        case 'success': return { text: 'text-emerald-400', badge: 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400' };
        case 'alert': return { text: 'text-rose-400', badge: 'bg-rose-500/10 border-rose-500/20 text-rose-400' };
        default: return { text: 'text-cyan-400', badge: 'bg-cyan-500/10 border-cyan-500/20 text-cyan-400' };
    }
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
};

// Technology server images mapping
import { onMounted } from 'vue';

const techImages = [
    'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1591453089816-0fbb971b454c?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1563770660941-20978e870e26?auto=format&fit=crop&w=600&q=80',
    'https://images.unsplash.com/photo-1677442136019-21780efad99a?auto=format&fit=crop&w=600&q=80'
];

const getTechImage = (id: number) => {
    return techImages[id % techImages.length];
};

const isLoading = ref(true);

onMounted(() => {
    setTimeout(() => {
        isLoading.value = false;
    }, 600);
});

const fallbackAnnouncements = [
    {
        id: 101,
        title: 'Déploiement des puces IA ARM Neoverse V3',
        content: 'ARM Holding annonce l\'intégration mondiale de sa nouvelle architecture Neoverse V3 au cœur des plus grands supercalculateurs d\'IA. Ces puces offrent une efficacité énergétique inédite pour le traitement de modèles de langage (LLM) de plus de 100 milliards de paramètres. Grâce à une bande passante mémoire doublée et des instructions vectorielles optimisées, les nœuds d\'infrastructure ARM atteignent des vitesses d\'apprentissage sans précédent.',
        type: 'info',
        created_at: new Date(Date.now() - 3600000 * 2).toISOString(),
        active: true
    },
    {
        id: 102,
        title: 'Nouveau Serveur ARM Helios AI pour Data Centers',
        content: 'Conçu spécifiquement pour le traitement massif en temps réel des réseaux de neurones profonds, le serveur Helios AI intègre 4 puces accélératrices ARM d\'inférence directe. Ce système révolutionne les performances de calcul pour les entreprises cloud partenaires. Le serveur Helios AI se connecte directement à la grille globale d\'ARM Holding, permettant aux locataires de nœuds de co-traiter des modèles de vision par ordinateur en moins de 5 millisecondes.',
        type: 'success',
        created_at: new Date(Date.now() - 3600000 * 12).toISOString(),
        active: true
    },
    {
        id: 103,
        title: 'Cortex-X95 : L\'IA générative directement sur puces ARM',
        content: 'Le nouveau processeur ARM Cortex-X95 intègre un moteur neuronal (NPU) de dernière génération permettant l\'exécution locale ultra-rapide d\'algorithmes d\'IA générative. Une avancée majeure pour les terminaux Edge connectés au réseau global d\'ARM Holding. Cette technologie réduit la latence de traitement de 80% tout en préservant la confidentialité des flux de co-traitement décentralisés.',
        type: 'alert',
        created_at: new Date(Date.now() - 3600000 * 24).toISOString(),
        active: true
    },
    {
        id: 104,
        title: 'Inauguration du Quantum Grid ARM à Singapour',
        content: 'Le consortium ARM Secure Net vient de déployer un cluster de calcul hybride silicium-quantique à Singapour. Cette infrastructure permet le co-traitement distribué ultra-sécurisé via cryptographie post-quantique. Les utilisateurs de nœuds réseau bénéficient désormais d\'une stabilité accrue de 25% lors des phases de génération intensive.',
        type: 'success',
        created_at: new Date(Date.now() - 3600000 * 36).toISOString(),
        active: true
    },
    {
        id: 105,
        title: 'Refroidissement Cryogénique Actif sur Nœuds Oméga',
        content: 'Afin d\'accompagner les records de calcul de l\'IA générative, ARM Holding déploie des systèmes de refroidissement cryogénique liquide à base de micro-canaux directs. Cette technologie permet aux supercalculateurs de tourner à plein régime sans aucune baisse de performance thermique.',
        type: 'info',
        created_at: new Date(Date.now() - 3600000 * 48).toISOString(),
        active: true
    },
    {
        id: 106,
        title: 'Partenariat Stratégique ARM & NVIDIA Blackwell',
        content: 'Une alliance technologique majeure unit désormais ARM Holding aux processeurs graphiques de nouvelle génération Blackwell. L\'objectif est de concevoir la plateforme ultime de co-traitement décentralisé grand public, reliant téléphones mobiles et serveurs clouds d\'IA.',
        type: 'warning',
        created_at: new Date(Date.now() - 3600000 * 60).toISOString(),
        active: true
    }
];

const activeAnnouncements = computed(() => {
    const list = props.announcements && props.announcements.length > 0
        ? props.announcements
        : fallbackAnnouncements;

    return list
        .filter(a => a.active)
        .map(a => {
            // Dynamic filtering & adaptation to ARM holding IA chips & servers
            if (!a.title.toLowerCase().includes('arm') && !a.title.toLowerCase().includes('ia') && !a.title.toLowerCase().includes('puce')) {
                return {
                    ...a,
                    title: "Serveur ARM Holding - Optimisation IA v" + a.id,
                    content: "Mise à niveau critique de l'infrastructure de traitement ARM Holding. Le co-processeur de neurones artificiels a été calibré avec succès pour accroître la vitesse de génération des récompenses de location de 15%. " + a.content
                };
            }
            return a;
        })
        .sort((a, b) => new Date(b.created_at).getTime() - new Date(a.created_at).getTime());
});

// Pagination state & logic
const currentPage = ref(1);
const itemsPerPage = 3;

const totalPages = computed(() => {
    return Math.ceil(activeAnnouncements.value.length / itemsPerPage);
});

const paginatedAnnouncements = computed(() => {
    const start = (currentPage.value - 1) * itemsPerPage;
    const end = start + itemsPerPage;
    return activeAnnouncements.value.slice(start, end);
});

const nextPage = () => {
    if (currentPage.value < totalPages.value) {
        currentPage.value++;
    }
};

const prevPage = () => {
    if (currentPage.value > 1) {
        currentPage.value--;
    }
};

const { containerRef } = useRevealAnimation();
</script>

<template>
    <Head title="Actualités Tech" />
    <AppLayout :breadcrumbs="breadcrumbs">

        <!-- SKELETON LOADING VIEW -->
        <div v-if="isLoading" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24 px-2 animate-pulse">
            <!-- Header Shimmer -->
            <div class="h-16 w-full rounded-2xl skeleton-shimmer border border-white/5"></div>
            <!-- News Cards Shimmers -->
            <div v-for="i in 2" :key="i" class="h-80 w-full rounded-3xl skeleton-shimmer border border-white/5"></div>
        </div>

        <!-- MAIN NEWS CONTENT -->
        <div v-else ref="containerRef" class="relative w-full max-w-xl mx-auto flex flex-col gap-5 pt-3 pb-24 transition-all duration-500 ease-out">

            <!-- HEADER -->
            <div data-animate="fade-down" class="flex items-center justify-between bg-gradient-to-r from-purple-950/20 via-black/40 to-transparent p-4 rounded-2xl border border-purple-500/10 shadow-lg mx-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl border border-purple-500/20 bg-purple-950/20 flex items-center justify-center text-purple-400">
                        <Megaphone class="h-6 w-6" />
                    </div>
                    <div>
                        <h2 class="text-md font-black text-white uppercase tracking-wide">News & Tech</h2>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider mt-0.5">Serveurs et technologies ARM Holdings</p>
                    </div>
                </div>
            </div>

            <!-- ANNOUNCEMENTS LIST -->
            <div data-stagger="true" class="space-y-4 px-2">
                <div v-if="activeAnnouncements.length === 0" class="bg-gradient-to-b from-[#0a0416]/90 to-[#05020c]/90 border border-purple-500/15 rounded-3xl p-10 text-center shadow-2xl backdrop-blur-sm">
                    <Server class="h-10 w-10 text-purple-400/20 mx-auto mb-3.5" />
                    <p class="text-xs font-black text-white uppercase tracking-wider">Aucune actualité disponible</p>
                    <p class="text-[15px] text-slate-400 mt-1.5 font-mono">[ ARM SECURE NET - CONNECTED ]</p>
                </div>

                <div
                    v-for="ann in paginatedAnnouncements" :key="ann.id"
                    data-animate="fade-up"
                    @click="selectedAnnouncement = ann"
                    class="bg-gradient-to-b from-[#0a0416]/90 to-[#05020c]/90 border border-purple-500/15 rounded-3xl overflow-hidden shadow-2xl backdrop-blur-sm cursor-pointer group hover:border-purple-400/30 transition-all duration-300 flex flex-col"
                >
                    <!-- Server Tech Card Image -->
                    <div class="w-full h-40 overflow-hidden relative border-b border-purple-500/10">
                        <div class="absolute inset-0 bg-gradient-to-t from-[#05020c] to-transparent z-10"></div>
                        <img
                            :src="getTechImage(ann.id)"
                            alt="ARM Computing Cluster"
                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                            loading="lazy"
                        />
                        <div class="absolute top-3 right-3 z-20">
                            <span
                                class="text-[9px] font-black px-2 py-0.5 rounded border uppercase tracking-wider shadow"
                                :class="ann.type === 'warning' ? 'bg-orange-500/10 border-orange-500/20 text-orange-400'
                                      : ann.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'
                                      : ann.type === 'alert' ? 'bg-fuchsia-500/10 border-fuchsia-500/20 text-fuchsia-400'
                                      : 'bg-purple-500/10 border-purple-500/20 text-purple-400'"
                            >
                                {{ ann.type }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-5 flex-1 flex flex-col justify-between">
                        <div>
                            <h3 class="text-xs font-black text-white uppercase tracking-wider line-clamp-2 group-hover:text-purple-400 transition-colors">
                                {{ ann.title }}
                            </h3>
                            <p class="text-[12px] text-slate-300 leading-relaxed mt-2.5 line-clamp-3">
                                {{ ann.content }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between border-t border-white/5 pt-3.5 mt-4 text-[15px] text-slate-500 font-mono">
                            <div class="flex items-center gap-1.5">
                                <Clock class="h-3.5 w-3.5 text-purple-400" />
                                <span>{{ formatDate(ann.created_at) }}</span>
                            </div>
                            <span class="text-purple-400 font-bold uppercase tracking-wider flex items-center gap-1">
                                Lire l'article
                                <ArrowRight class="h-3 w-3 transition-transform duration-300 group-hover:translate-x-1" />
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAGINATION CONTROLS -->
            <div v-if="totalPages > 1" data-animate="fade-up" class="flex items-center justify-between bg-black/40 border border-purple-500/10 px-4 py-3 rounded-2xl mx-4 shadow-lg shrink-0">
                <button
                    @click="prevPage"
                    :disabled="currentPage === 1"
                    class="w-10 h-10 rounded-xl bg-purple-950/20 border border-purple-500/20 flex items-center justify-center text-purple-400 hover:bg-purple-500/20 disabled:opacity-30 disabled:hover:bg-purple-950/20 transition-all cursor-pointer"
                >
                    <ChevronLeft class="h-5 w-5" />
                </button>

                <span class="text-xs font-black text-slate-300 font-mono">
                    PAGE {{ currentPage }} SUR {{ totalPages }}
                </span>

                <button
                    @click="nextPage"
                    :disabled="currentPage === totalPages"
                    class="w-10 h-10 rounded-xl bg-purple-950/20 border border-purple-500/20 flex items-center justify-center text-purple-400 hover:bg-purple-500/20 disabled:opacity-30 disabled:hover:bg-purple-950/20 transition-all cursor-pointer"
                >
                    <ChevronRight class="h-5 w-5" />
                </button>
            </div>

        </div>

        <!-- NEW DETAILED ATTRACTIVE VIEW MODAL -->
        <div
            v-if="selectedAnnouncement"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/90 backdrop-blur-sm animate-fadeIn"
            @click="selectedAnnouncement = null"
        >
            <div
                @click.stop
                class="w-full max-w-sm bg-[#05020c] border border-purple-500/20 rounded-3xl overflow-hidden shadow-2xl relative flex flex-col max-h-[85vh]"
            >
                <!-- Hero tech image -->
                <div class="w-full h-48 relative shrink-0">
                    <img
                        :src="getTechImage(selectedAnnouncement.id)"
                        alt="ARM Server Cluster"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute inset-0 bg-gradient-to-t from-[#05020c] via-transparent to-black/40"></div>

                    <!-- Close button -->
                    <button
                        @click="selectedAnnouncement = null"
                        class="absolute top-4 right-4 w-8 h-8 rounded-full bg-black/60 border border-white/10 flex items-center justify-center text-white/80 hover:bg-black hover:text-white transition-all z-20"
                    >
                        <X class="h-4.5 w-4.5" />
                    </button>

                    <div class="absolute bottom-4 left-4 right-4 z-10">
                        <span
                            class="text-[8px] font-black px-2 py-0.5 rounded border uppercase tracking-wider shadow inline-block mb-2"
                            :class="selectedAnnouncement.type === 'warning' ? 'bg-orange-500/10 border-orange-500/20 text-orange-400'
                                  : selectedAnnouncement.type === 'success' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'
                                  : selectedAnnouncement.type === 'alert' ? 'bg-fuchsia-500/10 border-fuchsia-500/20 text-fuchsia-400'
                                  : 'bg-purple-500/10 border-purple-500/20 text-purple-400'"
                        >
                            {{ selectedAnnouncement.type }}
                        </span>
                        <h2 class="text-xs font-black text-white uppercase tracking-wider leading-snug">
                            {{ selectedAnnouncement.title }}
                        </h2>
                    </div>
                </div>

                <!-- Text content body -->
                <div class="p-5 overflow-y-auto space-y-4 text-[10px] text-slate-300 leading-relaxed font-sans">
                    <p class="whitespace-pre-line">
                        {{ selectedAnnouncement.content }}
                    </p>

                    <div class="p-3.5 rounded-xl bg-purple-950/20 border border-purple-500/10 flex items-center gap-2.5">
                        <Cpu class="h-4 w-4 text-purple-400 shrink-0" />
                        <span class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">
                            Actualité authentifiée par le consortium technologique ARM Holdings.
                        </span>
                    </div>
                </div>

                <!-- Footer button -->
                <div class="p-4 border-t border-white/5 shrink-0 bg-black/20 flex items-center justify-between">
                    <div class="flex items-center gap-1 text-[8px] text-slate-500 font-mono">
                        <Clock class="h-3 w-3 text-purple-400" />
                        <span>{{ formatDate(selectedAnnouncement.created_at) }}</span>
                    </div>
                    <button
                        @click="selectedAnnouncement = null"
                        class="py-2 px-5 rounded-xl bg-purple-600 text-white font-extrabold uppercase tracking-wider text-[15px] hover:bg-purple-500 transition-all shadow-[0_0_15px_rgba(168,85,247,0.3)]"
                    >
                        Fermer
                    </button>
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
