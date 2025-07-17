window.addEventListener('elementor/init', () => {
    elementor.hooks.addAction('panel/open_editor/widget/form', function(panel, model, view) {
        const oldWidgetsIds = window.fmeData.oldWidgetsIds || [];
        const currentWidgetId = model.get('id') || model.id;

        const customFieldKeys = [
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
            'maskcard_date'
        ];

        function removeCustomFields() {
            if (oldWidgetsIds.length > 0 && !oldWidgetsIds.includes(currentWidgetId)) {
                customFieldKeys.forEach(key => {
                    if (typeof model.removeControl === 'function') {
                        model.removeControl(key);
                    }

                    panel.$el.find('select option').filter(function() {
                        return jQuery(this).val() === key;
                    }).remove();
                });
            }
        }

        removeCustomFields();

        panel.$el.click(()=>{
            removeCustomFields();
        })

    });
});
