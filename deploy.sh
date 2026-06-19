#!/bin/bash

# =============================================================================
# Script de déploiement pour Hostinger (hébergement partagé)
# npm/Node.js n'est PAS disponible sur le serveur.
# Les assets Vite doivent être COMPILÉS LOCALEMENT puis pushés via Git.
#
# WORKFLOW CORRECT :
#   1. En local  → npm run build
#   2. En local  → git add . && git commit -m "..." && git push
#   3. Sur Hostinger SSH → bash deploy.sh
# =============================================================================

set -e

echo "=== Déploiement ARM Holding sur Hostinger ==="
echo ""

# ── Étape 1 : Vérifier que les assets compilés sont présents ─────────────────
echo "Étape 1: Vérification des assets compilés..."
if [ ! -f "public/build/manifest.json" ]; then
    echo "❌ ERREUR: public/build/manifest.json est manquant!"
    echo ""
    echo "   → Vous devez builder les assets EN LOCAL avant de pousser :"
    echo "      npm run build"
    echo "      git add ."
    echo "      git commit -m 'build assets'"
    echo "      git push"
    exit 1
fi
echo "✅ Assets compilés détectés (public/build/manifest.json)"

# ── Étape 2 : Dossier uploads persistant ─────────────────────────────────────
echo ""
echo "Étape 2: Création du dossier de stockage persistant des images..."
mkdir -p storage/app/public/uploads
chmod -R 775 storage/app/public/uploads 2>/dev/null || true
echo "✅ Dossier storage/app/public/uploads/ prêt"

# ── Étape 3 : Permissions storage & cache ────────────────────────────────────
echo ""
echo "Étape 3: Correction des permissions..."
chmod -R 775 storage/ 2>/dev/null || true
chmod -R 775 bootstrap/cache/ 2>/dev/null || true
echo "✅ Permissions corrigées"

# ── Étape 4 : Lien symbolique storage -> public/storage ──────────────────────
echo ""
echo "Étape 4: Création du lien symbolique storage..."
if [ -L "public/storage" ]; then
    echo "   → Lien déjà existant, mise à jour..."
    rm -f public/storage
fi
php artisan storage:link
echo "✅ Lien symbolique: public/storage -> storage/app/public"

# ── Étape 5 : Migrations Laravel ─────────────────────────────────────────────
echo ""
echo "Étape 5: Migrations de la base de données..."
php artisan migrate --force
echo "✅ Migrations appliquées"

# ── Étape 6 : Migration images (anciens chemins /images/ → /storage/uploads/) ─
echo ""
echo "Étape 6: Migration des chemins d'images en base de données..."
php artisan images:migrate-to-storage
echo "✅ Migration des images terminée"

# ── Étape 7 : Optimisation du cache Laravel ──────────────────────────────────
echo ""
echo "Étape 7: Optimisation Laravel (cache config, routes, vues)..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✅ Cache Laravel optimisé"

# ── Résumé ────────────────────────────────────────────────────────────────────
echo ""
echo "=============================================="
echo "✅ DÉPLOIEMENT TERMINÉ AVEC SUCCÈS !"
echo "=============================================="
echo ""
echo "📁 Images uploadées : storage/app/public/uploads/"
echo "🔗 URL images       : https://armicm.com/storage/uploads/<nom_fichier>"
echo "🌐 Application      : https://armicm.com"
echo ""
echo "⚠️  RAPPEL : Pour le prochain déploiement :"
echo "   1. En LOCAL : npm run build && git push"
echo "   2. Sur SSH  : bash deploy.sh"
