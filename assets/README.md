# {{PROJECT_NAME}} — thème enfant Hello Elementor

Thème enfant de [Hello Elementor](https://wordpress.org/themes/hello-elementor/) pour le site {{PROJECT_NAME}}.

## Prérequis

- WordPress
- Thème parent **Hello Elementor** installé
- Extension **Elementor**

## Installation

Cloner ce dépôt dans le dossier des thèmes, sous le nom `hello-elementor-child` :

```bash
git clone {{CLONE_URL}} wp-content/themes/hello-elementor-child
wp theme activate hello-elementor-child
```

Le nom du dossier importe : WordPress s'en sert comme identifiant du thème.

## Structure

| Fichier | Rôle |
|---|---|
| `style.css` | En-tête du thème (dont `Template:` qui déclare le parent) et styles personnalisés |
| `functions.php` | Chargement de la feuille de styles enfant après celle du parent |

## Développement

Les styles personnalisés vont dans `style.css`, sous l'en-tête.

Après modification du CSS, incrémenter `{{PROJECT_PREFIX_UPPER}}_VERSION` dans `functions.php` : cette constante sert de numéro de version au fichier chargé et force le rafraîchissement du cache navigateur.

## Environnement local

Le site tourne sous DDEV. Commandes utiles depuis la racine du projet :

```bash
ddev start
ddev wp theme list
ddev ssh
```
