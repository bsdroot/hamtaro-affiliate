<?php
/**
 * Plugin Name: bounani affiliate
 * Plugin URI:  -
 * Description: E-commerce affiliate plugin
 * Version:     1.0
 * Author:      Kira-zaraki
 * Author URI:  https://author.example.com/
 * License:     -
 * License URI: -
 * Text Domain: wporg
 * Domain Path: /languages
 */

 
if ( ! defined( 'ABSPATH' ) ) {
    exit;  
}

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    require('bounani-affiliate-class.php');
    add_action( 'plugins_loaded', array( 'bounaniAffiliate', 'init' ));
	register_activation_hook( __FILE__, array( 'bounaniAffiliate', 'db_init' ) );
}