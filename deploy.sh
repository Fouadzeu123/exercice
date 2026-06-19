#!/bin/bash

# =============================================================================
# Script de déploiement pour Hostinger (hébergement partagé)
#
# PRÉREQUIS: npm/Node.js n'est PAS disponible sur le serveur.
# Les assets Vite doivent être compilés LOCALEMENT puis pushés via Git.
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
    echo "   → Compilez les assets EN LOCAL avant de pousser :"
    echo "      npm run build && git add . && git commit && git push"
    exit 1
fi
echo "✅ Assets compilés détectés"

# ── Étape 2 : Dossier uploads persistant (hors Git) ──────────────────────────
echo ""
echo "Étape 2: Création du dossier uploads persistant..."
mkdir -p public/uploads
chmod -R 775 public/uploads 2>/dev/null || true
echo "✅ Dossier public/uploads/ prêt (hors Git, persistant)"

# ── Étape 3 : Permissions storage & cache ────────────────────────────────────
echo ""
echo "Étape 3: Correction des permissions..."
chmod -R 775 storage/ 2>/dev/null || true
chmod -R 775 bootstrap/cache/ 2>/dev/null || true
echo "✅ Permissions corrigées"

# ── Étape 4 : Migrations Laravel ─────────────────────────────────────────────
echo ""
echo "Étape 4: Migrations de la base de données..."
php artisan migrate --force
echo "✅ Migrations appliquées"

# ── Étape 5 : Migration images (anciens chemins /images/ → /uploads/) ─────────
echo ""
echo "Étape 5: Migration des chemins d'images en base de données..."
php artisan images:migrate-to-storage
echo "✅ Migration des images terminée"

# ── Étape 6 : Optimisation du cache Laravel ──────────────────────────────────
echo ""
echo "Étape 6: Optimisation Laravel (cache config, routes, vues)..."
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
echo "📁 Images uploadées : public/uploads/"
echo "🔗 URL images       : https://armicm.com/uploads/<nom_fichier>"
echo "🌐 Application      : https://armicm.com"
echo ""
echo "⚠️  RAPPEL pour le prochain déploiement :"
echo "   1. LOCAL : npm run build → git push"
echo "   2. SSH   : bash deploy.sh"
