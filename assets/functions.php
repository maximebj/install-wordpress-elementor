<?php
/**
 * {{PROJECT_NAME}} — thème enfant de Hello Elementor.
 *
 * @package {{PROJECT_NAME}}
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Accès direct interdit.
}

define( '{{PROJECT_PREFIX_UPPER}}_VERSION', '1.0.0' );

/**
 * Charge la feuille de styles du thème enfant après celle du parent.
 *
 * La dépendance au style du parent garantit que le CSS enfant est chargé en
 * dernier, et peut donc le surcharger. Elle n'est déclarée que si le handle
 * existe réellement : Hello Elementor conditionne son chargement au filtre
 * « hello_elementor_enqueue_theme_style », qu'Elementor permet de désactiver
 * depuis ses réglages. Une dépendance non satisfaite empêcherait WordPress de
 * charger ce fichier, silencieusement et sans aucune erreur.
 */
function {{PROJECT_PREFIX}}_enqueue_styles() {
	$deps = wp_style_is( 'hello-elementor-theme-style', 'enqueued' )
		? array( 'hello-elementor-theme-style' )
		: array();

	wp_enqueue_style(
		'{{PROJECT_SLUG}}-style',
		get_stylesheet_directory_uri() . '/style.css',
		$deps,
		{{PROJECT_PREFIX_UPPER}}_VERSION
	);
}
add_action( 'wp_enqueue_scripts', '{{PROJECT_PREFIX}}_enqueue_styles', 20 );
