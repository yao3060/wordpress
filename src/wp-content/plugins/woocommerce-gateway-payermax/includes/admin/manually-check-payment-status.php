<p class="form-field form-field-wide">
    <button id="check_payermax_payment_status" type="button" class="button button-primary" data-action="check_payermax_payment_status" data-order_id="<?php echo $order->get_id(); ?>" data-text="<?php _e('Check PayerMax Payment Status', 'woocommerce-gateway-payermax'); ?>" style="width: 100%;">
        <?php _e('Check PayerMax Payment Status', 'woocommerce-gateway-payermax'); ?>
    </button>
</p>
<script>
    jQuery(document).ready(function() {

        jQuery('#check_payermax_payment_status').on('click', function(e) {
            e.preventDefault();
            const target = e.target;
            const dataset = target.dataset;

            var request = jQuery.ajax({
                url: ajaxurl,
                method: "POST",
                data: dataset,
                dataType: "json",
                beforeSend: function(xhr) {
                    target.classList.add('button-disabled');
                    target.innerHTML = `LOADING`;
                }
            });

            request.done(function(response) {
                // refresh current page if success
                if (response.code === "APPLY_SUCCESS" && response.data.status === "SUCCESS") {
                    window.location.reload();
                }
            });

            request.fail(function(jqXHR, textStatus) {
                alert("Request failed: " + jqXHR.responseText);
            });

            request.always(() => {
                target.classList.remove('button-disabled');
                target.innerHTML = dataset.text
            })
        });
    });
</script>
