(function ($) {
    $(function () {
        function resetConverter(){
            $('#a2wc_converter').show();
            $('#a2wc_get_products').show();
            $('#a2wc_convert_products').hide();
            $('#a2wc_convert_logs').hide();
            $('#a2wc_convert_logs .logs').html('');
        }

        function infoLog(text) {
            $('#a2wc_convert_logs .logs').append('<div class="a2wc-info">' + text + '</div>');
        }

        function warnLog(text) {
            $('#a2wc_convert_logs .logs').append('<div class="a2wc-warn">' + text + '</div>');
        }

        function errorLog(text) {
            $('#a2wc_convert_logs .logs').append('<div class="a2wc-error">' + text + '</div>');
        }

        $('#a2wc_system').change(function () {
            if($(this).val()){
                resetConverter();
            }else{
                $('#a2wc_converter').hide();
            }
        });

        $('#a2wc_convert_logs .clear-logs').click(function(){
            $('#a2wc_convert_logs .logs').html('');
        });

        $('#a2wc_get_products').click(function(){
            $('#a2wc_convert_logs').show();
            $('#a2wc_get_products').attr('disabled', 'disabled');
            infoLog('get products...');
            $.post(ajaxurl, { 'action':  'a2wc_get_products', 'converter': $('#a2wc_system').val() }).done(function (response) {
                var json = $.parseJSON(response);
                if(json.state==='error'){
                    errorLog('Error: '+json.message);
                }else{
                    a2w_products_to_convert = json.items;
                    infoLog(json.items.length + ' products ready to convert');
                    if(json.items.length > 0) {
                        $('#a2wc_get_products').hide();
                        $('#a2wc_convert_products').show();
                        $('#a2wc_convert_products').val('Convert '+json.items.length+' product(s)');
                        $('#a2wc_convert_products').removeAttr('disabled');
                    } else{
                        $('#a2wc_get_products').show();
                        $('#a2wc_convert_products').hide();
                    }
                }
                $('#a2wc_get_products').removeAttr('disabled');
            }).fail(function (xhr, status, error) {
                errorLog('Error: ' + error);
            });
        });

        $('#a2wc_convert_products').click(function() {
            $('#a2wc_convert_products').attr('disabled', 'disabled');
            const products_to_convert = a2w_products_to_convert.map(function(p){
                return { 'action':  'a2wc_convert_product', 'converter': $('#a2wc_system').val(), 'product_id': p.product_id, 'external_product_id': p.external_product_id }
            });
            a2wc_convert(products_to_convert);
        });

        let a2w_products_to_convert = [];

        function a2wc_convert(products_to_convert) {
            if (products_to_convert.length > 0) {
                var data = products_to_convert.shift();
                $.post(ajaxurl, data).done(function (response) {
                    const json = $.parseJSON(response);
                    if(json.state === 'error'){
                        errorLog(data.product_id+'#Product('+data.external_product_id+') Error: ' + json.message);
                    } else {
                        infoLog(data.product_id+'#Product('+data.external_product_id+') Ok');
                    }
                    a2wc_convert(products_to_convert);
                }).fail(function (xhr, status, error) {
                    errorLog(data.product_id+'#Product('+data.external_product_id+') Error: ' + error);
                    a2wc_convert(products_to_convert);
                });
            } else {
                infoLog('Done!');
            }
        }
    });

})(jQuery);