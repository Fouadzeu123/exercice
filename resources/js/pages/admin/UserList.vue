<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { 
    Users, 
    Search,
    ShieldAlert,
    ChevronLeft
} from 'lucide-vue-next';

const props = defineProps<{
    users: Array<{
        id: number;
        phone: string;
        balance: string;
        vip_level: number;
        avip_level: number;
        role: string;
        created_at: string;
    }>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Console Admin',
        href: '/admin',
    },
    {
        title: 'Utilisateurs',
        href: '/admin/users',
    },
];

const searchQuery = ref('');

const filteredUsers = computed(() => {
    if (!searchQuery.value) return props.users;
    const query = searchQuery.value.toLowerCase();
    return props.users.filter(u => 
        u.phone.toLowerCase().includes(query) || 
        u.vip_level.toString().includes(query) ||
        u.role.toLowerCase().includes(query)
    );
});

const formatXAF = (value: number | string) => {
    const num = typeof value === 'string' ? parseFloat(value) : value;
    return new Intl.NumberFormat('fr-FR').format(num) + ' FCFA';
};
</script>

<template>
    <Head title="Registre Utilisateurs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 md:p-6 max-w-7xl mx-auto w-full text-foreground">
            
            <!-- Header -->
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-white/5 pb-5">
                <div class="flex items-center gap-3">
                    <Link href="/admin" class="p-2 rounded-xl bg-white/5 border border-white/10 text-muted-foreground hover:text-white transition-all">
                        <ChevronLeft class="h-4 w-4" />
                    </Link>
                    <div>
                        <h2 class="text-xl md:text-2xl font-bold text-white flex items-center gap-2">
                            <Users class="h-6 w-6 text-primary drop-shadow-[0_0_8px_rgba(0,255,255,0.4)]" />
                            Registre des Utilisateurs
                        </h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Registre global des mineurs d'infrastructure ARM.</p>
                    </div>
                </div>
                
                <div class="relative max-w-xs w-full">
                    <Search class="h-3.5 w-3.5 text-muted-foreground absolute left-3 top-3" />
                    <input 
                        v-model="searchQuery" 
                        type="text" 
                        placeholder="Rechercher par téléphone..." 
                        class="bg-black/50 border border-white/10 text-white font-mono text-xs pl-9 pr-4 h-9 rounded-xl focus:outline-none focus:ring-1 focus:ring-primary w-full"
                    />
                </div>
            </div>

            <!-- Table of users -->
            <div class="glass rounded-2xl p-5 border border-white/5">
                <div v-if="filteredUsers.length === 0" class="py-12 text-center text-xs text-muted-foreground font-mono">
                    [ INFOS ] : Aucun utilisateur trouvé.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="border-b border-white/5 text-left text-[10px] font-extrabold uppercase tracking-widest text-muted-foreground">
                                <th class="pb-3">Numéro / ID</th>
                                <th class="pb-3">Solde Disponible</th>
                                <th class="pb-3">Niveau VIP</th>
                                <th class="pb-3">VIP Alternatif (AVIP)</th>
                                <th class="pb-3">Rôle Système</th>
                                <th class="pb-3 text-right">Inscrit Le</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5 text-xs font-mono">
                            <tr v-for="u in filteredUsers" :key="u.id" class="text-white/80 hover:bg-white/[0.01] transition-all">
                                <td class="py-3.5 font-bold text-white flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ u.phone }}
                                </td>
                                <td class="py-3.5 font-extrabold text-primary">
                                    {{ formatXAF(u.balance) }}
                                </td>
                                <td class="py-3.5 font-bold">
                                    VIP {{ u.vip_level }}
                                </td>
                                <td class="py-3.5 text-muted-foreground">
                                    AVIP {{ u.avip_level }}
                                </td>
                                <td class="py-3.5">
                                    <span class="px-2 py-0.5 rounded text-[8px] uppercase tracking-widest font-extrabold"
                                        :class="u.role === 'admin' ? 'bg-primary/20 text-primary border border-primary/20' : 'bg-white/5 text-muted-foreground'"
                                    >
                                        {{ u.role }}
                                    </span>
                                </td>
                                <td class="py-3.5 text-right text-muted-foreground">
                                    {{ new Date(u.created_at).toLocaleDateString() }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
