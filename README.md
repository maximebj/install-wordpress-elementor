# install-wordpress-elementor

Skill [Claude Code](https://claude.com/claude-code) qui installe un site WordPress local complet, prêt à travailler, en une seule demande.

L'installation manuelle laisse toujours traîner quelque chose : un contenu de démonstration, des permaliens jamais réglés, un thème enfant bricolé à la main. Le skill déroule la même séquence vérifiée à chaque fois, et contrôle le résultat obtenu plutôt que le simple succès des commandes.

## Ce que vous obtenez

| | |
|---|---|
| Environnement | DDEV (OrbStack sur macOS, WSL2 sur Windows), PHP 8.3, MariaDB 10.11 |
| WordPress | dernière version, en français, permaliens propres |
| Constructeur | Elementor + thème parent Hello Elementor |
| Thème | thème enfant prêt à personnaliser, versionné avec Git |
| Extensions | Yoast SEO, Complianz (RGPD), WPS Hide Login |
| Contenu | pages Accueil, Blog et Contact, avec menu principal |
| Outils | Adminer (base de données), Mailpit (e-mails) |
| Propreté | aucun contenu de démonstration, aucune extension inutile |

Le site est utilisable immédiatement : il ne reste qu'à ouvrir la page d'accueil dans Elementor.

## Prérequis

Trois outils, à installer une fois pour toutes : un moteur de conteneurs, [DDEV](https://docs.ddev.com/) qui orchestre l'environnement WordPress, et [Claude Code](https://code.claude.com/docs/en/setup) qui exécute le skill. Le [GitHub CLI](https://cli.github.com/) (`gh`) est facultatif : il ne sert qu'à créer le dépôt du thème.

### macOS

[OrbStack](https://orbstack.dev/) fait tourner les conteneurs — c'est une alternative à Docker Desktop, plus légère et plus rapide.

```bash
brew install --cask orbstack
brew install ddev/ddev/ddev gh
curl -fsSL https://claude.ai/install.sh | bash
```

### Windows

**Tout se passe dans WSL2**, le sous-système Linux de Windows. Ce n'est pas une préférence de confort, mais une contrainte technique : DDEV demande que les projets vivent dans le système de fichiers WSL2 plutôt que sur le disque `C:`, sous peine de lenteurs marquées et de problèmes de permissions. OrbStack, de son côté, n'existe pas sur Windows — c'est Docker qui tourne à l'intérieur de WSL2.

Dans PowerShell, installez WSL2 :

```powershell
wsl --install --no-distribution
wsl --update
wsl --install Ubuntu-26.04 --name DDEV
```

Installez ensuite DDEV avec son programme d'installation Windows, en suivant [la procédure officielle](https://docs.ddev.com/en/stable/users/install/ddev-installation/#windows) — choisissez l'option « Docker CE inside WSL2 », celle que DDEV recommande pour les performances.

**Basculez enfin dans un terminal WSL2** — plus PowerShell — pour installer Claude Code :

```bash
curl -fsSL https://claude.ai/install.sh | bash
```

Claude Code doit impérativement être installé et lancé **depuis WSL2**. Installé côté Windows, il écrirait le skill dans `C:\Users\...\.claude` alors que vos projets vivent dans WSL2 : il ne le trouverait jamais.

Une fois dans WSL2, vous êtes sous Linux. Tout le reste de ce README s'applique à l'identique, chemins `~/` compris.

### Vérifier

Depuis le terminal où vous travaillerez — celui de macOS, ou celui de WSL2 :

```bash
ddev version && docker version && claude --version
```

## Installation du skill

Clonez le dépôt dans le dossier des skills de Claude Code — sur Windows, depuis votre terminal WSL2 :

```bash
git clone https://github.com/maximebj/install-wordpress-elementor.git \
  ~/.claude/skills/install-wordpress-elementor
```

**Le nom du dossier doit être exactement `install-wordpress-elementor`.** Le skill y cherche ses gabarits de thème par ce chemin ; sous un autre nom, la création du thème enfant échoue.

Le skill est global : il fonctionne depuis n'importe quel dossier, pas seulement celui-ci.

## Utilisation

Créez un dossier vide, placez-vous dedans, et lancez Claude Code :

```bash
mkdir -p ~/Sites/mon-nouveau-site && cd ~/Sites/mon-nouveau-site
claude
```

Sur Windows, ce dossier doit se trouver dans votre répertoire personnel WSL2 (`~/`, soit `/home/votre-nom/`) et surtout pas dans `/mnt/c/`. Un projet posé sur le disque Windows fonctionne, mais devient très lent et provoque des erreurs de permissions.

Puis demandez simplement :

> installe un WordPress

Le nom du projet est déduit du dossier. Le skill déroule ensuite tout seul et ne s'interrompt qu'une fois, pour vous demander s'il doit créer un dépôt GitHub pour le thème — la seule étape qui envoie du code hors de votre machine.

Il termine par un récapitulatif : URL du site, identifiants, adresses d'Adminer et de Mailpit.

## Travailler sur le site

Toutes les commandes se lancent depuis le dossier du projet.

```bash
ddev wp plugin list   # WP-CLI, dans le conteneur
ddev adminer          # base de données, dans le navigateur
ddev launch -m        # Mailpit : e-mails envoyés par le site
ddev mysql            # base de données, en ligne de commande
ddev ssh              # shell du conteneur
ddev describe         # URLs et état des services
ddev stop | ddev start
```

`ddev wp` est obligatoire pour WP-CLI : un `wp` installé sur votre machine n'a pas accès à la base de données, qui vit dans le conteneur.

## Bon à savoir

**L'adresse de connexion n'est pas `/wp-admin`.** WPS Hide Login la masque. Elle devient `/connexion/` — le skill vous la rappelle à la fin. C'est la première chose qui surprend.

**Les e-mails ne partent pas vraiment.** Mailpit les intercepte tous. C'est voulu : un site de test ne doit jamais écrire à de vraies personnes. Consultez-les avec `ddev launch -m`.

**L'URL contient parfois un port** (`https://mon-site.ddev.site:33001`). Cela arrive quand un serveur web occupe déjà les ports 80 et 443 : sur macOS, souvent Herd, Valet ou un nginx installé via Homebrew ; sur Windows, IIS ou un autre service à l'écoute. Le site fonctionne tout à fait normalement ainsi. Pour retrouver une adresse sans port, arrêtez le serveur en question puis relancez `ddev restart`.

**Le dépôt Git ne couvre que le thème enfant**, jamais la racine du site. Versionner tout WordPress mettrait `wp-config.php` — donc les identifiants de la base — dans l'historique Git.

**Personnaliser le thème** : écrivez votre CSS dans `wp-content/themes/hello-elementor-child/style.css`, puis incrémentez la constante `_VERSION` dans `functions.php` du même dossier. Elle sert de numéro de version au fichier et force le navigateur à recharger vos styles au lieu de servir sa copie en cache.

## Et pour du WordPress natif ?

Ce skill couvre la voie **Elementor** : constructeur de pages, thème enfant, menus classiques.

Pour un site en édition native — thèmes de blocs, `theme.json`, Full Site Editing — la démarche est différente et ce skill ne s'applique pas. Les deux approches sont concurrentes et ne se mélangent pas sur un même site.

## Contenu du dépôt

```
SKILL.md      la procédure suivie par Claude Code
assets/       gabarits du thème enfant (style.css, functions.php, README, .gitignore)
```

Les gabarits contiennent des variables `{{...}}` que le skill remplace par le nom du projet au moment de l'installation.
