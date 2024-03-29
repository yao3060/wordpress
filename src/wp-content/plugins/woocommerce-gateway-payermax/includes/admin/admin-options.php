<style>
    .woocommerce_payermax_icon_wrapper .flex {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .woocommerce_payermax_icon img {
        max-height: 75px;
        border: 1px solid #ccc;
        background: #fff;
    }
</style>

<?php

/**@var WC_Gateway_PayerMax $this */

echo '<h2>' . esc_html($this->get_method_title());
wc_back_link(__('Return to payments', 'woocommerce'), admin_url('admin.php?page=wc-settings&tab=checkout'));
echo '</h2>';
echo wp_kses_post(wpautop($this->get_method_description()));
?>

<table class="form-table">

    <!-- upload payermax icon -->
    <tr valign="top" class="woocommerce_payermax_icon_wrapper">
        <th scope="row" class="titledesc">
            <label for="woocommerce_payermax_icon"><?php PayerMax::_e('ICON', 'woocommerce-gateway-payermax'); ?></label>
        </th>
        <td class="forminp">
            <div class="flex">
                <?php
                $image_id = get_option($this::ICON_ID_KEY, 0);
                if ($image = wp_get_attachment_image_url($image_id, 'full')) : ?>
                    <a href="#" class="woocommerce_payermax_icon" data-title="<?php PayerMax::_e('Upload ICON', 'woocommerce-gateway-payermax'); ?>">
                        <img src="<?php echo esc_url($image) ?>" />
                    </a>
                    <a href="#" class="remove_woocommerce_payermax_icon"><?php PayerMax::_e('Remove', 'woocommerce-gateway-payermax'); ?></a>
                    <input type="hidden" name="<?php echo $this::ICON_ID_KEY; ?>" value="<?php echo absint($image_id) ?>">
                <?php else : ?>
                    <a href="#" class="button woocommerce_payermax_icon" data-title="<?php PayerMax::_e('Upload ICON', 'woocommerce-gateway-payermax'); ?>">
                        <?php _e('Upload ICON', 'woocommerce-gateway-payermax'); ?>
                    </a>
                    <a href="#" class="remove_woocommerce_payermax_icon" style="display:none"><?php PayerMax::_e('Remove', 'woocommerce-gateway-payermax'); ?></a>
                    <input type="hidden" name="<?php echo $this::ICON_ID_KEY; ?>" value="" />
                <?php endif; ?>
            </div>

            <p class="description"><?php _e('ICON image should small than 512px * 256px', 'woocommerce-gateway-payermax'); ?></p>
        </td>
    </tr>

    <?php echo $this->generate_settings_html($this->get_form_fields(), false); ?>

    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="woocommerce_payermax_endpoint">Available Payment Method</label>
        </th>
        <td class="forminp">
            <fieldset>
                <legend class="screen-reader-text"><span>Available Payment Method</span></legend>
                <select class="select" disabled="disabled">
                    <option value="">SELECT</option>
                    <option value="CARD" selected>CARD</option>
                </select>
            </fieldset>
        </td>
    </tr>

    <tr valign="top">
        <th scope="row" class="titledesc">
            <label for="woocommerce_payermax_endpoint">
                <?php PayerMax::_e('Debug', 'woocommerce-gateway-payermax'); ?>
            </label>
        </th>
        <td class="forminp">
            <?php
            echo sprintf(
                PayerMax::__('<code>WP_DEBUG</code> is <code>%s</code>, if enabled, plugin will store trade info into <code>%s</code>.', 'woocommerce-gateway-payermax'),
                WP_DEBUG ? 'true' : 'false',
                wc_get_log_file_path(self::ID)
            );
            ?>
        </td>
    </tr>
</table>


<script>
    jQuery(function($) {
        // on upload button click
        var woocommerce_payermax_icon = jQuery('.woocommerce_payermax_icon');

        woocommerce_payermax_icon.on('click', function(event) {
            event.preventDefault(); // prevent default link click and page refresh

            const button = $(this);
            const imageId = button.next().next().val();

            const customUploader = wp.media({
                title: button.data('title'), // modal window title
                library: {
                    type: 'image'
                },
                multiple: false
            });

            customUploader.on('select', function() { // it also has "open" and "close" events
                const attachment = customUploader.state().get('selection').first().toJSON();
                console.log('attachment', attachment.height, attachment.width);
                if (attachment.width > 512 || attachment.height > 256) {
                    alert('Image too large for ICON');
                    return;
                }
                button.removeClass('button').html('<img src="' + attachment.url + '">'); // add image instead of "Upload Image"
                button.next().show(); // show "Remove image" link
                button.next().next().val(attachment.id); // Populate the hidden field with image ID
            });

            // already selected images
            customUploader.on('open', function() {
                if (imageId) {
                    const selection = customUploader.state().get('selection')
                    attachment = wp.media.attachment(imageId);
                    attachment.fetch();
                    selection.add(attachment ? [attachment] : []);
                }
            });

            customUploader.open();

        });
        // on remove button click
        $('body').on('click', '.remove_woocommerce_payermax_icon', function(event) {
            event.preventDefault();
            const button = $(this);
            button.next().val(''); // emptying the hidden field
            button.hide().prev().addClass('button').html(woocommerce_payermax_icon.data('title')); // replace the image with text
        });
    });
</script>
