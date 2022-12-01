<?php

/**
 * The sidebar containing the main widget area.
 *
 * @package storefront
 */

if (!is_active_sidebar('sidebar-1')) {
    return;
}
?>

<div id="secondary" class="widget-area" role="complementary">
    <pre style="padding: 5px;">
<?php
echo 'get_user_locale:' . get_user_locale() . PHP_EOL;
echo 'get_locale:' . get_locale() . PHP_EOL;
echo 'WPML:' . apply_filters('wpml_current_language', NULL) . PHP_EOL;
echo 'PayerMax:' . PayerMax_Helper::get_payermax_language(get_user_locale()) . PHP_EOL;
?>
</pre>

    <?php dynamic_sidebar('sidebar-1'); ?>
</div><!-- #secondary -->
