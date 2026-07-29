---
name: install-wordpress-elementor
description: Installe de zéro un site WordPress local complet en **stack Elementor**, avec DDEV et OrbStack — WordPress en français, Elementor, thème enfant Hello Elementor versionné, pages Accueil/Blog/Contact avec menu classique, extensions Yoast SEO / Complianz / WPS Hide Login, ménage complet des contenus par défaut, et dépôt GitHub privé. Utilise ce skill dès que l'utilisateur veut créer, installer, monter ou démarrer un nouveau site WordPress en local — « installe un WordPress », « nouveau site WP », « monte-moi un site local », « on démarre un nouveau projet client » — même s'il ne mentionne explicitement ni DDEV, ni Elementor, ni GitHub. C'est le skill par défaut pour un nouveau site. En revanche, si l'utilisateur demande explicitement du WordPress natif, du FSE, un thème de blocs ou l'éditeur de site (Full Site Editing), ce n'est pas ce skill : Elementor et l'édition de site native sont deux approches concurrentes qu'on ne mélange pas.
---

# Installation d'un site WordPress local — stack Elementor

Monte un site WordPress complet et propre dans le dossier courant : conteneurs DDEV sur OrbStack, stack Elementor, thème enfant versionné.

Ce skill couvre la voie **Elementor** : constructeur de pages, thème enfant Hello Elementor, menus classiques. Pour un site en édition native (FSE, thème de blocs, `theme.json`), la démarche est différente et ce skill ne s'applique pas.

Le skill est **autonome** : il ne demande que confirmation du nom du projet, puis déroule jusqu'au bout. La seule pause obligatoire est la création du dépôt GitHub, qui est une action externe et publique-par-conséquence — elle se demande toujours.

## Principe directeur : vérifier le résultat, pas le code de retour

Le piège central de cette install est qu'**une commande peut réussir tout en produisant un résultat faux**. Vu en conditions réelles : la page Blog répondait `HTTP 200` alors qu'elle servait en réalité la page d'accueil. Le code de retour ne prouve rien.

Vérifie donc toujours le contenu réel — le `<title>` servi, la valeur d'une option, la liste effective des extensions — et pas le simple succès de la commande. Chaque étape ci-dessous a sa vérification associée ; elles ne sont pas décoratives.

## Étape 0 — Prérequis et garde-fous

```bash
which ddev docker gh git
docker context ls | grep -q 'orbstack' && echo "OrbStack OK"
gh auth status
ls -A .
```

Attendu : `ddev`, `docker`, `git` présents, contexte Docker actif sur OrbStack, `gh` authentifié.

Si `gh` n'est pas authentifié, ce n'est pas bloquant : continue et signale-le au moment de l'étape Git.

**Le dossier doit être vide.** S'il contient déjà `wp-config.php` ou `.ddev/`, arrête-toi et demande à l'utilisateur ce qu'il veut faire — écraser une install existante détruirait sa base et son contenu.

## Étape 1 — Nom du projet

Déduis-le du dossier courant, en le normalisant : minuscules, lettres/chiffres/tirets uniquement.

Trois variables en découlent, et la distinction compte :

| Variable | Exemple | Usage |
|---|---|---|
| `PROJECT_SLUG` | `mon-site` | nom DDEV, text domain, handle CSS |
| `PROJECT_NAME` | `Mon Site` | titre du site, nom affiché du thème |
| `PROJECT_PREFIX` | `mon_site` | noms de fonctions et constantes PHP |

`PROJECT_PREFIX` est le slug avec les tirets remplacés par des underscores. C'est indispensable : **les tirets sont interdits dans les noms de fonctions PHP**, et un thème contenant `function mon-site_enqueue_styles()` provoque une erreur fatale dès son activation.

Annonce le nom retenu à l'utilisateur, puis enchaîne sans attendre de réponse.

## Étape 2 — Configurer et démarrer DDEV

```bash
ddev config --project-type=wordpress --project-name="$PROJECT_SLUG" \
  --docroot=. --php-version=8.3 --database=mariadb:10.11
ddev start -y
```

`--docroot=.` installe WordPress à la racine du dossier plutôt que dans un sous-dossier.

**Récupère ensuite l'URL réelle — ne la reconstruis jamais à la main :**

```bash
SITE_URL=$(ddev describe -j 2>/dev/null | python3 -c "import sys,json;print(json.load(sys.stdin)['raw']['primary_url'])")
echo "$SITE_URL"
```

C'est important : si les ports 80/443 de la machine sont déjà pris (typiquement par un nginx natif, Herd ou Valet), DDEV bascule automatiquement sur des ports comme 33000/33001 et l'URL contient alors un port. Une URL supposée serait fausse, et WordPress enregistrerait des liens cassés partout dans la base.

Si un basculement de port a eu lieu, identifie le coupable pour pouvoir l'expliquer, sans rien arrêter de toi-même — couper ce serveur casserait peut-être d'autres sites en cours :

```bash
lsof -nP -iTCP:80 -sTCP:LISTEN | head -3
```

## Étape 3 — Installer WordPress

```bash
ddev wp core download --locale=fr_FR
```

DDEV génère `wp-config.php` automatiquement, avec les identifiants du conteneur. Ne le crée pas à la main.

Mot de passe admin : génère-le aléatoirement, ne l'invente pas.

```bash
ADMIN_PASS=$(LC_ALL=C tr -dc 'A-Za-z0-9!@#%^*_-' </dev/urandom | head -c 20)
echo "$ADMIN_PASS" > .ddev/.admin-pass
ddev wp core install --url="$SITE_URL" --title="$PROJECT_NAME" \
  --admin_user='admin' --admin_password="$ADMIN_PASS" \
  --admin_email="$(git config --global user.email)" --skip-email
```

L'`--url` doit être celle récupérée à l'étape 2, port compris.

## Étape 4 — Permaliens

À faire **avant** de créer les pages :

```bash
ddev wp rewrite structure '/%postname%/' --hard
ddev wp rewrite flush --hard
```

Ce n'est pas un réglage cosmétique. En permaliens « simples » (le défaut), WordPress ne génère aucune règle de réécriture : `/blog/` ne renvoie pas d'erreur, il sert **silencieusement la page d'accueil**. La page Blog n'est alors joignable que via `?page_id=N`. C'est exactement le genre de bug qui coûte une heure parce que tout semble fonctionner.

## Étape 5 — Extensions

```bash
ddev wp plugin install elementor wordpress-seo complianz-gdpr wps-hide-login --activate
```

Slugs vérifiés sur le dépôt officiel : `wordpress-seo` est Yoast SEO, `complianz-gdpr` est Complianz (RGPD), `wps-hide-login` masque l'URL de connexion.

**Sur le bruit des commandes WP-CLI :** sous PHP 8.3, WP-CLI crache des dizaines de lignes `Deprecated:` venant de sa librairie Symfony interne. C'est inoffensif et sans effet sur le site. Ajoute `2>/dev/null` pour le filtrer — mais **ne tronque pas la sortie avec `tail -n`**, car la ligne de résultat se trouve à la fin et tu masquerais justement l'information utile.

**WPS Hide Login mérite une attention particulière** : une fois actif, `/wp-admin` et `/wp-login.php` ne répondent plus. Fixe l'URL explicitement pour qu'elle soit prévisible, puis relis-la depuis la base :

```bash
ddev wp option update whl_page 'connexion'
ddev wp option get whl_page
```

Vérifie ensuite que la connexion est réellement possible — **en suivant les redirections et en cherchant le formulaire**, pas en regardant le code HTTP :

```bash
curl -sL "$SITE_URL/connexion/" | grep -c 'id="loginform"'   # doit valoir 1
curl -s -o /dev/null -w "%{redirect_url}\n" "$SITE_URL/wp-admin/"  # doit pointer vers /404/
```

Sans `-L`, `/connexion` renvoie un 302 — une simple redirection canonique vers `/connexion/` avec le slash final — et on croit à tort s'être verrouillé dehors. L'URL à communiquer est celle **avec** le slash final.

Cette URL doit figurer en évidence dans le rapport final. Sans elle, l'utilisateur ne peut plus se connecter à son propre site.

## Étape 6 — Thème parent et thème enfant

```bash
ddev wp theme install hello-elementor
mkdir -p wp-content/themes/hello-elementor-child
```

Copie les trois gabarits depuis `assets/` du skill, puis substitue les variables :

```bash
SKILL_ASSETS="$HOME/.claude/skills/install-wordpress-elementor/assets"
cp "$SKILL_ASSETS/style.css"     wp-content/themes/hello-elementor-child/style.css
cp "$SKILL_ASSETS/functions.php" wp-content/themes/hello-elementor-child/functions.php
cp "$SKILL_ASSETS/README.md"     wp-content/themes/hello-elementor-child/README.md
cp "$SKILL_ASSETS/gitignore"     wp-content/themes/hello-elementor-child/.gitignore
```

Remplace dans les trois fichiers texte : `{{PROJECT_NAME}}`, `{{PROJECT_SLUG}}`, `{{PROJECT_PREFIX}}`, `{{PROJECT_PREFIX_UPPER}}`, `{{AUTHOR}}` et `{{AUTHOR_URI}}` (depuis `git config`), `{{THEME_URI}}` et `{{CLONE_URL}}` (URL GitHub prévue, ou chaîne vide si pas de dépôt).

Les gabarits contiennent déjà la subtilité importante : le CSS enfant déclare sa dépendance au style du parent **conditionnellement**, via `wp_style_is()`. Hello Elementor conditionne ce handle à un filtre qu'Elementor permet de désactiver ; une dépendance en dur empêcherait alors le CSS enfant de se charger, sans le moindre message d'erreur. Ne réécris pas ces fichiers de mémoire — c'est précisément le détail qu'on rate.

```bash
ddev wp theme activate hello-elementor-child
```

**Vérifie que le thème enfant est réellement fonctionnel**, et pas seulement activé :

```bash
ddev wp eval '$t = wp_get_theme(); echo $t->get("Name") . " | parent: " . ($t->parent() ? $t->parent()->get("Name") : "AUCUN") . "\n";'
curl -s "$SITE_URL" | grep -o 'hello-elementor-child/style.css[^"]*'
```

Le parent doit être `Hello Elementor`, et la feuille de styles doit apparaître dans le HTML. Si `parent` vaut `AUCUN`, l'en-tête `Template:` du `style.css` est incorrect.

## Étape 7 — Pages, menu et réglages de lecture

Crée les trois pages avec `--porcelain`, qui ne retourne que l'ID — plus fiable que de le lire dans une phrase de confirmation :

```bash
ID_HOME=$(ddev wp post create --post_type=page --post_title='Accueil' --post_name='accueil' --post_status=publish --porcelain)
ID_BLOG=$(ddev wp post create --post_type=page --post_title='Blog' --post_name='blog' --post_status=publish --porcelain)
ID_CONTACT=$(ddev wp post create --post_type=page --post_title='Contact' --post_name='contact' --post_status=publish --porcelain)

ddev wp option update show_on_front page
ddev wp option update page_on_front "$ID_HOME"
ddev wp option update page_for_posts "$ID_BLOG"
```

Menu principal — Hello Elementor déclare deux emplacements, `menu-1` (En-tête) et `menu-2` (Pied de page) :

```bash
ddev wp menu create "Menu principal"
ddev wp menu item add-post menu-principal "$ID_HOME"
ddev wp menu item add-post menu-principal "$ID_BLOG"
ddev wp menu item add-post menu-principal "$ID_CONTACT"
ddev wp menu location assign menu-principal menu-1
```

Confirme l'emplacement plutôt que de le supposer — l'assignation à un emplacement inexistant échoue en silence :

```bash
ddev wp menu location list
```

## Étape 8 — Ménage

Supprime les contenus de démonstration, les extensions par défaut et les anciens thèmes.

**Nomme toujours explicitement ce que tu supprimes.** N'utilise pas `--all` : une commande trop large emporterait le thème actif ou du contenu créé à l'étape précédente.

```bash
ddev wp post list --post_type=any --post_status=any --fields=ID,post_type,post_title,post_status
```

Inspecte cette liste avant d'agir, puis supprime définitivement, par ID, les contenus par défaut : « Bonjour tout le monde ! », « Page d'exemple », « Politique de confidentialité », et le brouillon « Hello Theme #N » créé par le thème. Vérifie au passage que les pages Accueil, Blog et Contact n'y figurent pas.

```bash
ddev wp post delete <IDS> --force
ddev wp comment delete <ID> --force
ddev wp plugin delete hello akismet
```

Une erreur « Invalid comment ID » sur le commentaire par défaut est normale : WordPress l'a déjà supprimé avec l'article auquel il était rattaché.

Thèmes : conserve le thème enfant, Hello Elementor, et **le plus récent des thèmes Twenty** comme filet de sécurité — si Elementor ou le thème enfant casse un jour, WordPress a besoin d'un thème valide vers lequel basculer. Supprime les autres, nommément.

```bash
ddev wp theme list --fields=name,status,version
ddev wp theme delete twentytwentyfour twentytwentythree twentytwentytwo
```

Adapte cette liste à ce que `theme list` retourne réellement : les thèmes livrés changent à chaque version de WordPress.

## Étape 9 — Vérification globale

Contrôle le contenu servi, pas les codes HTTP :

```bash
ddev wp eval 'echo "WP charge OK\n";'
for p in "" blog/ contact/; do printf "/%-9s -> " "$p"; curl -s "$SITE_URL/$p" | grep -o '<title>[^<]*</title>'; done
curl -s "$SITE_URL/" | grep -ci "fatal error\|parse error"          # doit valoir 0
curl -s "$SITE_URL/" | grep -o '>Accueil<\|>Blog<\|>Contact<' | sort -u   # le menu doit apparaître
curl -sL "$SITE_URL/connexion/" | grep -c 'id="loginform"'          # doit valoir 1
ddev wp plugin list --fields=name,status,version
ddev wp theme list --fields=name,status,version
```

Attendu : chaque page renvoie un titre contenant son propre nom, et les trois entrées de menu apparaissent dans le HTML du front.

Quelques repères pour lire ces résultats :

- **Si `/blog/` renvoie le titre du site seul**, les permaliens ne sont pas actifs — reprends l'étape 4.
- **Si les entrées de menu n'apparaissent pas** alors que `wp menu location assign` a réussi, l'emplacement visé n'existe pas. Vérifie avec `ddev wp menu location list`.
- **Les titres ont la forme « Accueil - Mon Site »** et non « Mon Site » seul : Yoast prend la main sur leur génération dès son activation. C'est normal, et c'est même le signe qu'il fonctionne.

## Étape 10 — Versionner le thème

Le dépôt couvre **le dossier du thème enfant uniquement**, jamais la racine du site. Versionner tout WordPress mettrait `wp-config.php` sous Git — donc des identifiants de base de données — en plus du core et des médias. Seul le code écrit à la main mérite d'être suivi.

```bash
cd wp-content/themes/hello-elementor-child
git init -b main
git add -A
git status --short
```

Vérifie la liste avant de committer : elle doit contenir `style.css`, `functions.php`, `README.md` et `.gitignore`, et rien d'autre.

```bash
git commit -m "Initial commit: thème enfant Hello Elementor"
```

### Dépôt GitHub — demander avant

**Demande toujours confirmation avant de créer le dépôt distant.** C'est la seule étape qui envoie du code hors de la machine de l'utilisateur ; elle ne doit jamais être implicite. Propose `<projet>-theme` comme nom, en privé, et attends une réponse claire.

```bash
gh repo create "$PROJECT_SLUG-theme" --private --source=. --remote=origin --push \
  --description "Thème enfant Hello Elementor pour le site $PROJECT_NAME"
```

Confirme ensuite la visibilité auprès de l'API plutôt que de la déduire du succès de la commande — se tromper ici rendrait du code client public :

```bash
gh repo view --json name,visibility,isPrivate,url
```

## Étape 11 — Rapport final

Présente un récapitulatif compact :

- **URL du site** (celle récupérée à l'étape 2, port compris)
- **URL de connexion** — `$SITE_URL/connexion`, en insistant sur le fait que `/wp-admin` ne répond plus à cause de WPS Hide Login
- **Identifiants** : `admin` + le mot de passe généré, avec sa localisation dans `.ddev/.admin-pass`
- **Stack** : versions de WordPress, PHP, base de données, thème, extensions
- **Dépôt** : URL GitHub et visibilité, ou mention qu'il est resté local
- **Ports** : si DDEV a basculé sur des ports non standard, explique pourquoi et comment y remédier (arrêter le serveur natif, puis `ddev restart`)

Termine par les commandes utiles :

```bash
ddev wp <commande>   # WP-CLI dans le conteneur
ddev ssh             # shell du conteneur
ddev mysql           # client base de données
ddev describe        # URLs et état
ddev stop | start
```

Précise que `ddev wp` est obligatoire : un WP-CLI installé sur la machine hôte n'a pas accès à la base, qui vit dans le conteneur.

## Pièges connus

| Symptôme | Cause | Correctif |
|---|---|---|
| `/blog/` sert la page d'accueil, en HTTP 200 | Permaliens en mode simple | Étape 4 |
| Erreur fatale à l'activation du thème | Tiret dans un nom de fonction PHP | Utiliser `PROJECT_PREFIX`, pas `PROJECT_SLUG` |
| CSS du thème enfant absent du HTML | Dépendance en dur à un handle parent non enqueué | Le `wp_style_is()` des gabarits |
| Impossible de se connecter | WPS Hide Login actif | `$SITE_URL/connexion/` |
| La page de connexion semble cassée (302) | Redirection canonique vers le slash final | `curl -sL`, chercher `id="loginform"` |
| Sortie noyée sous des `Deprecated:` | WP-CLI + PHP 8.3 | `2>/dev/null`, sans `tail` |
| L'URL du site ne répond pas | Ports 80/443 pris, DDEV a basculé | Lire `primary_url`, étape 2 |
| Le menu n'apparaît pas | Emplacement inexistant | `menu-1` chez Hello Elementor |
