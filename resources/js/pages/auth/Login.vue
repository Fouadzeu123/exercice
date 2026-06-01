<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { Phone, Lock, LogIn } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>
    <Head title="Connexion" />
    <div class="relative min-h-screen flex flex-col bg-black overflow-hidden">
        
        <!-- Background: Vertical Purple Shimmering Lines -->
        <div class="absolute inset-0 z-0 pointer-events-none overflow-hidden">
            <div class="absolute inset-0 bg-grid opacity-15"></div>
            <div class="vertical-line" style="left: 8%; animation-delay: 0s; animation-duration: 9s;"></div>
            <div class="vertical-line" style="left: 24%; animation-delay: 2.5s; animation-duration: 7s;"></div>
            <div class="vertical-line" style="left: 42%; animation-delay: 4.8s; animation-duration: 10s;"></div>
            <div class="vertical-line" style="left: 58%; animation-delay: 1.2s; animation-duration: 8s;"></div>
            <div class="vertical-line" style="left: 76%; animation-delay: 3.5s; animation-duration: 11s;"></div>
            <div class="vertical-line" style="left: 90%; animation-delay: 5.2s; animation-duration: 8.5s;"></div>
            <div class="absolute top-[20%] left-[20%] w-96 h-96 rounded-full bg-purple-500/5 blur-[120px]"></div>
            <div class="absolute bottom-[20%] right-[20%] w-[350px] h-[350px] rounded-full bg-purple-500/5 blur-[150px]"></div>
        </div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col flex-grow w-full max-w-xl mx-auto px-5 py-6">

            <!-- Server Image Section with Bubbles -->
            <div class="relative w-full h-44 rounded-2xl overflow-hidden bg-slate-950 border border-purple-500/15 shadow-lg">
                <img 
                    src="/images/cyber_server_hero.png" 
                    alt="Infrastructure Serveur ARM" 
                    class="w-full h-full object-cover opacity-90 object-center"
                />
                <!-- Bubbles -->
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
                <!-- Scanner -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-purple-400 to-transparent shadow-[0_0_12px_rgba(168,85,247,0.8)] animate-scanner pointer-events-none"></div>
            </div>

            <!-- Brand / Logo -->
            <div class="flex flex-col items-center gap-1.5 mt-6 mb-2">
                <Link :href="home()" class="group">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl border border-purple-500/20 bg-black/40 transition-transform duration-500 group-hover:scale-105 overflow-hidden">
                        <img src="/images/logo.jpg" class="h-full w-full object-cover" alt="ARM Logo" />
                    </div>
                </Link>
                <h1 class="text-base font-bold tracking-wider text-white uppercase">Connexion</h1>
                <p class="text-[10px] text-purple-400/80 font-medium tracking-wide uppercase">Accédez à votre espace d'investissement</p>
            </div>

            <!-- Status Alerts if any -->
            <div
                v-if="status"
                class="mt-4 text-center text-xs font-semibold text-purple-400 border border-purple-500/30 p-3 rounded-xl bg-purple-950/20 shadow-[0_0_10px_rgba(168,85,247,0.1)] font-mono animate-pulse"
            >
                {{ status }}
            </div>

            <!-- Login Form -->
            <Form
                v-bind="store.form({ phone: '237', password: '' })"
                :reset-on-success="['password']"
                v-slot="{ errors, processing }"
                class="flex flex-col gap-5 mt-6 flex-grow"
            >
                <!-- Phone -->
                <div class="grid gap-2">
                    <Label class="text-purple-400 text-xs font-bold flex items-center gap-1.5" for="phone">
                        <Phone class="h-3.5 w-3.5" :stroke-width="2.5" />
                        Numéro de téléphone
                    </Label>
                    <Input
                        id="phone"
                        type="tel"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="tel"
                        name="phone"
                        placeholder="Ex: 690000000"
                        class="bg-black/50 border-purple-500/20 text-white focus-visible:ring-purple-400 focus-visible:border-purple-400 transition-all duration-300 neon-border font-mono text-sm pl-4 h-12 rounded-xl"
                    />
                    <InputError :message="errors.phone" class="text-[10px] text-rose-400" />
                </div>

                <!-- Password -->
                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label class="text-purple-400 text-xs font-bold flex items-center gap-1.5" for="password">
                            <Lock class="h-3.5 w-3.5" :stroke-width="2.5" />
                            Mot de passe
                        </Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="''"
                            class="text-[10px] text-purple-400/60 hover:text-purple-400 transition-colors font-bold uppercase tracking-wider"
                            :tabindex="5"
                        >
                            Mot de passe oublié ?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        name="password"
                        placeholder="Saisissez votre mot de passe"
                        class="bg-black/50 border-purple-500/20 text-white focus-visible:ring-purple-400 focus-visible:border-purple-400 transition-all duration-300 neon-border text-sm pl-4 h-12 rounded-xl"
                    />
                    <InputError :message="errors.password" class="text-[10px] text-rose-400" />
                </div>

                <!-- Remember me Checkbox -->
                <div class="flex items-center justify-between mt-1">
                    <Label for="remember" class="flex items-center space-x-3 text-slate-300 text-xs font-bold cursor-pointer group">
                        <Checkbox 
                            id="remember" 
                            name="remember" 
                            :tabindex="3" 
                            class="border-purple-500/40 bg-black/40 data-[state=checked]:bg-purple-500 data-[state=checked]:text-black transition-all group-hover:border-purple-400 rounded-md" 
                        />
                        <span class="transition-colors group-hover:text-purple-400 select-none">Mémoriser la session</span>
                    </Label>
                </div>

                <!-- Submit Button -->
                <Button
                    type="submit"
                    class="mt-2 w-full bg-purple-500 text-black hover:bg-purple-400 transition-all duration-300 shadow-[0_0_15px_rgba(168,85,247,0.3)] hover:shadow-[0_0_25px_rgba(168,85,247,0.5)] font-extrabold tracking-wider text-sm uppercase h-12 rounded-xl"
                    tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner v-if="processing" class="text-black" />
                    <span v-else class="flex items-center gap-2">
                        <LogIn class="h-4 w-4" :stroke-width="2.5" />
                        SE CONNECTER
                    </span>
                </Button>

                <!-- Link to Register -->
                <div class="text-center text-sm text-slate-400 mt-2 pt-4 border-t border-white/5 font-semibold" v-if="canRegister">
                    Pas encore de compte ?
                    <TextLink
                        :href="register()"
                        class="text-purple-400 underline underline-offset-4 font-extrabold ml-1.5 hover:text-white transition-colors"
                        :tabindex="5"
                    >S'inscrire</TextLink>
                </div>
            </Form>
        </div>
    </div>
</template>

<style scoped>
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
    0% { transform: translateY(0); opacity: 0; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { transform: translateY(115vh); opacity: 0; }
}
@keyframes scanner {
    0% { top: 0%; opacity: 0.1; }
    10% { opacity: 1; }
    90% { opacity: 1; }
    100% { top: 100%; opacity: 0.1; }
}
.animate-scanner {
    animation: scanner 5s linear infinite;
}
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
.bubble:nth-child(1) { left: 8%; width: 10px; height: 10px; animation-duration: 5.5s; animation-delay: 0s; }
.bubble:nth-child(2) { left: 22%; width: 6px; height: 6px; animation-duration: 7s; animation-delay: 1.5s; }
.bubble:nth-child(3) { left: 38%; width: 14px; height: 14px; animation-duration: 6.2s; animation-delay: 0.4s; }
.bubble:nth-child(4) { left: 52%; width: 8px; height: 8px; animation-duration: 8s; animation-delay: 2s; }
.bubble:nth-child(5) { left: 68%; width: 12px; height: 12px; animation-duration: 5.8s; animation-delay: 0.9s; }
.bubble:nth-child(6) { left: 82%; width: 6px; height: 6px; animation-duration: 6.8s; animation-delay: 3s; }
.bubble:nth-child(7) { left: 28%; width: 9px; height: 9px; animation-duration: 7.6s; animation-delay: 2.5s; }
.bubble:nth-child(8) { left: 74%; width: 8px; height: 8px; animation-duration: 7.1s; animation-delay: 0.7s; }
@keyframes rise {
    0% { transform: translateY(0) scale(1); opacity: 0; }
    10% { opacity: 0.5; }
    90% { opacity: 0.5; }
    100% { transform: translateY(-210px) scale(0.6); opacity: 0; }
}
</style>
