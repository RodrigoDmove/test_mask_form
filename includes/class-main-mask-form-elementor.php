<?php 
namespace Mask_Form_Elementor;

// Optionally, you can add an autoloader here (or use Composer)
class Mask_Form_Elementor {

    /**
     * The single instance of the class.
     *
     * @var Mask_Form_Elementor
     */
    protected static $_instance = null;

    /**
     * Instance of the Admin class.
     *
     * @var Mask_Form_Elementor_Admin
     */
    protected $admin;

    /**
     * Instance of the FieldTypes class.
     *
     * @var Mask_Form_Elementor_FieldTypes
     */
    protected $fieldtypes;

    /**
     * Returns the singleton instance.
     *
     * @return Mask_Form_Elementor
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Private constructor to enforce singleton.
     */
    private function __construct() {
        if ($this->check_requirements() ) {
            $this->setup();
            
            add_action( 'template_redirect', array( $this, 'migration_setup' ),1 );
            add_action( 'init', array( $this, 'text_domain_path_set' ) );
            add_action( 'activated_plugin', array( $this, 'mfe_plugin_redirection' ) );
            add_filter( 'plugin_action_links_' . plugin_basename( MFE_PLUGIN_FILE ), array( $this, 'mfe_pro_plugin_demo_link' ) );
            add_filter( 'plugin_row_meta', array( $this, 'mfe_plugin_row_meta' ), 10, 2 );
            
		}
    }


    public function mfe_plugin_row_meta($plugin_meta, $plugin_file){
        if ( plugin_basename( MFE_PLUGIN_FILE ) === $plugin_file ) {
            $row_meta = array(
                'Maintained By <a href="' . esc_url('https://coolplugins.net/?ref=mask&utm_source=cfkef_plugin&utm_medium=inside&utm_campaign=docs&utm_content=plugins-list') . '" aria-label="' . esc_attr__('View Form Mask Documentation', 'cool-formkit') . '" target="_blank">' . esc_html__('Cool Plugins', 'cool-formkit') . '</a>',
            );
    
            $plugin_meta = array_merge($plugin_meta, $row_meta);
        }
        return $plugin_meta;
    }
    

    public function mfe_plugin_redirection($plugin){
        if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' )) {
			return false;
		}

		if ( is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) ) {
			return false;
		}

		if ( $plugin == plugin_basename( MFE_PLUGIN_FILE ) ) {
			exit( wp_redirect( admin_url( 'admin.php?page=cool-formkit' ) ) );
		}	
    }

    public function mfe_pro_plugin_demo_link($link){
        $settings_link = '<a href="' . admin_url( 'admin.php?page=cool-formkit' ) . '">Cool FormKit</a>';
		array_unshift( $link, $settings_link );
		return $link;
    }

    public function text_domain_path_set(){
        load_plugin_textdomain( 'mask-form-elementor', false, dirname( plugin_basename( MFE_PLUGIN_FILE ) ) . '/languages/' );
    }

    public function migration_setup(){
        if( get_option( 'mfe_migration_done', false ) ) {
            return; // Migration already complete. Do nothing.
        }

        // Get legacy field types keys.
        $legacy_types = [
            'maskdate',
            'masktime',
            'maskdate_time',
            'maskcep',
            'maskphone',
            'masktelephone_with_ddd',
            'maskphone_with_ddd',
            'maskcpfcnpj',
            'maskcpf',
            'maskcnpj',
            'maskmoney',
            'maskip_address',
            'maskpercent',
            'maskcard_number',
            'maskcard_date',
        ];

        $all_widget_ids = $this->get_legacy_form_widgets_sitewide($legacy_types);

        if(count($all_widget_ids) > 0){
            update_option('mfe_old_widget_id', $all_widget_ids);
        }
        update_option( 'mfe_migration_done', true );
    }
    /**
     * Setup the plugin by loading classes and registering hooks.
     */
    public function setup() {
        $stored_version = get_option('mfe-v', false);

        if ( false === $stored_version || version_compare($stored_version, '4.1.0', '<=') ) {
            $this->load_old_deprecate_code();
        }

        if ( is_admin() ) {
			require_once MFE_PLUGIN_PATH . 'admin/feedback/admin-feedback-form.php';
		}

        require_once MFE_PLUGIN_PATH . 'includes/class-plugin-input-mask.php';
        MFE_Plugin::instance();

        require_once MFE_PLUGIN_PATH . '/includes/class-plugin-elementor-page.php';
        new MFE_Elementor_Page();

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

        
    public function get_legacy_form_widgets_sitewide( array $legacy_types ) {
        $args = array(
            'post_type'      => array('page', 'post'), // adjust as needed.
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => '_elementor_data',
                    'compare' => 'EXISTS',
                ),
            ),
        );
        $query = new \WP_Query( $args );
        $all_legacy_widgets = [];
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $post_id = get_the_ID();
                $data = get_post_meta( $post_id, '_elementor_data', true );
                if ( ! empty( $data ) ) {
                    $elements = json_decode( $data, true );
                    if ( is_array( $elements ) ) {
                        // Use your recursive method to get only legacy form widget IDs.
                        $widget_ids = $this->findLegacyFormWidgets( $elements, $legacy_types );
                        if ( ! empty( $widget_ids ) ) {
                            $all_legacy_widgets = array_merge( $all_legacy_widgets, $widget_ids );
                        }
                    }
                }
            }
            wp_reset_postdata();
        }
        return array_unique( $all_legacy_widgets );
    }
    
    /**
     * Recursively search for legacy form widgets in an array of elements.
     *
     * @param array $elements    Array of elements.
     * @param array $legacy_types Array of legacy field types.
     * @return array List of widget IDs that have a legacy field.
    */
    function findLegacyFormWidgets(array $elements, array $legacy_types) {
        $widget_ids = [];
        foreach ($elements as $element) {
            // Check if the element is a widget and of type form.
            if (
                isset($element['elType'], $element['widgetType']) &&
                $element['elType'] === 'widget' &&
                $element['widgetType'] === 'form'
            ) {
                    if (
                        isset($element['settings']['form_fields']) &&
                        is_array($element['settings']['form_fields'])
                    ) {
                        // Loop through each form field to check for a legacy field.
                        foreach ($element['settings']['form_fields'] as $field) {
                            if (
                                isset($field['field_type']) &&
                                in_array($field['field_type'], $legacy_types, true)
                            ) {
                                $widget_ids[] = $element['id'];
                                break;
                            }
                        }
                    }
                }
                
                // If the element has nested elements, search them recursively.
                if (isset($element['elements']) && is_array($element['elements']) && !empty($element['elements'])) {
                    $nested_ids = $this->findLegacyFormWidgets($element['elements'], $legacy_types);
                    $widget_ids = array_merge($widget_ids, $nested_ids);
                }
        }        
        return array_unique($widget_ids);
    }

    public function load_old_deprecate_code(){
        // Load additional classes.
        require_once MFE_PLUGIN_PATH . 'includes/class-mask-form-elementor-admin.php';
        require_once MFE_PLUGIN_PATH . 'includes/class-mask-form-elementor-fieldtypes.php';

        $this->admin      = new Mask_Form_Elementor_Admin();
        $this->fieldtypes = new Mask_Form_Elementor_FieldTypes();

        // Enqueue frontend scripts.
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

        // Enqueue Elementor editor/admin scripts.
        add_action( 'elementor/editor/after_enqueue_scripts', [ $this->admin, 'enqueue_admin_scripts' ] );

        // Add custom field types to Elementor Pro.
        add_filter( 'elementor_pro/forms/field_types', [ $this->fieldtypes, 'add_field_types' ] );

        // Register render callbacks for custom field types.
        $this->register_field_render_actions();
    }

    public function check_requirements() {
		if ( ! version_compare( PHP_VERSION, MFE_PHP_MINIMUM_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_php_version_fail' ] );
			return false;
		}

		if ( ! version_compare( get_bloginfo( 'version' ), MFE_WP_MINIMUM_VERSION, '>=' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_wp_version_fail' ] );
			return false;
		}

		// if ( is_plugin_active( 'cool-formkit-for-elementor-forms/cool-formkit-for-elementor-forms.php' ) ) {
		// 	return false;
		// }

		// if ( ! is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
		// 	add_action('admin_notices', array($this, 'admin_notice_missing_main_plugin'));
		// 	return false;
		// }

		return true;
	}


    	/**
	 * Display admin notice for PHP version failure.
	 */
	public function admin_notice_php_version_fail() {
		$message = sprintf(
			esc_html__( '%1$s requires PHP version %2$s or greater.', 'extensions-for-elementor-form' ),
			'<strong>Cool Formkit Lite</strong>',
			MFE_PHP_MINIMUM_VERSION
		);

		echo wp_kses_post( sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message ) );
	}

	/**
	 * Display admin notice for WordPress version failure.
	 */
	public function admin_notice_wp_version_fail() {
		$message = sprintf(
			esc_html__( '%1$s requires WordPress version %2$s or greater.', 'mask-form-elementor' ),
			'<strong>Cool Formkit Lite</strong>',
			MFE_WP_MINIMUM_VERSION
		);

		echo wp_kses_post( sprintf( '<div class="notice notice-error"><p>%1$s</p></div>', $message ) );
	}

    /**
	 * Show notice to enable elementor pro
	 */
	public function admin_notice_missing_main_plugin() {
		$message = sprintf(
			// translators: %1$s replace with Conditional Fields for Elementor Form & %2$s replace with Elementor Pro.
			esc_html__(
				'%1$s requires %2$s to be installed and activated.',
				'mask-form-elementor'
			),
			esc_html__( 'Mask Form Elementor', 'mask-form-elementor' ),
			esc_html__( 'Elementor Pro', 'mask-form-elementor' ),
			); 
		printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', esc_html( $message ) );
		deactivate_plugins( plugin_basename( MFE_PLUGIN_FILE ) );
	}

    /**
     * Enqueue frontend scripts.
     */
    public function enqueue_scripts() {
        $post_id = get_the_ID();
        $post    = get_post($post_id);
    
        // Check if Elementor Pro is active and if the current page is built with Elementor.
        $using_elementor_pro = false;
        if ( defined('ELEMENTOR_PRO_VERSION') ) {
            $using_elementor_pro = \Elementor\Plugin::$instance->db->is_built_with_elementor( $post_id );
        }
    
        // Initialize widget_found as false.
        $widget_found = false;
        $widget_ids = get_option( 'mfe_old_widget_id', array() );
        
        // Get the Elementor data stored in the post meta.
        $elementor_data = get_post_meta( $post_id, '_elementor_data', true );
        
        if ( is_array( $widget_ids ) && ! empty( $widget_ids ) ) {
            // Check in Elementor data first.
            if ( $elementor_data ) {
                foreach ( $widget_ids as $widget_id ) {
                    if ( strpos( $elementor_data, $widget_id ) !== false ) {
                        $widget_found = true;
                        break;
                    }
                }
            }
            // Optionally, you can also check the post content.
            if ( ! $widget_found && $post ) {
                $content = $post->post_content;
                foreach ( $widget_ids as $widget_id ) {
                    if ( strpos( $content, $widget_id ) !== false ) {
                        $widget_found = true;
                        break;
                    }
                }
            }
        }       

        if ( $widget_found || !$using_elementor_pro ) {
            wp_enqueue_script(
                'jquery.mask.min.js',
                MFE_PLUGIN_URL . 'assets/deprecate_js/jquery.mask.min.js',
                [ 'jquery' ],
                '1.0',
                true
            );
            wp_enqueue_script(
                'maskformelementor.js',
                MFE_PLUGIN_URL . 'assets/deprecate_js/maskformelementor.js',
                [ 'jquery' ],
                '1.0',
                true
            );
        }
    }

    /**
     * Register render actions for custom field types.
     */
    protected function register_field_render_actions() {
        $field_types = $this->fieldtypes->get_field_types();
        foreach ( array_keys( $field_types ) as $field_type ) {
            add_action( "elementor_pro/forms/render_field/{$field_type}", [ $this->fieldtypes, 'render_field' ], 10, 3 );
        }
    }

    public static function mfe_activate(){
		update_option( 'mfe-v', MFE_VERSION );
		update_option( 'mfe-type', 'FREE' );
		update_option( 'mfe-installDate', gmdate( 'Y-m-d h:i:s' ) );
	}

	public static function mfe_deactivate(){
	}
}

// Initialize the plugin.
Mask_Form_Elementor::instance();

register_activation_hook( MFE_PLUGIN_FILE, array( 'Mask_Form_Elementor\Mask_Form_Elementor', 'mfe_activate' ) );
register_deactivation_hook( MFE_PLUGIN_FILE, array( 'Mask_Form_Elementor\Mask_Form_Elementor', 'mfe_deactivate' ) );