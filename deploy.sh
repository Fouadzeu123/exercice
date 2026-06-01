#!/bin/bash

# Script de déploiement pour Hostinger
# Ce script assure que le build Vite est correctement déployé

set -e

echo "=== Déploiement ARM Holding sur Hostinger ==="
echo "Étape 1: Installation des dépendances npm..."
npm install

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

echo "Étape 5: Affichage des fichiers compilés..."
ls -la public/build/ | head -20

echo ""
echo "✓ Déploiement réussi!"
echo "Les fichiers compilés sont prêts dans le répertoire public/build/"
echo "Le serveur web doit pointer vers le répertoire 'public' comme racine"
