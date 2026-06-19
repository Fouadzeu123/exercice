#!/bin/bash

# Script de déploiement pour Hostinger
# Ce script assure que le build Vite est correctement déployé
# et que les images uploadées en production sont PRÉSERVÉES.

set -e

echo "=== Déploiement ARM Holding sur Hostinger ==="

echo "Étape 1: Installation des dépendances npm..."
npm install --production=false

echo "Étape 2: Compilation avec Vite..."
npm run build

echo "Étape 3: Vérification du répertoire de build..."
if [ ! -d "public/build" ]; then
    echo "ERREUR: Le répertoire public/build n'a pas été créé!"
    exit 1
fi

echo "Étape 4: Vérification des fichiers compilés..."
if [ -f "public/build/manifest.json" ]; then
    echo "✓ manifest.json trouvé"
else
    echo "ERREUR: manifest.json non trouvé!"
    exit 1
fi

echo "Étape 5: Création du dossier de stockage persistant des images uploadées..."
mkdir -p storage/app/public/uploads
chmod -R 775 storage/app/public/uploads

echo "Étape 6: Permissions storage et cache..."
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/

echo "Étape 7: Création du lien symbolique storage -> public/storage..."
# Supprime l'ancien lien s'il existe pour éviter les erreurs
if [ -L "public/storage" ]; then
    echo "  → Ancien lien symbolique trouvé, mise à jour..."
    rm public/storage
fi
php artisan storage:link
echo "✓ Lien symbolique créé: public/storage -> storage/app/public"

echo "Étape 8: Optimisation Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "✓ Déploiement réussi!"
echo "Les images uploadées sont dans: storage/app/public/uploads/"
echo "Accessibles via: https://armicm.com/storage/uploads/<nom_image>"
echo "IMPORTANT: Ce dossier est HORS de Git — les images ne seront JAMAIS supprimées lors des push."
