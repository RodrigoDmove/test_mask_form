<?php
namespace Mask_Form_Elementor;

defined( 'ABSPATH' ) || die( 'The silence is god!' );

class Mask_Form_Elementor_Admin {

    /**
     * Constructor.
     */
    public function __construct() {
        // Additional admin initialization if needed.
    }

    /**
     * Enqueue admin/editor scripts.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_script(
            'maskef-admin',
            MFE_PLUGIN_URL . 'assets/deprecate_js/admin.js',
            [ 'jquery' ],
            time(),
            true
        );

        // Pass field types to the admin script.
        wp_localize_script(
            'maskef-admin',
            'maskFields',
            [
                'fields' => array_keys( $this->get_field_types() ),
            ]
        );
    }

    /**
     * Retrieve custom field types.
     *
     * @return array
     */
    protected function get_field_types() {
        // We instantiate the FieldTypes class to retrieve its field definitions.
        $fieldtypes = new Mask_Form_Elementor_FieldTypes();
        return $fieldtypes->get_field_types();
    }
}
