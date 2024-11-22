jQuery(document).ready(function($) {
    // AJAX mentés
    $('.save-row').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const row = button.closest('tr');
        const productId = button.data('product');
        const country = $('#country-selector').val();
        
        const data = {
            action: 'wcsm_save_product_data',
            security: wcsm_vars.nonce,
            product_id: productId,
            country: country,
            stock: row.find('.stock-input').val(),
            price: row.find('.price-input').val()
        };

        button.prop('disabled', true).text('Saving...');

        $.post(wcsm_vars.ajax_url, data, function(response) {
            if (response.success) {
                button.text('Saved!').addClass('updated');
                setTimeout(() => {
                    button.text('Save').removeClass('updated').prop('disabled', false);
                }, 2000);
            } else {
                alert('Error saving data');
                button.prop('disabled', false).text('Save');
            }
        });
    });

    // Bulk actions
    $('#bulk-action-button').on('click', function(e) {
        e.preventDefault();
        const action = $('#bulk-action-selector').val();
        const country = $('#country-selector').val();
        const selectedProducts = $('.product-select:checked').map(function() {
            return $(this).val();
        }).get();

        if (!selectedProducts.length) {
            alert('Please select products');
            return;
        }

        const data = {
            action: 'wcsm_bulk_update',
            security: wcsm_vars.nonce,
            bulk_action: action,
            country: country,
            products: selectedProducts,
            stock: $('#bulk-stock').val(),
            price: $('#bulk-price').val()
        };

        $.post(wcsm_vars.ajax_url, data, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error performing bulk action');
            }
        });
    });

    // Search/Filter
    let timer;
    $('#product-search').on('keyup', function() {
        clearTimeout(timer);
        timer = setTimeout(function() {
            $('#product-filter-form').submit();
        }, 500);
    });

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