<?php
/**
 * Classe abstraite de base pour les exporteurs.
 */

defined( 'ABSPATH' ) || exit;

abstract class Adv_CSV_Exporter_Base {

    /**
     * Identifiant unique de l'exporteur (slug).
     *
     * @var string
     */
    protected $id = '';

    /**
     * Titre affiché.
     *
     * @var string
     */
    protected $title = '';

    /**
     * Capacité requise pour exporter.
     *
     * @var string
     */
    protected $capability = 'manage_options';

    /**
     * Constructor.
     */
    public function __construct() {
        if ( empty( $this->id ) || empty( $this->title ) ) {
            wp_die( 'L\'exporteur doit définir un ID et un titre.' );
        }

        add_action( 'admin_init', array( $this, 'handle_export' ) );
    }

    /**
     * Ajoute le bouton d'export sur la page de liste.
     * À surcharger.
     */
    abstract public function add_export_button();

    /**
     * Génère le contenu du CSV.
     *
     * @param array $filters Filtres appliqués.
     * @return string
     */
    abstract protected function generate_csv( $filters );

    /**
     * Récupère les filtres depuis la requête.
     *
     * @return array
     */
    protected function get_filters() {
        // Sanitize GET inputs (shallow). Preserve arrays by sanitizing each element.
        $raw = wp_unslash( $_GET );
        $sanitized = array();
        foreach ( $raw as $key => $value ) {
            if ( is_array( $value ) ) {
                $sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
            } else {
                $sanitized[ $key ] = sanitize_text_field( $value );
            }
        }
        return $sanitized;
    }

    /**
     * Gère la requête d'export.
     */
    public function handle_export() {
        if ( ! isset( $_GET['action'] ) || 'export_csv' !== $_GET['action'] ) {
            return;
        }
        if ( ! isset( $_GET['exporter_id'] ) ) {
            return;
        }
        $exporter_id = sanitize_text_field( wp_unslash( $_GET['exporter_id'] ) );
        if ( $this->id !== $exporter_id ) {
            return;
        }
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['_wpnonce'] ), 'adv_csv_export_' . $this->id ) ) {
            wp_die( 'Action non autorisée.' );
        }
        if ( ! current_user_can( $this->capability ) ) {
            wp_die( 'Vous n\'avez pas les droits pour exporter.' );
        }

        $filters = $this->get_filters();

        // Determine delimiter and streaming option from settings
        $delimiter = get_option( 'adv_csv_delimiter', ';' );
        $streaming = get_option( 'adv_csv_streaming', 'yes' ) === 'yes';

        $filename = sanitize_file_name( sanitize_title( $this->title ) . '-' . date( 'Y-m-d' ) . '.csv' );

        // Prevent caching and force download
        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        // BOM for Excel compatibility
        echo "\xEF\xBB\xBF";

        // If exporter implements streaming generation and streaming is enabled, use it.
        if ( $streaming && method_exists( $this, 'generate_csv_stream' ) ) {
            $out = fopen( 'php://output', 'w' );
            if ( false === $out ) {
                wp_die( 'Impossible d\'ouvrir le flux de sortie.' );
            }
            // exporters should write header row themselves via fputcsv, but we allow them to receive $out
            $this->generate_csv_stream( $filters, $out, $delimiter );
            fclose( $out );
            exit;
        }

        // Fallback to legacy generation (string)
        $csv_content = $this->generate_csv( $filters );
        echo $csv_content;
        exit;
    }

    /**
     * Convertit un tableau en ligne CSV.
     *
     * @param array  $row   Tableau associatif.
     * @param string $delimiter Délimiteur.
     * @return string
     */
    protected function array_to_csv_line( $row, $delimiter = ';' ) {
        $cells = array();
        foreach ( $row as $value ) {
            if ( is_array( $value ) ) {
                $value = maybe_serialize( $value );
            }
            // Cast to string safely
            if ( is_null( $value ) ) {
                $value = '';
            } else {
                $value = (string) $value;
            }

            $needs_quotes = false;
            if ( strpos( $value, $delimiter ) !== false || strpos( $value, '"' ) !== false || strpos( $value, "\n" ) !== false || strpos( $value, "\r" ) !== false ) {
                $needs_quotes = true;
            }

            $escaped = str_replace( '"', '""', $value );
            if ( $needs_quotes ) {
                $escaped = '"' . $escaped . '"';
            }
            $cells[] = $escaped;
        }
        return implode( $delimiter, $cells ) . "\n";
    }
}