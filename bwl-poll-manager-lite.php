<?php
/**
 * Plugin Name: BWL Poll Manager Lite
 * Plugin URI: https://wordpress.org/plugins/bwl-poll-manager-lite
 * Description: Poll Manager Lite provides a great option to add a custom voting system anywhere on the site.
 * Author: Mahbub Alam Khan
 * Version: 1.0.6
 * Author URI: https://bluewindlab.net/
 * WP Requires at least: 6.0+
 * Text Domain: bwl-poll
 * Domain Path: /lang/
 *
 * @package   BWLPollManagerLite
 * @author    Mahbub Alam Khan
 * @license   GPL-2.0+
 * @link      https://codecanyon.net/user/xenioushk
 * @copyright 2025 BlueWindLab
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

if ( ! class_exists( 'BWL_Poll_Manager' ) ) {

    register_activation_hook( __FILE__, [ 'BWL_Poll_Manager', 'bpm_create_custom_table' ] );

    class BWL_Poll_Manager {


        function __construct() {

            /*--- PLUGIN COMMON CONSTANTS ---*/

            define( 'BWL_PM_PLUGIN_TITLE', 'BWL Poll Manager Lite' );
            define( 'BWL_PM_PLUGIN_DIR', plugins_url() . '/bwl-poll-manager-lite/' );
            define( 'BWL_PM_PLUGIN_VERSION', '1.0.6' );
            $this->register_post_type();
            $this->taxonomies();
            // Call Immediatly Initialized.
            $this->included_files();
            $this->enqueue_plugin_scripts();

            // Upgrade data table
            // Since: Version 1.0.4
            $this->bpm_upgrade_db_table();

            $this->bpm_flash_rules(); // Added in version 1.0.4

            // Edit plugin metalinks
            add_filter( 'plugin_row_meta', [ $this, 'bpm_metalinks' ], null, 2 );
        }

        public function bpm_metalinks( $links, $file ) {

            if ( empty( get_option( 'bpm_lite_install_date' ) ) ) {
                add_option( 'bpm_lite_install_date', date( 'Y-m-d H:i:s' ) );
            }

            if ( strpos( $file, 'bwl-poll-manager-lite.php' ) !== false && is_plugin_active( $file ) ) {

                $new_links = [
                    '<a href="' . esc_url( 'https://xenioushk.github.io/docs-wp-plugins/bpm/index.html' ) . '" target="_blank">' . __( 'Documentation', 'bwl-poll' ) . '</a>',
                    '<a href="' . esc_url( 'https://1.envato.market/bpm-wp' ) . '" target="_blank" style="color:green; font-weight: bold;">' . __( 'Get Pro Version', 'bwl-poll' ) . '</a>',
                ];

                $links = array_merge( $links, $new_links );
            }

            return $links;
        }

        function bpm_flash_rules() {

            $bpm_data = get_option( 'bwl_poll_options' );

            $bpm_flash_rules_status = get_option( 'bpm_flash_rules_status' );

            if ( $bpm_flash_rules_status != 1 ) {

                flush_rewrite_rules();
                update_option( 'bpm_flash_rules_status', 1 );
            }

            // Matching Old Slug & New Slug Value.
            // First we get data from plugin option panel.

            $bpm_custom_slug = 'bwl-poll';

            if ( isset( $bpm_data['bpm_custom_slug'] ) && $bpm_data['bpm_custom_slug'] != '' ) {

                $bpm_custom_slug = trim( $bpm_data['bpm_custom_slug'] );
            }

            $bpm_old_custom_slug = get_option( 'bpm_old_custom_slug' );

            if ( $bpm_old_custom_slug == '' ) {

                update_option( 'bpm_old_custom_slug', $bpm_custom_slug );
            }

            if ( $bpm_custom_slug != $bpm_old_custom_slug ) {

                flush_rewrite_rules();
                update_option( 'bpm_old_custom_slug', $bpm_custom_slug );
            }
        }

        function included_files() {

            include_once __DIR__ . '/includes/bpm-filters.php';
            include_once __DIR__ . '/includes/bpm-helper-functions.php';
            include_once __DIR__ . '/shortcode/bpm-shortcodes.php';
            include_once __DIR__ . '/includes/bpm-vote-counter.php';
            include_once __DIR__ . '/includes/bpm-custom-google-font.php';
            include_once __DIR__ . '/includes/bpm-single-page.php';

            if ( ! is_admin() ) {

                include_once __DIR__ . '/includes/bpm-custom-theme.php';  // Intriduced in version 1.0.3

            }

            if ( is_admin() ) {

                include_once __DIR__ . '/includes/bpm-cmb-framework/bpm_cmb_fields.php';
                include_once __DIR__ . '/includes/bpm-custom-column.php';
                include_once __DIR__ . '/tinymce/bpm_tiny_mce_config.php';
                include_once __DIR__ . '/includes/bpm-quick-edit.php';

                include_once __DIR__ . '/option-panel/bpm-admin-settings.php';
            }
        }

        // @Upgrade Database Tabel.
        // @Introduced in version: 1.1.0

        function bpm_upgrade_db_table() {

            global $wpdb;

            $bpm_voting_data_table = $wpdb->prefix . 'bpm_vote_data';

            if ( $wpdb->get_var( "SHOW TABLES LIKE '$bpm_voting_data_table'" ) != $bpm_voting_data_table ) {
                $this->bpm_create_custom_table();
            }
        }

        // @Custom Table For Count Votes.
        // @Introduced in version: 1.0.4

        public static function bpm_create_custom_table() {

            global $wpdb;

            $bpm_voting_data_table = $wpdb->prefix . 'bpm_vote_data'; // for deatils. each day info.

            $sql = "CREATE TABLE $bpm_voting_data_table (
                                ID int(11) NOT NULL AUTO_INCREMENT,
                                postid bigint(20) NOT NULL,
                                opt_id bigint(20) NOT NULL,
                                vote int(20) DEFAULT 1,
                                voted_ip varchar(36) NOT NULL,
                                user_id int(20) DEFAULT 0,
                                vote_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                                PRIMARY KEY  (ID)
                        )";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            dbDelta( $sql );
        }

        function enqueue_plugin_scripts() {

            $bwl_pm_data = get_option( 'bwl_poll_options' );

            if ( ! is_admin() ) {

                bpm_enqueue_google_fonts();

                if ( ! isset( $pvm_data['bpm_fontawesome_status'] ) || $pvm_data['bpm_fontawesome_status'] == 1 ) {
                    wp_enqueue_style( 'bpm-font-awesome', plugins_url( 'css/font-awesome.min.css', __FILE__ ) );
                }

                // Register Styles.

                wp_register_style( 'bpm-tooltipster', plugins_url( 'css/tooltipster.css', __FILE__ ) );
                wp_register_style( 'bpm-jquery-remodal-style', plugins_url( 'css/jquery.remodal.css', __FILE__ ) );
                wp_register_style( 'bpm-jquery-toast-style', plugins_url( 'css/jquery.toast.min.css', __FILE__ ) );
                wp_register_style( 'bpm-custom-styles', plugins_url( 'css/custom-styles.css', __FILE__ ) );
                wp_register_style( 'bpm-rtl-support', plugins_url( 'css/rtl-support.css', __FILE__ ) );

                wp_enqueue_style( 'bpm-custom-styles' );
                wp_enqueue_style( 'bpm-rtl-support' );

                // Register Scripts.
                wp_register_script( 'bpm-custom-script', plugins_url( 'js/custom-scripts.js', __FILE__ ), [ 'jquery' ], '', false );
            }

            if ( is_admin() ) {

                // shortcode
                wp_enqueue_style( 'bwl-poll-shortcode-editor-style', plugins_url( 'tinymce/css/bpm-editor.css', __FILE__ ) );
                wp_register_script( 'bpm-admin-custom-scripts', plugins_url( 'js/bpm-admin-custom-scripts.js', __FILE__ ), [ 'jquery', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-draggable', 'jquery-ui-droppable' ], '', true );
                wp_enqueue_script( 'bpm-admin-custom-scripts' );
            }
        }

        /*--- Define Custom Post Type  ---*/

        public function register_post_type() {

            /*
             * Custom Slug Section.
             */

            $bpm_options = get_option( 'bwl_poll_options' );

            $bpm_custom_slug = 'bwl-poll';

            if ( isset( $bpm_options['bpm_custom_slug'] ) && $bpm_options['bpm_custom_slug'] != '' ) {

                $bpm_custom_slug = trim( $bpm_options['bpm_custom_slug'] );
            }

            $labels = [
                'name'               => esc_html__( 'All Polls', 'bwl-poll' ),
                'singular_name'      => esc_html__( 'Poll', 'bwl-poll' ),
                'add_new'            => esc_html__( 'Add New Poll', 'bwl-poll' ),
                'add_new_item'       => esc_html__( 'Add New Poll', 'bwl-poll' ),
                'edit_item'          => esc_html__( 'Edit Poll', 'bwl-poll' ),
                'new_item'           => esc_html__( 'New Poll', 'bwl-poll' ),
                'all_items'          => esc_html__( 'All Poll', 'bwl-poll' ),
                'view_item'          => esc_html__( 'View Polls', 'bwl-poll' ),
                'search_items'       => esc_html__( 'Search Polls', 'bwl-poll' ),
                'not_found'          => esc_html__( 'No Poll found', 'bwl-poll' ),
                'not_found_in_trash' => esc_html__( 'No Poll found in Trash', 'bwl-poll' ),
                'parent_item_colon'  => '',
                'menu_name'          => esc_html__( 'BWL Poll Lite', 'bwl-poll' ),
            ];

            $args = [
                'labels'             => $labels,
                'query_var'          => 'bwl_polls',
                'show_in_nav_menus'  => true,
                'public'             => true,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'rewrite'            => [
                    'slug' => $bpm_custom_slug,
                ],
                'publicly_queryable' => true,
                'capability_type'    => 'post',
                'has_archive'        => true,
                'hierarchical'       => false,
                'show_in_admin_bar'  => true,
                'supports'           => [ 'title' ],
                'menu_icon'          => BWL_PM_PLUGIN_DIR . 'images/bwl_poll_menu_icon.png',
            ];

            register_post_type( 'bwl_poll', $args ); // text domian limitations :D

        }

        public function taxonomies() {

            /*
             * Custom Slug Section.
             */

            $bpm_options = get_option( 'bpm_options' );

            $bpm_custom_slug = 'bwl-poll';

            if ( isset( $bpm_options['bpm_custom_slug'] ) && $bpm_options['bpm_custom_slug'] != '' ) {

                $bpm_custom_slug = trim( $bpm_options['bpm_custom_slug'] );
            }

            $taxonomies = [];

            $this->register_all_taxonomies( $taxonomies );
        }


        function register_all_taxonomies( $taxonomies ) {

            foreach ( $taxonomies as $name => $arr ) {
                register_taxonomy( $name, [ 'bwl_poll' ], $arr );
            }
        }
    }

    /*--- Initialization ---*/

    function init_bwl_poll_manager() {
        new BWL_Poll_Manager();
    }

    add_action( 'init', 'init_bwl_poll_manager' );

    /*---  TRANSLATION FILE ---*/

    load_plugin_textdomain( 'bwl-poll', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}