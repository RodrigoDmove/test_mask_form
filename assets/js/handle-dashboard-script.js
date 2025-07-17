(function($) {
    $(document).ready(function() {
        let links = $('.wp-submenu-wrap').find('a[href="admin.php?page=cool-formkit"]');
        if (links.length > 1) {
            links.not(':first').remove();
        }
        
        let matchedWrapper = null; // Store the correct .cfk-wrapper element

        $('.cfk-p-name h2').each(function() {
            let titleText = $(this).text().trim();

            if (titleText === 'Input Mask Elementor Form Fields') {
                // Find the closest .cfk-wrapper for the matching heading
                matchedWrapper = $(this).closest('.cfk-wrapper');
            }
        });

        if (matchedWrapper) {
            // Count total .cfk-wrapper elements
            let totalWrappers = $('.cfk-wrapper').length;

            if (totalWrappers > 1) {
                // Remove all .cfk-wrapper elements that are NOT the matched one
                $('.cfk-wrapper').not(matchedWrapper).remove();
            }
        }
    });
})(jQuery);
