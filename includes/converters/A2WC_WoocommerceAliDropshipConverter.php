<?php

class A2WC_WoocommerceAliDropshipConverter extends A2WC_AbstractConverter {
    public function get_id() {
        return "woocommerce-alidropship";
    }
    
    public function get_name() {
        return "ALD - Aliexpress Dropshipping and Fulfillment for WooCommerce";
    }

    public function  get_products() {
        global $wpdb;
        return $wpdb->get_results("SELECT DISTINCT pm1.post_id as product_id, pm1.meta_value as external_product_id FROM $wpdb->postmeta pm1 LEFT JOIN $wpdb->postmeta pm2 ON (pm1.post_id=pm2.post_id and pm2.meta_key='_a2w_external_id') WHERE pm2.meta_value is null AND pm1.meta_key='_vi_wad_aliexpress_product_id'", ARRAY_A);
    }
}