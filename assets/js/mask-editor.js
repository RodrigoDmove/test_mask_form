jQuery(document).on('click','#cfef_elementor_review_dismiss',(event)=>{
    jQuery(".cfef_elementor_review_notice").hide();
    const btn=jQuery(event.target);
    const nonce=btn.data('nonce');
    const url=btn.data('url');

    jQuery.ajax({
        type: 'POST',
        // eslint-disable-next-line no-undef
        url: url, // Set this using wp_localize_script
        data: {
            action: 'fme_elementor_review_notice',
            cfef_notice_dismiss: true,
            nonce: nonce
        },
        success: (response) => {
            btn.closest('.elementor-control').remove();
        },
        error: (xhr, status, error) => {
            console.log(xhr.responseText);
            console.log(error);
            console.log(status);
        }
    });
});

