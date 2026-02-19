<?php
/**
 * Export des produits.
 */

defined( 'ABSPATH' ) || exit;

class Adv_CSV_Exporter_Products extends Adv_CSV_Exporter_Base {

    protected $id = 'products';
    protected $title = 'Produits';
    protected $capability = 'edit_products';

    /**
     * Ajoute le bouton sur la page des produits.
     */
    public function add_export_button() {
        add_action( 'manage_posts_extra_tablenav', array( $this, 'render_button' ) );
    }

    /**
     * Affiche le bouton.
     *
     * @param string $which
     */
    public function render_button( $which ) {
        global $post_type;

        if ( 'top' !== $which || 'product' !== $post_type ) {
            return;
        }

        // Récupérer les filtres actuels (catégorie, etc.) pour les conserver.
        $query_args = array(
            'action'      => 'export_csv',
            'exporter_id' => $this->id,
        );

        // Ajouter les filtres existants.
        $filters = array( 'product_cat', 'product_type', 'stock_status' );
        foreach ( $filters as $filter ) {
            if ( isset( $_GET[ $filter ] ) && ! empty( $_GET[ $filter ] ) ) {
                $query_args[ $filter ] = sanitize_text_field( $_GET[ $filter ] );
            }
        }

        $url = wp_nonce_url(
            add_query_arg( $query_args, admin_url( 'edit.php?post_type=product' ) ),
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
        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        // Filtre par catégorie.
        $tax_query = array();
        if ( ! empty( $filters['product_cat'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $filters['product_cat'] ),
            );
        }

        // Filtre par type de produit.
        if ( ! empty( $filters['product_type'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $filters['product_type'] ),
            );
        }

        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }

        $products = get_posts( $args );

        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Nom', 'advanced-csv-exporter' ),
            __( 'SKU', 'advanced-csv-exporter' ),
            __( 'Prix', 'advanced-csv-exporter' ),
            __( 'Stock', 'advanced-csv-exporter' ),
            __( 'Catégories', 'advanced-csv-exporter' ),
            __( 'Type', 'advanced-csv-exporter' ),
            __( 'Date création', 'advanced-csv-exporter' ),
        );
        $csv = $this->array_to_csv_line( $headers );

        foreach ( $products as $product_post ) {
            $product = wc_get_product( $product_post->ID );
            if ( ! $product ) {
                continue;
            }

            $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
            $row = array(
                $product->get_id(),
                $product->get_name(),
                $product->get_sku(),
                $product->get_price(),
                $product->get_stock_status() === 'instock' ? __( 'En stock', 'advanced-csv-exporter' ) : __( 'Rupture', 'advanced-csv-exporter' ),
                implode( ', ', $categories ),
                $product->get_type(),
                // get_date_created can return WC_DateTime or DateTime; handle both safely
                ( function() use ( $product ) {
                    $dt = $product->get_date_created();
                    if ( ! $dt ) {
                        return '';
                    }
                    if ( is_object( $dt ) && method_exists( $dt, 'date' ) ) {
                        return $dt->date( 'Y-m-d H:i:s' );
                    }
                    if ( $dt instanceof DateTime ) {
                        return $dt->format( 'Y-m-d H:i:s' );
                    }
                    return (string) $dt;
                } )(),
            );
            $csv .= $this->array_to_csv_line( $row );
        }

        return $csv;
    }

    /**
     * Streaming generator for products.
     *
     * @param array $filters
     * @param resource $out
     * @param string $delimiter
     */
    protected function generate_csv_stream( $filters, $out, $delimiter = ';' ) {
        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Nom', 'advanced-csv-exporter' ),
            __( 'SKU', 'advanced-csv-exporter' ),
            __( 'Prix', 'advanced-csv-exporter' ),
            __( 'Stock', 'advanced-csv-exporter' ),
            __( 'Catégories', 'advanced-csv-exporter' ),
            __( 'Type', 'advanced-csv-exporter' ),
            __( 'Date création', 'advanced-csv-exporter' ),
        );
        fputcsv( $out, $headers, $delimiter );

        $args = array(
            'post_type'      => 'product',
            'posts_per_page' => -1,
            'post_status'    => 'publish',
        );

        $tax_query = array();
        if ( ! empty( $filters['product_cat'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $filters['product_cat'] ),
            );
        }
        if ( ! empty( $filters['product_type'] ) ) {
            $tax_query[] = array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $filters['product_type'] ),
            );
        }
        if ( ! empty( $tax_query ) ) {
            $args['tax_query'] = $tax_query;
        }

        $products = get_posts( $args );

        foreach ( $products as $product_post ) {
            $product = wc_get_product( $product_post->ID );
            if ( ! $product ) {
                continue;
            }

            $categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) );
            $row = array(
                $product->get_id(),
                $product->get_name(),
                $product->get_sku(),
                $product->get_price(),
                $product->get_stock_status() === 'instock' ? __( 'En stock', 'advanced-csv-exporter' ) : __( 'Rupture', 'advanced-csv-exporter' ),
                implode( ', ', $categories ),
                $product->get_type(),
                ( function() use ( $product ) {
                    $dt = $product->get_date_created();
                    if ( ! $dt ) {
                        return '';
                    }
                    if ( is_object( $dt ) && method_exists( $dt, 'date' ) ) {
                        return $dt->date( 'Y-m-d H:i:s' );
                    }
                    if ( $dt instanceof DateTime ) {
                        return $dt->format( 'Y-m-d H:i:s' );
                    }
                    return (string) $dt;
                } )(),
            );
            fputcsv( $out, $row, $delimiter );
        }
    }
}