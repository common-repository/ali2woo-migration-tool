<?php
/*
Plugin Name: Ali2Woo Migration Tool
Description: Ali2Woo Migration Tool allows you to convert products imported by third-party plugins to Ali2Woo format
Text Domain: ali2woo-converter
Domain Path: /languages
Version: 1.1.0
Author: MA-Group
Author URI: https://ali2woo.com
License: GPLv2+
Tested up to: 6.0
WC tested up to: 6.5
WC requires at least: 3.0
 */

if (!defined('A2WC_PLUGIN_FILE')) {
    define('A2WC_PLUGIN_FILE', __FILE__);
}

if (!class_exists('A2WC_Main')) {

    class A2WC_Main
    {

        /**
         * @var The single instance of the class
         */
        protected static $_instance = null;

        /**
         * @var string Ali2WooConverter plugin version
         */
        public $version;

        /**
         * @var array Converters
         */
        private $converters = array();

        private $a2w_installed = false;
        private $a2wl_installed = false;

        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        private function __construct()
        {
            $active_plugins = (array) get_option('active_plugins', array());
            if (is_multisite()) {
                $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
            }

            $this->a2w_installed = in_array('ali2woo/ali2woo.php', $active_plugins) || array_key_exists('ali2woo/ali2woo.php', $active_plugins);
            $this->a2wl_installed = in_array('ali2woo-lite/ali2woo-lite.php', $active_plugins) || array_key_exists('ali2woo-lite/ali2woo-lite.php', $active_plugins);

            if(!$this->a2w_installed && !$this->a2wl_installed) return;

            require_once ABSPATH . 'wp-admin/includes/plugin.php';
            $plugin_data = get_plugin_data(A2WC_PLUGIN_FILE);

            $this->version = $plugin_data['Version'];

            include_once $this->plugin_path() . '/includes/functions.php';

            add_action('admin_enqueue_scripts', array($this, 'admin_assets'));

            add_filter('a2w_converter_installed', function($b){ return true; });
            add_action('a2w_init_admin_menu', array($this, 'add_submenu_page'), 1000);

            add_filter('a2wl_converter_installed', function($b){ return true; });
            add_action('a2wl_init_admin_menu', array($this, 'add_submenu_page_lite'), 1000);

            spl_autoload_register( array( $this, 'autoload' ) );

            $this->load_converters();

            add_action('wp_ajax_a2wc_get_products', array($this, 'ajax_get_products'));
            add_action('wp_ajax_a2wc_convert_product', array($this, 'ajax_convert_product'));
        }

        /**
         * Path to Ali2WooConverter plugin root url
         */
        public function plugin_url($sub_url = false)
        {
            return untrailingslashit(plugins_url('/', A2WC_PLUGIN_FILE)) . ($sub_url ? DIRECTORY_SEPARATOR.$sub_url : "");
        }

        /**
         * Path to Ali2WooConverter plugin root dir
         */
        public function plugin_path($sub_path = false)
        {
            return untrailingslashit(plugin_dir_path(A2WC_PLUGIN_FILE)) . ($sub_path ? DIRECTORY_SEPARATOR.$sub_path : "");
        }

        public function autoload($class)
        {
            if(strpos($class, "Converter") !== false) {
                $dir = $this->plugin_path('includes'. DIRECTORY_SEPARATOR . 'converters' . DIRECTORY_SEPARATOR);
            } else {
                $dir = $this->plugin_path('includes'. DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);
            }
            if ( file_exists( $dir . "{$class}.php" ) ) {
                include( $dir . "{$class}.php" );
                return true;
            }
            return false;
        }

        public function load_converters( )
        {
            include_once($this->plugin_path('includes/abstract/A2WC_AbstractConverter.php'));

            $classpath = $this->plugin_path('includes/converters/');
            foreach (glob($classpath . "*.php") as $f) {
                $file_info = pathinfo($f);
                $class = $file_info['filename'];
                $converter = new $class();
                $this->converters[sanitize_key($converter->get_id())] = $converter;
            }
        }

        public function admin_assets($page)
        {
            if($page === 'ali2woo_page_a2w_converter' || $page === 'ali2woo-lite_page_a2wl_converter') {
                wp_enqueue_style('a2wc-style', A2WC()->plugin_url() . '/assets/css/style.css', array(), A2WC()->version);
                wp_enqueue_script('a2wc-script', A2WC()->plugin_url() . '/assets/js/script.js', array('jquery'),  A2WC()->version);
            }
        }

        public function add_submenu_page($parent_slug) {
            $page_id = add_submenu_page($parent_slug, __('Migration Tool', 'ali2woo-converter'), __('Migration Tool', 'ali2woo-converter'), 'import', 'a2w_converter', array($this, 'render'));
        }

        public function add_submenu_page_lite($parent_slug) {
            $page_id = add_submenu_page($parent_slug, __('Migration Tool', 'ali2woo-converter'), __('Migration Tool', 'ali2woo-converter'), 'import', 'a2wl_converter', array($this, 'render'));
        }

        public function get_converters() {
            return $this->converters;
        }

        public function get_base_sufix($uppercase = false) {
            if($this->a2w_installed) {
                return $uppercase ? 'A2W' : 'a2w';
            } else if($this->a2wl_installed) {
                return $uppercase ? 'A2WL' : 'a2wl';
            }
            throw new Exception('Base ali2woo plugin not installed!');
        }

        public function render()
        {
            include_once($this->plugin_path('view/dashboard.php'));
        }

        public function ajax_get_products(){
            $converters = A2WC()->get_converters();
            $converter_id = isset($_POST['converter']) ? sanitize_key($_POST['converter']) : "";
            if(isset($converters[$converter_id])) {
                $converter = $converters[$converter_id];
                $result = array('state'=>'ok', 'items'=>$converter->get_products());
            } else {
                $result = array('state'=>'error', 'message'=>'Wrong params');
            }
            echo json_encode($result);
            wp_die();
        }

        public function ajax_convert_product(){
            $converters = A2WC()->get_converters();
            $converter_id = isset($_POST['converter']) ? sanitize_key($_POST['converter']) : "";
            $product_id = isset($_POST['product_id']) ? sanitize_key($_POST['product_id']) : "";
            $external_product_id = isset($_POST['product_id']) ? sanitize_key($_POST['external_product_id']) : "";
            if(isset($converters[$converter_id]) && !empty($product_id) && !empty($external_product_id)) {
                $converter = $converters[$converter_id];
                $result = $converter->convert($product_id, $external_product_id);
            } else {
                $result = array('state'=>'error', 'message'=>'Wrong params');
            }

            echo json_encode($result);
            wp_die();
        }
    }

}

/**
 * Returns the main instance of A2WC_Main to prevent the need to use globals.
 *
 * @return A2WC_Main
 */
if (!function_exists('A2WC')) {

    function A2WC()
    {
        return A2WC_Main::instance();
    }
}

$ali2woo_converter = A2WC();