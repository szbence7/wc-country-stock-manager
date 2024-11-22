jQuery(document).ready(function($) {
    // Handle bulk update actions
    $('.wcsm-bulk-update').on('click', function(e) {
        e.preventDefault();
        var value = $('#wcsm-bulk-value').val();
        var type = $(this).data('type');
        
        $('.wcsm-' + type + '-input').val(value);
    });

    // Handle country selection
    $('#wcsm-add-country').on('change', function() {
        var countryCode = $(this).val();
        if (countryCode) {
            // Add new country row logic here
        }
    });
});