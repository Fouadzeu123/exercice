# Configuration de déploiement pour Hostinger

## ⚠️ PROBLÈME COURANT: "No output directory found after build"

**Cause:** Le système de déploiement Hostinger ne reconnaît pas où trouver les fichiers compilés.

**Solution complète:**

### Étape 1: Vérifier le Document Root dans cPanel
1. Connectez-vous à **cPanel**
2. Allez à **File Manager** ou **Public_html**
3. Assurez-vous que le document root pointe vers `/home/username/public_html` (ou `/home/username/armicm.com/public`)
4. **IMPORTANT:** Si vous avez clôné le projet dans un sous-dossier, vous DEVEZ créer un lien symbolique ou modifier le document root

### Étape 2: Configuration requise sur Hostinger

**Option A: Lien symbolique (Recommandé)**
```bash
# Via SSH:
cd ~/public_html
ln -s ~/projects/ARM\ holding/public ./arm-holding
# Accédez via: https://armicm.com/arm-holding
```

**Option B: Déplacer le dossier public**
```bash
# Via SSH:
cd ~/public_html
rsync -av ~/projects/ARM\ holding/public/* ./
```

**Option C: Rediriger le document root (cPanel)**
- Allez à **Addon Domains** ou **Main Domain**
- Changez le Document Root vers: `/home/username/projects/ARM\ holding/public`

### Étape 3: Vérifier la configuration

Après le déploiement, exécutez le diagnostic:
```bash
bash diagnose-deployment.sh
```

Ou vérifiez manuellement:
```bash
# Les fichiers doivent exister:
ls public/build/manifest.json
ls public/index.php
```

### Étape 4: Fichiers de configuration

Nous avons ajouté plusieurs fichiers pour aider au déploiement:

- ✅ `.cpanel.yml` - Configuration cPanel
- ✅ `.buildrc.json` - Configuration générale de build
- ✅ `vercel.json` - Configuration Vercel (au cas où)
- ✅ `netlify.toml` - Configuration Netlify (au cas où)

### Étape 5: Vérification finale

1. **Build local réussi:**
   ```bash
   npm run build
   # Output: ✓ built in 7.80s
   # Fichiers dans: public/build/
   ```

2. **Permissions correctes:**
   ```bash
   chmod -R 755 public/build/
   chmod -R 755 storage/
   chmod -R 755 bootstrap/cache/
   ```

3. **Test final via HTTPS:**
   ```
   https://armicm.com/
   # Devrait afficher votre application
   ```

---

## Configuration complète sur Hostinger

### Étape 1: Accès SSH
```bash
ssh username@armicm.com
cd public_html
```

### Étape 2: Clone du projet
```bash
# Si pas déjà fait:
git clone <votre-repo> arm-holding
cd arm-holding
```

### Étape 3: Installation et build
```bash
npm install
npm run build
```

### Étape 4: Configuration du document root
```bash
# Vérifier:
ls -la public/index.php
ls -la public/build/manifest.json
```

### Étape 5: Si tout est en `/home/username/public_html`
```bash
# Déplacer les fichiers de public vers public_html
cd ~/public_html
rsync -av ~/arm-holding/public/* ./
rsync -av ~/arm-holding/storage ./
rsync -av ~/arm-holding/bootstrap ./
rsync -av ~/arm-holding/app ./
# ... (tous les fichiers sauf node_modules et .git)
```

---

## ❌ Erreurs couantes et solutions

| Erreur | Cause | Solution |
|--------|-------|----------|
| "No output directory found" | Document root mal configuré | Vérifier cPanel File Manager |
| Node.js non trouvé | Node.js non installé | Installer via cPanel ou softaculous |
| Permission denied | Permissions incorrectes | `chmod -R 755 public/` |
| manifest.json manquant | Build n'a pas réussi | Vérifier les logs: `npm run build` |
| Fichiers non trouvés | Chemin incorrect | Vérifier: `pwd` et `ls public/` |

---

## 📝 Notes essentielles

1. **Toujours utiliser la structure Laravel:**
   ```
   /home/username/public_html/
   ├── index.php (Laravel)
   ├── .htaccess
   ├── app/
   ├── bootstrap/
   ├── config/
   ├── public/build/  (Assets compilés)
   ├── resources/
   ├── routes/
   ├── storage/
   └── vendor/
   ```

2. **Document root DOIT être `public/`** car c'est là que Laravel cherche les fichiers

3. **Les fichiers compilés Vite sont dans `public/build/`** et sont référencés automatiquement

4. **Après chaque modification, relancer:**
   ```bash
   npm run build
   ```

