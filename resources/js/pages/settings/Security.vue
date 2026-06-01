<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ShieldCheck, Lock, Smartphone, Clock, AlertTriangle, CheckCircle2 } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import { useRevealAnimation } from '@/composables/useRevealAnimation';
import userPassword from '@/routes/user-password';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/security';
import { disable, enable } from '@/routes/two-factor';
import type { BreadcrumbItem } from '@/types';

type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Paramètres de Sécurité',
        href: edit(),
    },
];

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
const { containerRef } = useRevealAnimation();
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Paramètres de Sécurité" />

        <h1 class="sr-only">Paramètres de Sécurité</h1>

        <SettingsLayout>
            <div ref="containerRef" class="space-y-8">
                <!-- Header -->
                <div data-animate="fade-down" class="flex items-center gap-3 border-b border-white/5 pb-6">
                    <div class="p-3 rounded-xl bg-gradient-to-br from-purple-500/20 to-fuchsia-500/20 border border-purple-500/30">
                        <ShieldCheck class="h-6 w-6 text-purple-400" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-black text-white">Paramètres de Sécurité</h2>
                        <p class="text-xs text-muted-foreground mt-1">Protégez votre compte avec des paramètres avancés</p>
                    </div>
                </div>

                <!-- Password Update Section -->
                <div data-animate="fade-up" data-delay="100" class="space-y-4 p-5 rounded-xl border border-white/10 bg-gradient-to-br from-white/5 to-transparent">
                    <div class="flex items-center gap-2 mb-4">
                        <Lock class="h-5 w-5 text-purple-400" :stroke-width="2.5" />
                        <h3 class="text-lg font-bold text-white">Mettre à Jour le Mot de Passe</h3>
                    </div>
                    <p class="text-xs text-white/60 font-light">
                        Assurez-vous que votre compte utilise un mot de passe long et aléatoire pour rester sécurisé.
                    </p>

                    <Form
                        v-bind="userPassword.update.form()"
                        :options="{
                            preserveScroll: true,
                        }"
                        reset-on-success
                        :reset-on-error="[
                            'password',
                            'password_confirmation',
                            'current_password',
                        ]"
                        class="space-y-4 mt-6"
                        v-slot="{ errors, processing, recentlySuccessful }"
                    >
                        <div class="space-y-2">
                            <Label for="current_password" class="text-xs font-bold text-white uppercase tracking-wide">Mot de passe actuel</Label>
                            <PasswordInput
                                id="current_password"
                                name="current_password"
                                class="mt-1 block w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-purple-500/50 focus:outline-none"
                                autocomplete="current-password"
                                placeholder="••••••••••••••"
                            />
                            <InputError :message="errors.current_password" class="text-xs text-red-400" />
                        </div>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <Label for="password" class="text-xs font-bold text-white uppercase tracking-wide">Nouveau mot de passe</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    class="mt-1 block w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-purple-500/50 focus:outline-none"
                                    autocomplete="new-password"
                                    placeholder="••••••••••••••"
                                />
                                <InputError :message="errors.password" class="text-xs text-red-400" />
                            </div>

                            <div class="space-y-2">
                                <Label for="password_confirmation" class="text-xs font-bold text-white uppercase tracking-wide">Confirmer le mot de passe</Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    class="mt-1 block w-full bg-black/40 border border-white/10 rounded-lg px-4 py-3 text-white focus:border-purple-500/50 focus:outline-none"
                                    autocomplete="new-password"
                                    placeholder="••••••••••••••"
                                />
                                <InputError :message="errors.password_confirmation" class="text-xs text-red-400" />
                            </div>
                        </div>

                        <div class="flex items-center gap-4 pt-4">
                            <Button
                                type="submit"
                                :disabled="processing"
                                class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-fuchsia-600 hover:from-purple-400 hover:to-fuchsia-500 text-white font-bold rounded-lg transition-all duration-300 hover:shadow-[0_0_20px_rgba(168,85,247,0.3)] disabled:opacity-50"
                            >
                                {{ processing ? 'Mise à jour...' : 'Enregistrer le Mot de Passe' }}
                            </Button>

                            <Transition
                                enter-active-class="transition ease-in-out duration-300"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out duration-300"
                                leave-to-class="opacity-0"
                            >
                                <div v-show="recentlySuccessful" class="flex items-center gap-2 text-sm text-emerald-400 font-semibold">
                                    <CheckCircle2 class="h-4 w-4" :stroke-width="2.5" />
                                    <span>Mot de passe mis à jour avec succès !</span>
                                </div>
                            </Transition>
                        </div>
                    </Form>
                </div>

                <!-- Two-Factor Authentication Section -->
                <div v-if="canManageTwoFactor" data-animate="fade-up" data-delay="150" class="space-y-4">
                    <div class="p-5 rounded-xl border border-white/10 bg-gradient-to-br from-white/5 to-transparent">
                        <div class="flex items-center gap-2 mb-4">
                            <Smartphone class="h-5 w-5 text-purple-400" :stroke-width="2.5" />
                            <h3 class="text-lg font-bold text-white">Authentification à Deux Facteurs (2FA)</h3>
                        </div>
                        <p class="text-xs text-white/60 font-light">
                            Renforcez la sécurité de votre compte en activant l'authentification à deux facteurs.
                        </p>

                        <div v-if="!twoFactorEnabled" class="mt-6 space-y-4">
                            <div class="p-4 rounded-lg border border-orange-500/30 bg-orange-500/5 flex items-start gap-3">
                                <AlertTriangle class="h-5 w-5 text-orange-400 mt-0.5 shrink-0" :stroke-width="2.5" />
                                <div class="text-xs text-white/80">
                                    <p class="font-semibold mb-1">Sécurité Non Maximale</p>
                                    <p>Lorsque vous activez l'authentification à deux facteurs, vous serez invité à entrer un code de sécurité lors de votre connexion.</p>
                                </div>
                            </div>

                            <div>
                                <Button
                                    v-if="hasSetupData"
                                    @click="showSetupModal = true"
                                    class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-fuchsia-600 hover:from-purple-400 hover:to-fuchsia-500 text-white font-bold rounded-lg transition-all duration-300 hover:shadow-[0_0_20px_rgba(168,85,247,0.3)] flex items-center gap-2"
                                >
                                    <ShieldCheck class="h-4 w-4" :stroke-width="2.5" />
                                    Continuer la Configuration
                                </Button>
                                <Form
                                    v-else
                                    v-bind="enable.form()"
                                    @success="showSetupModal = true"
                                    #default="{ processing }"
                                >
                                    <Button 
                                        type="submit" 
                                        :disabled="processing"
                                        class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-fuchsia-600 hover:from-purple-400 hover:to-fuchsia-500 text-white font-bold rounded-lg transition-all duration-300 hover:shadow-[0_0_20px_rgba(168,85,247,0.3)] disabled:opacity-50 flex items-center gap-2"
                                    >
                                        <Smartphone class="h-4 w-4" :stroke-width="2.5" />
                                        {{ processing ? 'Activation...' : 'Activer 2FA' }}
                                    </Button>
                                </Form>
                            </div>
                        </div>

                        <div v-else class="mt-6 space-y-4">
                            <div class="p-4 rounded-lg border border-emerald-500/30 bg-emerald-500/5 flex items-start gap-3">
                                <CheckCircle2 class="h-5 w-5 text-emerald-400 mt-0.5 shrink-0" :stroke-width="2.5" />
                                <div class="text-xs text-white/80">
                                    <p class="font-semibold mb-1">Authentification à Deux Facteurs Activée</p>
                                    <p>Vous serez invité à entrer un code de sécurité lors de votre connexion. Ce code peut être récupéré depuis l'application TOTP sur votre téléphone.</p>
                                </div>
                            </div>

                            <div>
                                <Form v-bind="disable.form()" #default="{ processing }">
                                    <Button
                                        variant="destructive"
                                        type="submit"
                                        :disabled="processing"
                                        class="px-6 py-2.5 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-400 hover:to-red-500 text-white font-bold rounded-lg transition-all duration-300 hover:shadow-[0_0_20px_rgba(239,68,68,0.3)] disabled:opacity-50"
                                    >
                                        {{ processing ? 'Désactivation...' : 'Désactiver 2FA' }}
                                    </Button>
                                </Form>
                            </div>

                            <TwoFactorRecoveryCodes />
                        </div>
                    </div>

                    <TwoFactorSetupModal
                        v-model:isOpen="showSetupModal"
                        :requiresConfirmation="requiresConfirmation"
                        :twoFactorEnabled="twoFactorEnabled"
                    />
                </div>

                <!-- Security Tips -->
                <div data-animate="scale-up" data-delay="200" class="p-5 rounded-xl border border-purple-500/20 bg-gradient-to-br from-purple-500/10 to-transparent">
                    <h4 class="text-sm font-bold text-purple-300 mb-3 uppercase tracking-wide">Conseils de Sécurité</h4>
                    <ul class="space-y-2 text-xs text-white/70">
                        <li class="flex items-start gap-2">
                            <span class="text-purple-400 font-bold">•</span>
                            <span>Utilisez un mot de passe unique et fort contenant au moins 12 caractères</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-400 font-bold">•</span>
                            <span>Activez toujours l'authentification à deux facteurs pour maximiser votre sécurité</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-400 font-bold">•</span>
                            <span>Ne partagez jamais vos codes de récupération 2FA avec quiconque</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-purple-400 font-bold">•</span>
                            <span>Connectez-vous régulièrement sur des appareils de confiance uniquement</span>
                        </li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
