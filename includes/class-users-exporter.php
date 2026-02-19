<?php
/**
 * Export des utilisateurs.
 */

defined( 'ABSPATH' ) || exit;

class Adv_CSV_Exporter_Users extends Adv_CSV_Exporter_Base {

    protected $id = 'users';
    protected $title = 'Utilisateurs';
    protected $capability = 'list_users';

    /**
     * Ajoute le bouton sur la page des utilisateurs.
     */
    public function add_export_button() {
        add_action( 'restrict_manage_users', array( $this, 'render_button' ) );
    }

    /**
     * Affiche le bouton après les filtres.
     */
    public function render_button( $which ) {
        if ( 'top' !== $which ) {
            return;
        }

        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'action'      => 'export_csv',
                    'exporter_id' => $this->id,
                ),
                admin_url( 'users.php' )
            ),
            'adv_csv_export_' . $this->id
        );

        printf(
            '<a href="%s" class="button adv-csv-export-btn">%s</a>',
            esc_url( $url ),
            esc_html__( 'Exporter en CSV', 'advanced-csv-exporter' )
        );
    }

    /**
     * Génère le CSV.
     *
     * @param array $filters
     * @return string
     */
    protected function generate_csv( $filters ) {
        // Construire la requête utilisateur en fonction des filtres (rôle, etc.)
        $args = array(
            'fields' => 'all_with_meta',
            'number' => -1,
        );

        // Si un rôle est sélectionné dans le filtre.
        if ( ! empty( $filters['role'] ) ) {
            $args['role'] = sanitize_text_field( $filters['role'] );
        }

        $user_query = new WP_User_Query( $args );
        $users = $user_query->get_results();

        // En-têtes CSV.
        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Nom d\'utilisateur', 'advanced-csv-exporter' ),
            __( 'Email', 'advanced-csv-exporter' ),
            __( 'Rôle(s)', 'advanced-csv-exporter' ),
            __( 'Date d\'inscription', 'advanced-csv-exporter' ),
            __( 'Prénom', 'advanced-csv-exporter' ),
            __( 'Nom', 'advanced-csv-exporter' ),
            __( 'Site web', 'advanced-csv-exporter' ),
        );
        $csv = $this->array_to_csv_line( $headers );

        foreach ( $users as $user ) {
            $roles = implode( ', ', $user->roles );
            $row = array(
                $user->ID,
                $user->user_login,
                $user->user_email,
                $roles,
                $user->user_registered,
                $user->first_name,
                $user->last_name,
                $user->user_url,
            );
            $csv .= $this->array_to_csv_line( $row );
        }

        return $csv;
    }

    /**
     * Streaming generator for large exports.
     * Writes directly to provided output handle using fputcsv.
     *
     * @param array $filters
     * @param resource $out
     * @param string $delimiter
     */
    protected function generate_csv_stream( $filters, $out, $delimiter = ';' ) {
        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Nom d\'utilisateur', 'advanced-csv-exporter' ),
            __( 'Email', 'advanced-csv-exporter' ),
            __( 'Rôle(s)', 'advanced-csv-exporter' ),
            __( 'Date d\'inscription', 'advanced-csv-exporter' ),
            __( 'Prénom', 'advanced-csv-exporter' ),
            __( 'Nom', 'advanced-csv-exporter' ),
            __( 'Site web', 'advanced-csv-exporter' ),
        );
        // fputcsv expects a single-char delimiter; tab is "\t"
        fputcsv( $out, $headers, $delimiter );

        $args = array(
            'fields' => 'all_with_meta',
            'number' => -1,
        );
        if ( ! empty( $filters['role'] ) ) {
            $args['role'] = sanitize_text_field( $filters['role'] );
        }
        $user_query = new WP_User_Query( $args );
        $users = $user_query->get_results();

        foreach ( $users as $user ) {
            $roles = implode( ', ', $user->roles );
            $row = array(
                $user->ID,
                $user->user_login,
                $user->user_email,
                $roles,
                $user->user_registered,
                $user->first_name,
                $user->last_name,
                $user->user_url,
            );
            fputcsv( $out, $row, $delimiter );
        }
    }
}