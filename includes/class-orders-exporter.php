<?php
/**
 * Export des commandes.
 */

defined( 'ABSPATH' ) || exit;

class Adv_CSV_Exporter_Orders extends Adv_CSV_Exporter_Base {

    protected $id = 'orders';
    protected $title = 'Commandes';
    protected $capability = 'edit_shop_orders';

    /**
     * Ajoute le bouton sur la page des commandes.
     */
    public function add_export_button() {
        // Pour l'écran classique des commandes (post_type shop_order)
        add_action( 'manage_posts_extra_tablenav', array( $this, 'render_button' ) );

        // Pour HPOS (nouvelle table)
        add_action( 'woocommerce_order_list_table_extra_tablenav', array( $this, 'render_button_hpos' ) );
    }

    /**
     * Rendu pour l'écran posts.
     *
     * @param string $which
     */
    public function render_button( $which ) {
        global $post_type;

        if ( 'top' !== $which || 'shop_order' !== $post_type ) {
            return;
        }

        $this->output_button();
    }

    /**
     * Rendu pour HPOS.
     *
     * @param string $which
     */
    public function render_button_hpos( $which ) {
        if ( 'top' !== $which ) {
            return;
        }
        $this->output_button();
    }

    /**
     * Affiche le bouton.
     */
    private function output_button() {
        $url = wp_nonce_url(
            add_query_arg(
                array(
                    'action'      => 'export_csv',
                    'exporter_id' => $this->id,
                ),
                admin_url( 'edit.php?post_type=shop_order' ) // Même URL pour HPOS
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
        $args = array(
            'limit'  => -1,
            'return' => 'objects',
        );

        // Filtre par statut.
        if ( ! empty( $filters['post_status'] ) && 'all' !== $filters['post_status'] ) {
            $args['status'] = str_replace( 'wc-', '', $filters['post_status'] );
        }

        // Utilisation de wc_get_orders (compatible HPOS)
        $orders = wc_get_orders( $args );

        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Numéro de commande', 'advanced-csv-exporter' ),
            __( 'Date', 'advanced-csv-exporter' ),
            __( 'Statut', 'advanced-csv-exporter' ),
            __( 'Total', 'advanced-csv-exporter' ),
            __( 'Client', 'advanced-csv-exporter' ),
            __( 'Email', 'advanced-csv-exporter' ),
            __( 'Méthode de paiement', 'advanced-csv-exporter' ),
            __( 'Méthode de livraison', 'advanced-csv-exporter' ),
        );

        $csv = $this->array_to_csv_line( $headers );

        foreach ( $orders as $order ) {
            // Shipping methods (may be multiple)
            $shipping_items = $order->get_items( 'shipping' );
            $shipping_methods = array();
            foreach ( $shipping_items as $si ) {
                // $si may be an instance of WC_Order_Item_Shipping
                if ( is_object( $si ) ) {
                    if ( method_exists( $si, 'get_method_title' ) ) {
                        $shipping_methods[] = $si->get_method_title();
                    } elseif ( method_exists( $si, 'get_name' ) ) {
                        $shipping_methods[] = $si->get_name();
                    }
                }
            }

            $shipping = ! empty( $shipping_methods ) ? implode( ', ', $shipping_methods ) : '';

            // Payment method title fallback
            $payment_method = '';
            if ( method_exists( $order, 'get_payment_method_title' ) ) {
                $payment_method = $order->get_payment_method_title();
            } elseif ( method_exists( $order, 'get_payment_method' ) ) {
                $payment_method = $order->get_payment_method();
            }

            // Date created may be WC_DateTime or DateTime
            $date_created = $order->get_date_created();
            if ( is_object( $date_created ) && method_exists( $date_created, 'date' ) ) {
                $date_str = $date_created->date( 'Y-m-d H:i:s' );
            } elseif ( $date_created instanceof DateTime ) {
                $date_str = $date_created->format( 'Y-m-d H:i:s' );
            } else {
                $date_str = '';
            }

            $row = array(
                $order->get_id(),
                $order->get_order_number(),
                $date_str,
                wc_get_order_status_name( $order->get_status() ),
                $order->get_total(),
                trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                $order->get_billing_email(),
                $payment_method,
                $shipping,
            );
            $csv .= $this->array_to_csv_line( $row );
        }

        return $csv;
    }

    /**
     * Streaming generator for orders.
     *
     * @param array $filters
     * @param resource $out
     * @param string $delimiter
     */
    protected function generate_csv_stream( $filters, $out, $delimiter = ';' ) {
        $headers = array(
            __( 'ID', 'advanced-csv-exporter' ),
            __( 'Numéro de commande', 'advanced-csv-exporter' ),
            __( 'Date', 'advanced-csv-exporter' ),
            __( 'Statut', 'advanced-csv-exporter' ),
            __( 'Total', 'advanced-csv-exporter' ),
            __( 'Client', 'advanced-csv-exporter' ),
            __( 'Email', 'advanced-csv-exporter' ),
            __( 'Méthode de paiement', 'advanced-csv-exporter' ),
            __( 'Méthode de livraison', 'advanced-csv-exporter' ),
        );
        fputcsv( $out, $headers, $delimiter );

        $args = array(
            'limit'  => -1,
            'return' => 'objects',
        );

        if ( ! empty( $filters['post_status'] ) && 'all' !== $filters['post_status'] ) {
            $args['status'] = str_replace( 'wc-', '', $filters['post_status'] );
        }

        $orders = wc_get_orders( $args );

        foreach ( $orders as $order ) {
            $shipping_items = $order->get_items( 'shipping' );
            $shipping_methods = array();
            foreach ( $shipping_items as $si ) {
                if ( is_object( $si ) ) {
                    if ( method_exists( $si, 'get_method_title' ) ) {
                        $shipping_methods[] = $si->get_method_title();
                    } elseif ( method_exists( $si, 'get_name' ) ) {
                        $shipping_methods[] = $si->get_name();
                    }
                }
            }

            $shipping = ! empty( $shipping_methods ) ? implode( ', ', $shipping_methods ) : '';

            $payment_method = '';
            if ( method_exists( $order, 'get_payment_method_title' ) ) {
                $payment_method = $order->get_payment_method_title();
            } elseif ( method_exists( $order, 'get_payment_method' ) ) {
                $payment_method = $order->get_payment_method();
            }

            $date_created = $order->get_date_created();
            if ( is_object( $date_created ) && method_exists( $date_created, 'date' ) ) {
                $date_str = $date_created->date( 'Y-m-d H:i:s' );
            } elseif ( $date_created instanceof DateTime ) {
                $date_str = $date_created->format( 'Y-m-d H:i:s' );
            } else {
                $date_str = '';
            }

            $row = array(
                $order->get_id(),
                $order->get_order_number(),
                $date_str,
                wc_get_order_status_name( $order->get_status() ),
                $order->get_total(),
                trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
                $order->get_billing_email(),
                $payment_method,
                $shipping,
            );
            fputcsv( $out, $row, $delimiter );
        }
    }
}