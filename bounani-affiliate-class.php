<?php

class bounaniAffiliate{

	public static function init() {
       $class = __CLASS__;
       new $class;
    }

    public static function db_init(){   
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		maybe_create_table( $wpdb->prefix . 'affiliate', "CREATE TABLE $wpdb->prefix . 'affiliate' ( id INT PRIMARY KEY NOT NULL AUTO_INCREMENT, affiliate_post_id INT, client_id INT, states varchar(20), order_date date default null  )$charset_collate;" ); 
	}
    private $wpdb;
    private $table_name;
    function __construct(){  	
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->table_name = $this->wpdb->prefix . 'affiliate'; 
		$this->init_filters_actions();
    }
    function init_filters_actions(){
    	add_action( 'woocommerce_single_product_summary',  array($this,'create_get_url_button' ), 10, 1 );
		add_action( 'woocommerce_before_add_to_cart_button',  array($this,'add_hidden_infos_to_form' ));
		add_action( 'wp_enqueue_scripts', array($this,'add_script_ajax_js' ));
        add_action( 'wp_enqueue_scripts', array($this,'add_affiliate_style'));
        add_action( 'wp_ajax_generate_affiliate_code' , array($this,'generate_affiliate_code' ));
		add_action( 'wp_ajax_nopriv_generate_affiliate_code' , array($this,'generate_affiliate_code' ));
        add_action( 'wp_ajax_analytics' , array($this,'analytics' ));
		add_action( 'init', array($this,'affiliate_post_type' ));
		add_filter( 'manage_affiliate_posts_columns', array($this,'affiliat_add_custom_fields' ));
		add_action( 'manage_affiliate_posts_custom_column' , array($this,'affiliat_add_custom_fields_value'), 10, 2 );
		add_filter( 'woocommerce_account_menu_items' , array($this,'affiliat_add_item_to_my_account_menu') );
		add_filter( 'query_vars', array($this,'affiliate_add_query_token' )); 
		add_action( 'init', array($this,'affiliate_add_endpoint_to_my_account_and_rewrite' ));
		add_action( 'woocommerce_account_affiliate_endpoint', array($this,'affiliate_user_dashboard' )); 
        add_action( 'woocommerce_add_to_cart', array($this,'add_to_cart_action' )); 
        add_action( 'woocommerce_thankyou', array($this,'action_woocommerce_after_checkout_shipping_form'), 10, 1 );  
        add_action('admin_menu', array($this,'add_sub_menu_to_affiliate_menu'));
    }
    
    function add_affiliate_style(){
    	if (is_account_page()) {
    		wp_enqueue_style( 'dashboard_affiliate_bootstrap_css', plugins_url('/lib/css/bootstrap.min.css', __FILE__ ));
		    wp_enqueue_script( 'dashboard_affiliate_bootstrap_js', plugins_url('/lib/js/bootstrap.min.js', __FILE__ ),array('jquery'),null,'all');   
		    wp_enqueue_script( 'dashboard_affiliate_jquery_ui_js', plugins_url( '/lib/js/jquery-ui.min.js', __FILE__ ), array('jquery') ,null,false); 
		    wp_enqueue_style( 'dashboard_affiliate_jquery_ui_css', plugins_url( '/lib/css/jquery-ui.min.css', __FILE__ )); 
		    wp_enqueue_script( 'canvas-js', plugins_url( '/lib/js/jquery.canvasjs.min.js', __FILE__ ), array('jquery') ,null,false); 
    	}
		wp_enqueue_style( 'core', get_template_directory_uri() . '/style.css' );
        wp_enqueue_style( 'style-affiliate', plugins_url( 'css/style.css', __FILE__ ), array(), null, 'all' );
    }

	function create_get_url_button( $product ) {     
		echo "<p><input type='button' value='get your url' id='get_url' data-id='".wc_get_product()->get_id()."'></p>";

	}

	function add_hidden_infos_to_form() {    
	    echo "<input type='hidden' value=".get_query_var('affiliate_token')." name='affiliate_token' id='get_token' data-token='".get_query_var('affiliate_token')."'>";
	    echo "<p><input type='hidden' value=".wc_get_product()->get_id()." name='product_id' id='get_url' data-id='".wc_get_product()->get_id()."'></p>";
	}

    function add_script_ajax_js(){ 
    	wp_enqueue_script( 'ajax-script', plugins_url( '/js/ajax.js', __FILE__ ), array('jquery') );
		wp_localize_script( 'ajax-script', 'ajax_object',array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'plugin_url' =>plugin_dir_url(__FILE__ ) ) );
    }

    function generate_affiliate_code(){   
		$affilat_post = array(
		  'post_type'    => 'affiliate', 
		  'post_title'    => 'affiliate',
		  'post_status'  => 'publish'
		); 
        $shop = $this->get_affiliate_url_path($_POST['product_id']);  
		$token = bin2hex(random_bytes(16)); 
        $post = wp_insert_post( $affilat_post );
		update_post_meta($post , 'affiliate_id', get_current_user_id());
		update_post_meta($post , 'affiliate_token', $token);
		update_post_meta($post , 'product_id', $_POST['product_id']); 
		
    	echo "$shop/$token";
    	
    }

 	function get_current_user_posts($params = null){
	    $args = array(
	            'post_type' => 'affilate',
	            'fields'          => $params,
	            'meta_query' => array(
	                array(
	                    'key' => 'affilate_id',
	                    'value' => get_current_user_id()
	                )
	            )
	         ); 
	    return get_posts( $args );
 	}
    function get_affiliate_url_path($product_id){  
        $shop = str_replace(site_url().'/','',get_permalink( $product_id ));
        $shop = explode('/', $shop)[0];  
        $shop = str_replace($shop,'product',get_permalink( $product_id ));
        return $shop;
    }

	function analytics(){ 
   	   $ids = implode(',' , $this->get_current_user_posts('ids')); 
	   $affilat_post_chart = $this->wpdb->get_results( "
        SELECT count(*) y ,  order_date label FROM `wp_affiliate` 
        WHERE `states` like 'ordered'
        AND `affiliate_post_id` in ($ids)
        GROUP BY order_date
        ", ARRAY_A );
	   function convert_to_int(&$item,$key){
	        $item['y'] = intval($item['y']);
	   }
	   array_walk($affilat_post_chart,'convert_to_int');
	   print_r(json_encode($affilat_post_chart));
	
	}

	function affiliate_post_type() { 

	  register_post_type( 'affiliate',
	    array(
	      'labels' => array(
	      'name' => __( 'affiliate' ),
	      'singular_name' => __( 'affiliate' ),
	      ),
		  'supports'          => array( 'title', 'product', 'affiliate', 'client', 'token' ),
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_nav_menus'       => false, 
			'show_in_admin_bar'       => false, 
	    )
	  );
	}

    function affiliat_add_custom_fields($columns){

    	return array(
    		'cb' => '<input type="checkbox" />',
    		'title' => 'title',
    		'affiliate' => 'affiliate', 
            'product' => 'product', 
            'token' => 'token',
    	);
    }

	function affiliat_add_custom_fields_value($columns , $post_id){  
    	switch ( $columns ) {

	        case 'title' : 
	            echo get_post($post_id)->post_title; 
	            break;

	        case 'affiliate' :
	            echo get_post_meta( $post_id , 'affiliate_id' , true ); 
	            break;

	        case 'product' :
	            echo get_post_meta( $post_id , 'product_id' , true ); 
	            break; 

	        case 'token' :
	            echo get_post_meta( $post_id , 'affiliate_token' , true ); 
	            break;

    	}
    }

    function affiliat_add_item_to_my_account_menu($item){
    	$item['affiliate'] = 'affiliate';
    	return $item;
    }

    function affiliate_add_query_token($query){
    	$query[] ='affiliate_token';
    	return $query;
    }

    function affiliate_add_endpoint_to_my_account_and_rewrite(){ 
    	add_rewrite_tag('%affiliate_token%', '([^/]*)'); 
    	add_rewrite_rule('^product/([^/]*)/([^/]*)/?', 'index.php?product=$matches[1]&affiliate_token=$matches[2]', 'top'); 
    	add_rewrite_endpoint( 'affiliate', EP_PAGES );
    	add_rewrite_endpoint( 'affiliate_token', EP_ALL);  
    }  

	function affiliate_user_dashboard() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		} 


		$affiliate_ports =  $this->get_current_user_posts();  
	    $total = 0;
	    $percentage = get_option('percentage') / 100;
	    foreach ($this->get_affiliate_gain_data() as $key => $product) { 
	        $total += wc_get_product($product["product"])->get_price()*$product["total"]*$percentage;
         }   
		require_once plugin_dir_url(__FILE__ ).'inc/admin_dashboard.html';
	}
	 function get_affiliate_gain_data(){ 
	 	$user_id = get_current_user_id();    
	    $affilat_post_chart = $this->wpdb->get_results( "SELECT count(*) total , prd.meta_value product
	        FROM {$this->wpdb->postmeta} prd, {$this->wpdb->postmeta} clt,`wp_affiliate` ,{$this->wpdb->posts} post  
	        WHERE `wp_affiliate`.affiliate_post_id = prd.post_id 
	        AND post.ID = prd.post_id
	        AND post.ID = clt.post_id
	        AND prd.meta_key = 'product_id' 
	        AND clt.meta_key = 'client_id' 
	        AND clt.meta_value = $user_id
	        AND `wp_affiliate`.states = 'ordered'
	        AND post.post_type = 'affilate' 
	        GROUP BY product", ARRAY_A );
	    return $affilat_post_chart;
	 }

    function add_to_cart_action($product){   
        extract($_POST);   
        $post_affiliat = $this->get_posts_by_tiken( $affiliate_token );   
        $this->wpdb->insert( 
            $this->table_name, 
            array( 
                'affiliate_post_id' => $post_affiliat, 
                'client_id' => get_current_user_id(), 
                'states' => 'in cart',  
            ) 
        );
        $value = json_encode(array('affiliate_token'=>$affiliate_token,'post_id'=>$this->wpdb->insert_id));
        setcookie($product_id,$value,time()+2592000, "/");  
    }

    function get_posts_by_tiken($token){ 
        $args = array(
            'post_type' => 'affiliate',
            'meta_query' => array(
                array(
                    'key' => 'affiliate_token',
                    'value' => $token, 
                )
            )
         );  
        return get_posts( $args )[0]->ID;  
    }
    
    function action_woocommerce_after_checkout_shipping_form($order_id){   
 
        global $product; 
        $products = wc_get_order( $order_id );
        $products = $products->get_items(); 

        foreach ($products as $key => $product) { 
            if (isset($_COOKIE[$product['product_id']]) && !empty($_COOKIE[$product['product_id']])) {       
                $coockie_data=json_decode(stripslashes($_COOKIE[$product['product_id']]), true); 

                $this->wpdb->update( 
                     $this->table_name, 
                    array( 
                        'states' => 'ordered',  
                        'order_date' => date("Y-m-d"),  
                    ), 
                    array( 'id' => $coockie_data['post_id'] ) 
                );  
                unset($_COOKIE[$product['product_id']]);
            }  
        }

    }

	function get_client_by_post_id($id){ 
	    $results = $this->wpdb->get_results( "SELECT * FROM $this->table_name WHERE affiliate_post_id = $id AND states like 'ordered'", ARRAY_A );
	    return $results;
	}
    
    function add_sub_menu_to_affiliate_menu(){
        add_submenu_page( 
            'edit.php?post_type=affiliate',
            'settings',
            'settings',
            'manage_options',
            'my-custom-submenu-page',
            array($this,'my_custom_submenu_page_callback')
        );
        add_action('admin_init', array($this,'add_option_to_affiliate_settings'));        
    }

    function my_custom_submenu_page_callback(){
        require_once 'inc/settings.html';
    }

    function add_option_to_affiliate_settings(){
         register_setting( 'affiliate_options', 'percentage' );
    }

    function get_affilate_url_path($product_id){
        $shop = str_replace(site_url().'/','',get_permalink( $product_id ));
        $shop = explode('/', $shop)[0];  
        $shop = str_replace($shop,'product',get_permalink( $product_id ));
        return $shop;
    }

}
