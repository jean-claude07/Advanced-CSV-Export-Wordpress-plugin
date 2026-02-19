<?php
/**
 * Export des catégories de produits.
 */

defined( 'ABSPATH' ) || exit;

class Adv_CSV_Exporter_Categories extends Adv_CSV_Exporter_Base {

    protected $id = 'categories';
    protected $title = 'Catégories';
    protected $capability = 'manage_categories';

    /**
     * Ajoute le bouton sur la page des catégories.
     */
    public function add_export_button() {
        add_action( 'manage_edit-product_cat_extra_tablenav', array( $this, 'render_button' ) );
    }

    /**
     * Affiche le bouton.
     *
     * @param string $which
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
                    'taxonomy'    => 'product_cat',
                ),
                admin_url( 'edit-tags.php?taxonomy=product_cat&post_type=product' )
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
        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Nom', 'advanced-csv-exporter' ),
            __( 'Slug', 'advanced-csv-exporter' ),
            __( 'Description', 'advanced-csv-exporter' ),
            __( 'Parent', 'advanced-csv-exporter' ),
            __( 'Nombre de produits', 'advanced-csv-exporter' ),
        );
        $csv = $this->array_to_csv_line( $headers );

        foreach ( $categories as $cat ) {
            $parent = $cat->parent ? get_term( $cat->parent )->name : '';
            $row = array(
                $cat->term_id,
                $cat->name,
                $cat->slug,
                $cat->description,
                $parent,
                $cat->count,
            );
            $csv .= $this->array_to_csv_line( $row );
        }

        return $csv;
    }

    /**
     * Streaming generator for categories.
     *
     * @param array $filters
     * @param resource $out
     * @param string $delimiter
     */
    protected function generate_csv_stream( $filters, $out, $delimiter = ';' ) {
        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Nom', 'advanced-csv-exporter' ),
            __( 'Slug', 'advanced-csv-exporter' ),
            __( 'Description', 'advanced-csv-exporter' ),
            __( 'Parent', 'advanced-csv-exporter' ),
            __( 'Nombre de produits', 'advanced-csv-exporter' ),
        );
        fputcsv( $out, $headers, $delimiter );

        $categories = get_terms( array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
        ) );

        foreach ( $categories as $cat ) {
            $parent = $cat->parent ? get_term( $cat->parent )->name : '';
            $row = array(
                $cat->term_id,
                $cat->name,
                $cat->slug,
                $cat->description,
                $parent,
                $cat->count,
            );
            fputcsv( $out, $row, $delimiter );
        }
    }
}