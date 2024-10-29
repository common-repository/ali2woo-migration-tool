<?php

class A2WC_AliDropshipConverter extends A2WC_AbstractConverter {
    public function get_id() {
        return "AliDropship";
    }
    
    public function get_name() {
        return "AliDropship";
    }

    public function  get_products() {
        global $wpdb;
        return $wpdb->get_results("SELECT distinct am.post_id as product_id, am.product_id as external_product_id FROM {$wpdb->prefix}adsw_ali_meta am INNER JOIN $wpdb->posts p ON (am.post_id=p.ID and p.post_type='product') LEFT JOIN $wpdb->postmeta pm1 ON (am.post_id=pm1.post_id and pm1.meta_key='_a2w_external_id') WHERE pm1.meta_value is null", ARRAY_A);
    }

    public function after_convert($product_id, $external_product_id) {
        global $wpdb;

        // Convert alidropship images
        $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta (post_id,meta_key,meta_value) SELECT distinct pm1.post_id, '_wp_a2w_attached_file', '1' from $wpdb->postmeta pm1 INNER JOIN $wpdb->posts p ON (pm1.post_id=p.ID and p.post_parent=%d) INNER JOIN $wpdb->postmeta pm2 ON (pm1.post_id=pm2.post_id and pm2.meta_key='_wp_adsw_attached_file') LEFT JOIN $wpdb->postmeta pm3 ON (pm1.post_id=pm3.post_id and pm3.meta_key='_wp_a2w_attached_file') WHERE pm3.meta_value is null", $product_id));

        $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta (post_id,meta_key,meta_value) SELECT distinct pm1.post_id, '_a2w_external_image_url', pm2.meta_value from $wpdb->postmeta pm1 INNER JOIN $wpdb->posts p ON (pm1.post_id=p.ID and p.post_parent=%d) INNER JOIN $wpdb->postmeta pm2 ON (pm1.post_id=pm2.post_id and pm2.meta_key='_adsw_external_image_url') LEFT JOIN $wpdb->postmeta pm3 ON (pm1.post_id=pm3.post_id and pm3.meta_key='_a2w_external_image_url') WHERE pm3.meta_value is null", $product_id));

        return array('state' => 'ok');
    }
}