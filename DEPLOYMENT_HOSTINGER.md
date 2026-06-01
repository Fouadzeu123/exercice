# Configuration de déploiement pour Hostinger

## ⚠️ Configuration requise sur Hostinger

### 1. Répertoire racine du site web
Le répertoire racine de votre site web **doit pointer vers le dossier `public`** et non à la racine du projet.

**Dans cPanel:**
- Allez à **File Manager** ou **Public_html**
- Assurez-vous que le document root pointe vers `/public`
- Ou créez un lien symbolique : `ln -s /path/to/project/public public_html`

### 2. Node.js et NPM
Assurez-vous que Node.js est installé sur votre serveur Hostinger:
```bash
node -v
npm -v
```

### 3. Build automatique
Le script de build est configuré dans `package.json`:
```bash
npm run build
```

Cela va:
1. Installer les dépendances (`npm install`)
2. Compiler les assets avec Vite (`vite build`)
3. Générer le manifest.json dans `public/build/`

### 4. Structure après build
```
public/
├── build/
│   ├── manifest.json
│   ├── assets/
│   └── ...
├── index.php
└── .htaccess
```

### 5. Déploiement
Deux options:

**Option A: Via cPanel Git Repository** (Recommandé)
1. Push votre code vers GitHub
2. Dans cPanel, utilisez Git Repository pour cloner
3. Configurez un hook post-pull pour exécuter: `bash deploy.sh`

**Option B: Manuel via FTP**
1. Téléchargez les fichiers via FTP
2. SSH et exécutez: `bash deploy.sh`

### 6. Vérification
Après le build, vérifiez:
```bash
# Fichiers compilés existent
ls -la public/build/

# manifest.json présent
cat public/build/manifest.json
```

### 7. Permissions
S'il y a des erreurs de permissions:
```bash
chmod -R 755 public/build/
chmod -R 755 bootstrap/cache/
chmod -R 755 storage/
```

## ❌ Erreur courante: "No output directory found after build"

Cette erreur signifie:
- ✗ Hostinger ne trouve pas `public/build/` après le build
- ✗ Le répertoire racine ne pointe pas vers `public/`
- ✗ Node.js n'est pas disponible sur le serveur

**Solution:**
1. Vérifiez que `public/` est le document root dans cPanel
2. Vérifiez que Node.js est installé: `node -v`
3. Exécutez manuellement: `npm run build`
4. Vérifiez les permissions sur `public/build/`

## 📝 Notes supplémentaires

- Les dépendances générées par Wayfinder (`/resources/js/routes`) **doivent être** incluses dans le git
- Utilisez le fichier `.cpanel.yml` pour automatiser le déploiement
- Vérifiez les logs en `/home/username/access-logs/` pour diagnostiquer les problèmes

