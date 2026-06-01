<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthLayout from '@/layouts/AuthLayout.vue';
</script>

<template>
    <AuthLayout
        title="Mot de passe oublié"
        description="La réinitialisation de mot de passe par e-mail n'est pas disponible."
    >
        <Head title="Mot de passe oublié" />
        <div class="text-center text-sm text-muted-foreground">
            Veuillez contacter notre support client pour obtenir de l'aide afin de réinitialiser votre mot de passe.
        </div>
    </AuthLayout>
</template>
