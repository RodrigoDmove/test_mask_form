<?php
namespace Mask_Form_Elementor;

defined( 'ABSPATH' ) || die( 'The silence is god!' );

class Mask_Form_Elementor_FieldTypes {

    /**
     * Get the custom field types.
     *
     * @return array
     */
    public function get_field_types() {
        return [
            'maskdate'               => 'Máscara: Data',
            'masktime'               => 'Máscara: Horário',
            'maskdate_time'          => 'Máscara: Data e Horário',
            'maskcep'                => 'Máscara: CEP',
            'maskphone'              => 'Máscara: Telefone sem DDD',
            'masktelephone_with_ddd' => 'Máscara: Telefone',
            'maskphone_with_ddd'     => 'Máscara: Telefone com nono digito',
            'maskcpfcnpj'            => 'Máscara: Cpf ou Cnpj',
            'maskcpf'                => 'Máscara: CPF',
            'maskcnpj'               => 'Máscara: CNPJ',
            'maskmoney'              => 'Máscara: Monetário',
            'maskip_address'         => 'Máscara: Endereço de IP',
            'maskpercent'            => 'Máscara: Porcentagem',
            'maskcard_number'        => 'Máscara: Número Cartão de Crédito',
            'maskcard_date'          => 'Máscara: Validade Cartão de Crédito',
        ];
    }

    public function get_all_form_widget_ids( array $elements ) {
        $widget_ids = [];
        foreach ( $elements as $element ) {
            if (
                isset( $element['elType'], $element['widgetType'] ) &&
                $element['elType'] === 'widget' &&
                $element['widgetType'] === 'form'
            ) {
                $widget_ids[] = $element['id'];
            }
            if ( isset( $element['elements'] ) && is_array( $element['elements'] ) && ! empty( $element['elements'] ) ) {
                $widget_ids = array_merge( $widget_ids, $this->get_all_form_widget_ids( $element['elements'] ) );
            }
        }
        return array_unique( $widget_ids );
    }

    /**
     * Add custom field types to Elementor Pro.
     *
     * @param array $types Existing field types.
     * @return array
     */
    public function add_field_types( $types ) {
        return array_merge( $types, $this->get_field_types() );
    }    

    /**
     * Render the custom field.
     *
     * @param array  $item       Field settings.
     * @param int    $item_index Field index.
     * @param object $el         Elementor form widget instance.
     */
    public function render_field( $item, $item_index, $el ) {
        // Remove the "mask" prefix to generate a CSS class.
        $mask_class = substr( $item['field_type'], 4 );

        $el->set_render_attribute( 'input' . $item_index, 'type', 'tel' );
        $el->add_render_attribute( 'input' . $item_index, 'class', 'elementor-field-textual ' . $mask_class );

        if ( ! empty( $item['field_label'] ) ) {
            $el->add_render_attribute( 'input' . $item_index, 'placeholder', $item['field_label'] );
        }

        echo '<input size="1" ' . $el->get_render_attribute_string( 'input' . $item_index ) . '>';
    }
}
