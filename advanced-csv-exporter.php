<?php
/**
 * Plugin Name: Advanced CSV Exporter
 * Plugin URI:  https://www.dizitalizeo.com
 * Description: Exportez facilement vos utilisateurs, produits, catégories et commandes en CSV avec des filtres.
 * Version:     1.0.0
 * Author:      RAKOTONARIVO Jean Claude
 * Author URI:  https://e-vrotra.g
 * Text Domain: advanced-csv-exporter
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 6.0
 * WC tested up to: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'ADV_CSV_EXPORTER_VERSION', '1.0.0' );
define( 'ADV_CSV_EXPORTER_PATH', plugin_dir_path( __FILE__ ) );
define( 'ADV_CSV_EXPORTER_URL', plugin_dir_url( __FILE__ ) );

/**
 * Charge les fichiers requis.
 */
require_once ADV_CSV_EXPORTER_PATH . 'includes/class-exporter-base.php';
require_once ADV_CSV_EXPORTER_PATH . 'includes/class-admin.php';
require_once ADV_CSV_EXPORTER_PATH . 'includes/class-users-exporter.php';
require_once ADV_CSV_EXPORTER_PATH . 'includes/class-products-exporter.php';
require_once ADV_CSV_EXPORTER_PATH . 'includes/class-categories-exporter.php';

// Si WooCommerce est actif, charger l'export des commandes.
if ( class_exists( 'WooCommerce' ) ) {
    require_once ADV_CSV_EXPORTER_PATH . 'includes/class-orders-exporter.php';
}

/**
 * Initialisation du plugin.
 */
function adv_csv_exporter_init() {
    load_plugin_textdomain( 'advanced-csv-exporter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    // Instancier la classe admin.
    new Adv_CSV_Exporter_Admin();
}
add_action( 'plugins_loaded', 'adv_csv_exporter_init' );
