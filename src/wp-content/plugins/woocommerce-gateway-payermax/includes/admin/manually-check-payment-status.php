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
                beforeSend: (xhr) => {
                    target.classList.add('button-disabled');
                    target.innerHTML = `LOADING`;
                }
            });

            request.done((response) => {
                if (response.code === "APPLY_SUCCESS") {
                    target.innerHTML = `<span title="${dataset.text}">${response.data.status}</span>`;
                    if (response.data.status === "SUCCESS" || response.data.status === "CLOSED") {
                        window.location.reload();
                    } else {
                        target.classList.remove('button-disabled');
                    }
                }
            });

            request.fail((jqXHR, textStatus) => {
                alert("Request failed: " + jqXHR.responseText);
                target.classList.remove('button-disabled');
                target.innerHTML = dataset.text
            });
        });
    });
</script>
