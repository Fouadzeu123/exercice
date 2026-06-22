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

# ── Étape 2 : Dossier uploads PERSISTANT (hors dossier projet) ───────────────
# PROBLÈME HOSTINGER : Le Document Root est public_html/ MAIS Laravel public_path()
# pointe vers public_html/public/. Les fichiers uploadés vont dans public/uploads/
# mais le serveur web cherche à la racine public_html/uploads/.
# SOLUTION : 1 dossier persistant + 2 liens symboliques.
echo ""
echo "Étape 2: Mise en place du stockage d'uploads persistant..."

# Déterminer le dossier home et le dossier projet
USER_HOME="$(eval echo ~$(whoami))"
PROJECT_DIR="$(pwd)"
PERSISTENT_UPLOADS_DIR="${USER_HOME}/uploads_persistant"

# ── 2a. Créer le dossier persistant HORS du projet ──
if [ ! -d "$PERSISTENT_UPLOADS_DIR" ]; then
    mkdir -p "$PERSISTENT_UPLOADS_DIR"
    echo "  ✓ Dossier persistant créé : $PERSISTENT_UPLOADS_DIR"
else
    echo "  ✓ Dossier persistant existant : $PERSISTENT_UPLOADS_DIR"
fi
chmod -R 775 "$PERSISTENT_UPLOADS_DIR" 2>/dev/null || true

# ── 2b. Symlink Laravel : public/uploads → ~/uploads_persistant ──
# Nécessaire pour que PHP (AdminController) écrive dans le bon dossier via public_path('uploads')
if [ -d "public/uploads" ] && [ ! -L "public/uploads" ]; then
    if [ "$(ls -A public/uploads 2>/dev/null | grep -v '.gitignore' | grep -v '.gitkeep')" ]; then
        echo "  → Migration des anciens fichiers vers le dossier persistant..."
        mv public/uploads/* "$PERSISTENT_UPLOADS_DIR/" 2>/dev/null || true
    fi
    rm -rf public/uploads
fi
if [ ! -L "public/uploads" ]; then
    ln -s "$PERSISTENT_UPLOADS_DIR" public/uploads
    echo "  ✓ Symlink Laravel créé    : public/uploads → $PERSISTENT_UPLOADS_DIR"
else
    echo "  ✓ Symlink Laravel existant : public/uploads → $(readlink public/uploads)"
fi

# ── 2c. Symlink web root : ../uploads → public/uploads ──
# CRITIQUE : Hostinger sert depuis public_html/ mais uploads est dans public_html/public/
# Sans ce symlink, https://armicm.com/uploads/fichier.jpg retourne 404
WEB_ROOT_DIR="$(dirname "$PROJECT_DIR")"
WEB_ROOT_UPLOADS="${WEB_ROOT_DIR}/uploads"
if [ -d "$WEB_ROOT_UPLOADS" ] && [ ! -L "$WEB_ROOT_UPLOADS" ]; then
    rm -rf "$WEB_ROOT_UPLOADS"
fi
if [ ! -L "$WEB_ROOT_UPLOADS" ]; then
    ln -s "$PERSISTENT_UPLOADS_DIR" "$WEB_ROOT_UPLOADS"
    echo "  ✓ Symlink web root créé   : $WEB_ROOT_UPLOADS → $PERSISTENT_UPLOADS_DIR"
else
    echo "  ✓ Symlink web root existant: $WEB_ROOT_UPLOADS → $(readlink "$WEB_ROOT_UPLOADS")"
fi

echo "✅ Stockage uploads persistant configuré"
echo "   Chemin physique : $PERSISTENT_UPLOADS_DIR"
echo "   URL d'accès     : https://armicm.com/uploads/<fichier>"

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
echo "📁 Stockage persistant   : ~/uploads_persistant/"
echo "🔗 Symlink Laravel       : [projet]/public/uploads → ~/uploads_persistant/"
echo "🔗 Symlink Web Root      : [public_html]/uploads  → ~/uploads_persistant/"
echo "🔗 URL images            : https://armicm.com/uploads/<nom_fichier>"
echo "🌐 Application           : https://armicm.com"
echo ""
echo "ℹ️  Les fichiers dans ~/uploads_persistant/ survivent à tous les déploiements."
echo "ℹ️  Pour accéder au diagnostic : https://armicm.com/diagnose_images.php?token=arm_diag_2026"
echo ""
echo "⚠️  RAPPEL pour le prochain déploiement :"
echo "   1. LOCAL : npm run build → git push"
echo "   2. SSH   : bash deploy.sh"
