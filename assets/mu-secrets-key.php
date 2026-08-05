<?php
/**
 * Plugin Name: Clé de chiffrement des secrets
 * Description: Définit WP_SECRETS_KEY pour que les clés d'API des connecteurs IA survivent aux redémarrages de DDEV.
 * Version:     1.0.0
 *
 * Pourquoi ce fichier existe :
 *
 * Sans WP_SECRETS_KEY, WordPress dérive la clé de chiffrement des secrets de
 * LOGGED_IN_KEY . LOGGED_IN_SALT, définis dans wp-config.php. Ce fichier étant
 * généré par DDEV, il peut être réécrit avec de nouveaux sels : toute clé
 * d'API enregistrée devient alors indéchiffrable, et le site tombe en erreur
 * fatale au chargement — pas seulement en avertissement.
 *
 * Définir WP_SECRETS_KEY rend le chiffrement indépendant des sels. Le fichier
 * est une extension « must-use » plutôt qu'un ajout à wp-config.php, car DDEV
 * regénère ce dernier intégralement : retirer son marqueur « #ddev-generated »
 * ne suffit pas à l'en empêcher.
 *
 * Si vous changez la valeur ci-dessous, reportez l'ancienne dans
 * WP_SECRETS_KEY_PREVIOUS : WordPress rechiffrera les secrets automatiquement
 * au prochain chargement, puis vous pourrez retirer l'ancienne.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'WP_SECRETS_KEY' ) ) {
	define( 'WP_SECRETS_KEY', '{{SECRETS_KEY}}' );
}
