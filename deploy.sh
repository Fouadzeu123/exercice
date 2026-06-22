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
# Les fichiers doivent survivre aux déploiements Git/npm.
# Solution : dossier réel HORS du projet + lien symbolique dans public/
echo ""
echo "Étape 2: Mise en place du stockage d'uploads persistant..."

# Déterminer le dossier home de l'utilisateur courant
USER_HOME="$(eval echo ~$(whoami))"
PERSISTENT_UPLOADS_DIR="${USER_HOME}/uploads_persistant"

# Créer le dossier persistant s'il n'existe pas
if [ ! -d "$PERSISTENT_UPLOADS_DIR" ]; then
    mkdir -p "$PERSISTENT_UPLOADS_DIR"
    echo "  → Dossier persistant créé : $PERSISTENT_UPLOADS_DIR"
else
    echo "  → Dossier persistant existant trouvé : $PERSISTENT_UPLOADS_DIR"
fi
chmod -R 775 "$PERSISTENT_UPLOADS_DIR" 2>/dev/null || true

# Supprimer le dossier public/uploads s'il existe déjà (non-symlink)
if [ -d "public/uploads" ] && [ ! -L "public/uploads" ]; then
    # S'il y a des fichiers, les déplacer vers le persistant
    if [ "$(ls -A public/uploads 2>/dev/null)" ]; then
        echo "  → Migration des anciens fichiers uploads vers le dossier persistant..."
        mv public/uploads/* "$PERSISTENT_UPLOADS_DIR/" 2>/dev/null || true
    fi
    rm -rf public/uploads
fi

# Créer le lien symbolique si nécessaire
if [ ! -L "public/uploads" ]; then
    ln -s "$PERSISTENT_UPLOADS_DIR" public/uploads
    echo "  → Lien symbolique créé : public/uploads → $PERSISTENT_UPLOADS_DIR"
else
    echo "  → Lien symbolique existant : public/uploads → $(readlink public/uploads)"
fi
echo "✅ Stockage uploads persistant configuré (${PERSISTENT_UPLOADS_DIR})"

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
echo "📁 Images uploadées (persistant) : ~/uploads_persistant/"
echo "🔗 Lien symbolique              : public/uploads/ → ~/uploads_persistant/"
echo "🔗 URL images                   : https://armicm.com/uploads/<nom_fichier>"
echo "🌐 Application                  : https://armicm.com"
echo ""
echo "ℹ️  Les fichiers dans ~/uploads_persistant/ survivent aux déploiements."
echo ""
echo "⚠️  RAPPEL pour le prochain déploiement :"
echo "   1. LOCAL : npm run build → git push"
echo "   2. SSH   : bash deploy.sh"
