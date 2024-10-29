<?php

abstract class A2WC_AbstractConverter {
    abstract public function get_id();

    abstract public function get_name();
    
    abstract public function get_products();

    public function before_convert($product_id, $external_product_id) {
        return array('state' => 'ok');
    }

    public function after_convert($product_id, $external_product_id) {
        return array('state' => 'ok');
    }

    public function convert($product_id, $external_product_id) {
        global $wpdb;

        $result = array('state' => 'ok');

        if ($result['state'] !== 'error') {
            $result = $this->before_convert($product_id, $external_product_id);
        }

        // mark all variation for deletion
        $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) SELECT distinct p1.ID, 'external_variation_id', 'delete' FROM $wpdb->posts p1 LEFT JOIN $wpdb->postmeta pm1 ON (p1.ID=pm1.post_id and pm1.meta_key='external_variation_id') WHERE p1.post_type='product_variation' AND p1.post_parent=%d AND pm1.meta_value IS NULL", $product_id));

        // add external product ID
        $wpdb->query($wpdb->prepare("INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) SELECT distinct p.ID, '_a2w_external_id', %s FROM $wpdb->posts p LEFT JOIN $wpdb->postmeta pm1 ON (p.ID=pm1.post_id and pm1.meta_key='_a2w_external_id') WHERE p.ID=%d AND p.post_type='product' AND pm1.meta_value is null", $external_product_id, $product_id));

        // Sync product
        $woocommerce_model = $this->get_class_instance('Woocommerce');
        $aliexpress_model = $this->get_class_instance('Aliexpress');
        $sync_model = $this->get_class_instance('Synchronize');
        $price_model = $this->get_class_instance('PriceFormula');

        $result = $aliexpress_model->sync_products(array($external_product_id), array('manual_update' => 1, 'pc' => $sync_model->get_product_cnt()));
        if ($result['state'] !== 'error') {
            foreach ($result['products'] as $product) {
                $product = $price_model->apply_formula($product);
                $product['skip_vars'] = array();
                $product['skip_images'] = array();
                $product['disable_sync'] = false;
                $product['disable_var_price_change'] = false;
                $product['disable_var_quantity_change'] = false;
                $product['disable_add_new_variants'] = false;

                $result = $woocommerce_model->upd_product($product_id, $product, array('manual_update' => 1, 'on_new_variation_appearance' => 'add', 'on_not_available_variation'=>'trash'));
            }
        }

        if ($result['state'] !== 'error') {
            $result = $this->after_convert($product_id, $external_product_id);
        }

        return $result;
    }

    private function get_class_instance($name) {
        $clazz = A2WC()->get_base_sufix(true).'_'.$name;
        return new $clazz();
    }
}