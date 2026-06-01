#!/bin/bash

# Script de diagnostic pour Hostinger
# Ce script aide à diagnostiquer les problèmes de déploiement

echo "=== DIAGNOSTIC DE DÉPLOIEMENT ARM HOLDING ==="
echo ""

echo "1. Vérification de la structure du projet..."
echo "   Répertoire courant: $(pwd)"
echo "   Contenu de la racine:"
ls -la | head -20

echo ""
echo "2. Vérification du répertoire public..."
if [ -d "public" ]; then
    echo "   ✓ Répertoire 'public' trouvé"
    echo "   Contenu de public/:"
    ls -la public/ | head -10
else
    echo "   ✗ Répertoire 'public' NOT FOUND!"
    exit 1
fi

echo ""
echo "3. Vérification du répertoire build..."
if [ -d "public/build" ]; then
    echo "   ✓ Répertoire 'public/build' trouvé"
    echo "   Fichiers compilés:"
    ls -la public/build/ | head -15
else
    echo "   ✗ Répertoire 'public/build' NOT FOUND!"
    echo "   Relancer le build: npm run build"
    exit 1
fi

echo ""
echo "4. Vérification de manifest.json..."
if [ -f "public/build/manifest.json" ]; then
    echo "   ✓ manifest.json trouvé"
    echo "   Taille: $(wc -c < public/build/manifest.json) bytes"
else
    echo "   ✗ manifest.json NOT FOUND!"
    exit 1
fi

echo ""
echo "5. Vérification des permissions..."
echo "   Permissions de public/build/:"
stat -c "%A %u:%g" public/build/ 2>/dev/null || stat -f "%A" public/build/ 2>/dev/null || echo "   (Impossible de vérifier les permissions)"

echo ""
echo "6. Configuration Hostinger requise..."
echo "   ✓ Document root doit pointer vers: $(pwd)/public"
echo "   ✓ Fichiers compilés sont dans: $(pwd)/public/build/"
echo "   ✓ Fichier index.php est dans: $(pwd)/public/index.php"

echo ""
echo "=== DIAGNOSTIC TERMINÉ ==="
echo ""
echo "Si vous voyez 'ERROR: No output directory found after build':"
echo "1. Vérifiez que le document root cPanel pointe vers 'public'"
echo "2. Vérifiez que Node.js est disponible sur le serveur"
echo "3. Vérifiez les permissions des fichiers"
echo "4. Consultez les logs cPanel pour plus de détails"
