<?php
/*
    Plugin Name: Product Country Restrictions
    Description: Restrict WooCommerce products to certain countries
    Author: Bizilica
    Author URI: https://www.reddit.com/user/Bizilica
    Version: 0.4.3
    Text Domain: product-country-restrictions
    Domain Path: /lang/

    WC requires at least: 3.0
    WC tested up to: 3.2

*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( !class_exists( 'FZ_Product_Country_Restrictions' ) ) {
    class FZ_Product_Country_Restrictions {
        var $user_country = "";
        
        function __construct() {
            add_action( 'plugins_loaded', array( $this, 'plugin_init' ) );
        }

        function on_activation() {
            WC_Geolocation::update_database();                     
        }
        
        function plugin_init() {
            $i18n_dir = basename( dirname( __FILE__ ) ) . '/lang/';         
            load_plugin_textdomain( 'product-country-restrictions', false, $i18n_dir );

            if ( $this->valid_version() ) {
                add_action( 'woocommerce_product_options_general_product_data', array( $this, 'add_custom_product_fields' ) );
                add_action( 'woocommerce_process_product_meta', array( $this, 'save_custom_product_fields' ) );
                
                add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'add_custom_variation_fields'), 10, 3 );
                add_action( 'woocommerce_save_product_variation', array( $this, 'save_custom_variation_fields'), 10, 2 );

                add_filter( 'woocommerce_is_purchasable', array( $this, 'is_purchasable' ), 10, 2 );
                add_filter( 'woocommerce_available_variation', array( $this, 'variation_filter' ), 10, 3 );

                add_action( 'woocommerce_product_meta_start', array($this, 'meta_area_message') );
                add_filter( 'woocommerce_geolocation_update_database_periodically', array($this, 'update_geo_database'), 10, 1 );   

                add_filter( 'woocommerce_product_settings', array($this, 'add_pcr_settings') );                 
            } else {
                add_action( 'admin_notices', array( $this, 'admin_error_notice' ) );
            }
        }

        function valid_version() {
            if ( defined( 'WOOCOMMERCE_VERSION' ) ) {
                if ( version_compare( WOOCOMMERCE_VERSION, "3.0", ">=" ) ) {
                    return true;
                }
            }
            return false;
        }

        function add_pcr_settings( $settings ) {
            $new_settings = $settings;

            $new_settings[] =
                    array( 'type' => 'title', 'title' => __('Product Country Restrictions', 'product-country-restrictions') );
            $new_settings[] =                     
                    array(
                        'name'     => __( 'Restriction message', 'product-country-restrictions' ),
                        'desc_tip' => __( 'Error message to display when a product is restricted by country.', 'product-country-restrictions' ),
                        'id'       => 'product-country-restrictions-message',
                        'type'     => 'text',
                        'css'      => 'min-width:300px;',
                        'std'      => '',  
                        'default'  => '',  
                        'desc'     => sprintf(__( 'Leave blank for default message: %s', 'product-country-restrictions' ), $this->default_message()),
                    );
            $new_settings[] =                    
                    array( 'type' => 'sectionend' );

            return $new_settings;
        }

        function admin_error_notice() {
            $message = __('Product Country Restrictions requires WooCommerce 3.0 or newer', 'product-country-restrictions');
            echo"<div class='error'><p>$message</p></div>";
        }

        function update_geo_database( ) {
            return true;
        }
        
        function add_custom_product_fields() {
            global $post;
            echo '<div class="options_group">';

            woocommerce_wp_select(
                array(
                    'id'      => '_fz_country_restriction_type',
                    'label'   => __( 'Geographic availability', 'product-country-restrictions' ),
                    'default'       => 'all',
                    'class'         => 'availability wc-enhanced-select',
                    'options'       => array(
                        'all'       => __( 'All countries', 'product-country-restrictions' ),
                        'specific'  => __( 'Selected countries only', 'product-country-restrictions' ),
                        'excluded'  => __( 'Excluding selected countries', 'product-country-restrictions' ),
                    )
                ) );

            $selections = get_post_meta( $post->ID, '_fz_restricted_countries', true );
            if(empty($selections) || ! is_array($selections)) { 
                $selections = array(); 
            }
            $countries = WC()->countries->get_shipping_countries();
            asort( $countries );
?>
            <p class="form-field forminp">
            <label for="_restricted_countries"><?php echo __( 'Selected countries', 'product-country-restrictions' ); ?></label>
            <select multiple="multiple" name="_restricted_countries[]" style="width:350px"
                data-placeholder="<?php esc_attr_e( 'Choose countries&hellip;', 'woocommerce' ); ?>" title="<?php esc_attr_e( 'Country', 'woocommerce' ) ?>"
                class="wc-enhanced-select">
                <?php
            if ( ! empty( $countries ) ) {
                foreach ( $countries as $key => $val ) {
                    echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val . '</option>';
                }
            }
?>
            </select>
            
            </p><?php
            if( empty( $countries ) ) {
                echo "<p><b>You need to setup shipping locations in WooCommerce settings <a href='admin.php?page=wc-settings'>HERE</a> before you can choose country restrictions</b></p>";
            }

            echo '</div>';
        }
        
        function add_custom_variation_fields( $loop, $variation_data, $variation ) {

            woocommerce_wp_select(
                array(
                    'id'      => '_fz_country_restriction_type[' . $variation->ID . ']',
                    'label'   => __( 'Geographic availability', 'product-country-restrictions' ),
                    'default'       => 'all',
                    'class'         => 'availability wc-enhanced-select',
                    'value'         => get_post_meta( $variation->ID, '_fz_country_restriction_type', true ),
                    'options'       => array(
                        'all'       => __( 'All countries', 'product-country-restrictions' ),
                        'specific'  => __( 'Selected countries only', 'product-country-restrictions' ),
                        'excluded'  => __( 'Excluding selected countries', 'product-country-restrictions' ),
                    )
                ) 
            );

            $selections = get_post_meta( $variation->ID, '_fz_restricted_countries', true );
            if(empty($selections) || ! is_array($selections)) { 
                $selections = array(); 
            }
            $countries = WC()->countries->get_shipping_countries();
            asort( $countries );
?>
            <p class="form-field forminp">
            <label for="_restricted_countries[<?php echo $variation->ID; ?>]"><?php echo __( 'Selected countries', 'product-country-restrictions' ); ?></label>
            <select multiple="multiple" name="_restricted_countries[<?php echo $variation->ID; ?>][]" style="width:350px"
                data-placeholder="<?php esc_attr_e( 'Choose countries&hellip;', 'woocommerce' ); ?>" title="<?php esc_attr_e( 'Country', 'woocommerce' ) ?>"
                class="wc-enhanced-select">
<?php
            if ( ! empty( $countries ) ) {
                foreach ( $countries as $key => $val ) {
                    echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ).'>' . $val . '</option>';
                }
            }
?>
            </select>
<?php            
        }

        function save_custom_product_fields( $post_id ) {
            $restriction = $_POST['_fz_country_restriction_type'];
            if(! is_array($restriction)) {
                if ( !empty( $restriction ) )
                    update_post_meta( $post_id, '_fz_country_restriction_type', esc_attr($restriction));

                $countries = array();
                if(isset($_POST["_restricted_countries"])) {
                    $countries = $_POST['_restricted_countries'];
                }
                update_post_meta( $post_id, '_fz_restricted_countries', $countries );
            }
        }

        function save_custom_variation_fields( $post_id ) {
            $restriction = $_POST['_fz_country_restriction_type'][ $post_id ];
            if ( !empty( $restriction ) )
                update_post_meta( $post_id, '_fz_country_restriction_type', esc_attr($restriction) );

            $countries = array();
            if(isset($_POST["_restricted_countries"])) {
                $countries = $_POST['_restricted_countries'][ $post_id ];
            }
            update_post_meta( $post_id, '_fz_restricted_countries', $countries );
        }

        function is_restricted_by_id( $id ) {
            $restriction = get_post_meta( $id, '_fz_country_restriction_type', true );
            if ( 'specific' == $restriction || 'excluded' == $restriction ) {
                $countries = get_post_meta( $id, '_fz_restricted_countries', true );
                if ( empty( $countries ) || ! is_array( $countries ) )
                    $countries = array();

                $customercountry = $this->get_country();

                if ( 'specific' == $restriction && !in_array( $customercountry, $countries ) )
                    return true;

                if ( 'excluded' == $restriction && in_array( $customercountry, $countries ) )
                    return true;
            }

            return false;
        }

        function is_restricted( $product ) {
            $id = $product->get_id();

            if($product->get_type() == 'variation') {
                $parentid = $product->get_parent_id();
                $parentRestricted = $this->is_restricted_by_id($parentid);
                if($parentRestricted)
                    return true;
            }
            return $this->is_restricted_by_id($id);
        }

        function is_purchasable( $purchasable, $product ) {
            if ( $this->is_restricted( $product ) )
                $purchasable = false;
            return $purchasable;
        }

        function variation_filter($a, $b, $c) {
            if(! $a['is_purchasable']) {
                $a['variation_description'] = $this->no_soup_for_you() . $a['variation_description']; 
            }
            return $a;
        }

        function meta_area_message() {
            global $product;

            if($this->is_restricted($product)) {
                echo $this->no_soup_for_you();
            }
        }

        function default_message() {
            return __('Sorry, this product is not available in your country', 'product-country-restrictions');
        }        

        function no_soup_for_you() {
            $msg = get_option('product-country-restrictions-message', $this->default_message());
            if(empty($msg)) { 
                $msg = $this->default_message();
            }
            return "<span class='restricted_country'>" . $msg . "</span>";
        }

        function get_country() {
            if( empty( $this->user_country) ) {
                $geoloc = WC_Geolocation::geolocate_ip();
                $this->user_country = $geoloc['country'];
            }
            return $this->user_country;
        }
    }

    $fzpcr = new FZ_Product_Country_Restrictions();

    register_activation_hook( __FILE__, array( $fzpcr, 'on_activation' ) );
}
