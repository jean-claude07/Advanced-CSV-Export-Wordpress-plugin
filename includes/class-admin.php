<?php
/**
 * Classe principale pour l'administration.
 */

defined( 'ABSPATH' ) || exit;

class Adv_CSV_Exporter_Admin {

    /**
     * Liste des exporteurs enregistrés.
     *
     * @var array
     */
    private $exporters = array();

    /**
     * Constructor.
     */
    public function __construct() {
        $this->register_exporters();
        $this->init_hooks();
    }

    /**
     * Enregistre les exporteurs.
     */
    private function register_exporters() {
        $this->exporters[] = new Adv_CSV_Exporter_Users();
        $this->exporters[] = new Adv_CSV_Exporter_Products();
        $this->exporters[] = new Adv_CSV_Exporter_Categories();

        if ( class_exists( 'WooCommerce' ) ) {
            $this->exporters[] = new Adv_CSV_Exporter_Orders();
        }
    }

    /**
     * Initialise les hooks.
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

        // Ajouter les boutons via les exporteurs.
        foreach ( $this->exporters as $exporter ) {
            $exporter->add_export_button();
        }
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'adv_csv_options', 'adv_csv_delimiter', array(
            'type' => 'string',
            'sanitize_callback' => array( $this, 'sanitize_delimiter' ),
            'default' => ';',
        ) );

        register_setting( 'adv_csv_options', 'adv_csv_streaming', array(
            'type' => 'string',
            'sanitize_callback' => array( $this, 'sanitize_streaming' ),
            'default' => 'yes',
        ) );
    }

    public function sanitize_delimiter( $val ) {
        $allowed = array( ',', ';', "\t" );
        if ( in_array( $val, $allowed, true ) ) {
            return $val;
        }
        return ';';
    }

    public function sanitize_streaming( $val ) {
        return $val === 'yes' ? 'yes' : 'no';
    }

    /**
     * Ajoute une page de paramètres (optionnel).
     */
    public function add_admin_menu() {
        add_options_page(
            __( 'Export CSV Avancé', 'advanced-csv-exporter' ),
            __( 'Export CSV', 'advanced-csv-exporter' ),
            'manage_options',
            'advanced-csv-exporter',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Charge les assets si nécessaire.
     *
     * @param string $hook
     */
    public function enqueue_scripts( $hook ) {
        // Charger sur les pages concernées.
        $screens = array(
            'users.php',
            'edit.php', // pour produits et catégories ?
            'edit-tags.php',
            'woocommerce_page_wc-orders', // pour les commandes HPOS
            'edit-shop_order', // ancienne page commandes
        );
        if ( in_array( $hook, $screens, true ) ) {
            wp_enqueue_style( 'adv-csv-exporter-admin', ADV_CSV_EXPORTER_URL . 'assets/css/admin.css', array(), ADV_CSV_EXPORTER_VERSION );
        }
    }

    /**
     * Page de réglages (simple).
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Export CSV Avancé', 'advanced-csv-exporter' ); ?></h1>
            <p><?php esc_html_e( 'Ce plugin ajoute des boutons d\'export CSV sur les pages de listes : utilisateurs, produits, catégories, commandes.', 'advanced-csv-exporter' ); ?></p>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'adv_csv_options' );
                do_settings_sections( 'adv_csv_options' );
                $delimiter = get_option( 'adv_csv_delimiter', ';' );
                $streaming = get_option( 'adv_csv_streaming', 'yes' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="adv_csv_delimiter"><?php esc_html_e( 'Séparateur CSV', 'advanced-csv-exporter' ); ?></label></th>
                        <td>
                            <select id="adv_csv_delimiter" name="adv_csv_delimiter">
                                <option value="," <?php selected( $delimiter, ',' ); ?>><?php echo esc_html( 'Virgule (,)' ); ?></option>
                                <option value=";" <?php selected( $delimiter, ';' ); ?>><?php echo esc_html( 'Point-virgule (;)' ); ?></option>
                                <option value="\t" <?php selected( $delimiter, "\t" ); ?>><?php echo esc_html( 'Tabulation (\t)' ); ?></option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Streaming', 'advanced-csv-exporter' ); ?></th>
                        <td>
                            <label><input type="radio" name="adv_csv_streaming" value="yes" <?php checked( $streaming, 'yes' ); ?> /> <?php esc_html_e( 'Activer l\'export en streaming (recommandé pour gros jeux de données)', 'advanced-csv-exporter' ); ?></label><br />
                            <label><input type="radio" name="adv_csv_streaming" value="no" <?php checked( $streaming, 'no' ); ?> /> <?php esc_html_e( 'Désactiver (génération en mémoire)', 'advanced-csv-exporter' ); ?></label>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}