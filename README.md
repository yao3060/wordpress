# WordPress 5.8.6

## Docker 加速地址

```json
{
  "registry-mirrors": ["https://nh656izg.mirror.aliyuncs.com"]
}
```

## WP Order Object

```
Automattic\WooCommerce\Admin\Overrides\Order Object
(
    [refunded_line_items:protected] =>
    [customer_id] =>
    [status_transition:protected] =>
    [data:protected] => Array
        (
            [parent_id] => 0
            [status] => pending
            [currency] => CNY
            [version] => 6.9.4
            [prices_include_tax] =>
            [date_created] => WC_DateTime Object
                (
                    [utc_offset:protected] => 28800
                    [date] => 2022-11-21 06:27:25.000000
                    [timezone_type] => 1
                    [timezone] => +00:00
                )

            [date_modified] => WC_DateTime Object
                (
                    [utc_offset:protected] => 28800
                    [date] => 2022-11-21 06:27:25.000000
                    [timezone_type] => 1
                    [timezone] => +00:00
                )

            [discount_total] => 0
            [discount_tax] => 0
            [shipping_total] => 0
            [shipping_tax] => 0
            [cart_tax] => 0
            [total] => 45.00
            [total_tax] => 0
            [customer_id] => 1
            [order_key] => wc_order_oNfB4e8ocTITI
            [billing] => Array
                (
                    [first_name] => YAO
                    [last_name] => YINGYING
                    [company] =>
                    [address_1] => 襄阳南路489号4F
                    [address_2] => 金环大厦
                    [city] => 徐汇区
                    [state] => CN10
                    [postcode] => 1111
                    [country] => CN
                    [email] => yao3060@gmail.com
                    [phone] => 18601660362
                )

            [shipping] => Array
                (
                    [first_name] => YAO
                    [last_name] => YINGYING
                    [company] =>
                    [address_1] => 襄阳南路489号4F
                    [address_2] => 金环大厦
                    [city] => 徐汇区
                    [state] => CN10
                    [postcode] => 1111
                    [country] => CN
                    [phone] =>
                )

            [payment_method] => payermax
            [payment_method_title] => PayerMax
            [transaction_id] =>
            [customer_ip_address] => 172.20.0.1
            [customer_user_agent] => Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/107.0.0.0 Safari/537.36
            [created_via] => checkout
            [customer_note] =>
            [date_completed] =>
            [date_paid] =>
            [cart_hash] => 6e0e990b73ec17e814a03c650e986521
        )

    [items:protected] => Array
        (
        )

    [items_to_delete:protected] => Array
        (
        )

    [cache_group:protected] => orders
    [data_store_name:protected] => order
    [object_type:protected] => order
    [id:protected] => 19
    [changes:protected] => Array
        (
        )

    [object_read:protected] => 1
    [extra_data:protected] => Array
        (
        )

    [default_data:protected] => Array
        (
            [parent_id] => 0
            [status] =>
            [currency] =>
            [version] =>
            [prices_include_tax] =>
            [date_created] =>
            [date_modified] =>
            [discount_total] => 0
            [discount_tax] => 0
            [shipping_total] => 0
            [shipping_tax] => 0
            [cart_tax] => 0
            [total] => 0
            [total_tax] => 0
            [customer_id] => 0
            [order_key] =>
            [billing] => Array
                (
                    [first_name] =>
                    [last_name] =>
                    [company] =>
                    [address_1] =>
                    [address_2] =>
                    [city] =>
                    [state] =>
                    [postcode] =>
                    [country] =>
                    [email] =>
                    [phone] =>
                )

            [shipping] => Array
                (
                    [first_name] =>
                    [last_name] =>
                    [company] =>
                    [address_1] =>
                    [address_2] =>
                    [city] =>
                    [state] =>
                    [postcode] =>
                    [country] =>
                    [phone] =>
                )

            [payment_method] =>
            [payment_method_title] =>
            [transaction_id] =>
            [customer_ip_address] =>
            [customer_user_agent] =>
            [created_via] =>
            [customer_note] =>
            [date_completed] =>
            [date_paid] =>
            [cart_hash] =>
        )

    [data_store:protected] => WC_Data_Store Object
        (
            [instance:WC_Data_Store:private] => WC_Order_Data_Store_CPT Object
                (
                    [internal_meta_keys:protected] => Array
                        (
                            [0] => _parent_id
                            [1] => _status
                            [2] => _currency
                            [3] => _version
                            [4] => _prices_include_tax
                            [5] => _date_created
                            [6] => _date_modified
                            [7] => _discount_total
                            [8] => _discount_tax
                            [9] => _shipping_total
                            [10] => _shipping_tax
                            [11] => _cart_tax
                            [12] => _total
                            [13] => _total_tax
                            [14] => _customer_id
                            [15] => _order_key
                            [16] => _billing
                            [17] => _shipping
                            [18] => _payment_method
                            [19] => _payment_method_title
                            [20] => _transaction_id
                            [21] => _customer_ip_address
                            [22] => _customer_user_agent
                            [23] => _created_via
                            [24] => _customer_note
                            [25] => _date_completed
                            [26] => _date_paid
                            [27] => _cart_hash
                            [28] => _customer_user
                            [29] => _order_key
                            [30] => _order_currency
                            [31] => _billing_first_name
                            [32] => _billing_last_name
                            [33] => _billing_company
                            [34] => _billing_address_1
                            [35] => _billing_address_2
                            [36] => _billing_city
                            [37] => _billing_state
                            [38] => _billing_postcode
                            [39] => _billing_country
                            [40] => _billing_email
                            [41] => _billing_phone
                            [42] => _shipping_first_name
                            [43] => _shipping_last_name
                            [44] => _shipping_company
                            [45] => _shipping_address_1
                            [46] => _shipping_address_2
                            [47] => _shipping_city
                            [48] => _shipping_state
                            [49] => _shipping_postcode
                            [50] => _shipping_country
                            [51] => _shipping_phone
                            [52] => _completed_date
                            [53] => _paid_date
                            [54] => _edit_lock
                            [55] => _edit_last
                            [56] => _cart_discount
                            [57] => _cart_discount_tax
                            [58] => _order_shipping
                            [59] => _order_shipping_tax
                            [60] => _order_tax
                            [61] => _order_total
                            [62] => _payment_method
                            [63] => _payment_method_title
                            [64] => _transaction_id
                            [65] => _customer_ip_address
                            [66] => _customer_user_agent
                            [67] => _created_via
                            [68] => _order_version
                            [69] => _prices_include_tax
                            [70] => _date_completed
                            [71] => _date_paid
                            [72] => _payment_tokens
                            [73] => _billing_address_index
                            [74] => _shipping_address_index
                            [75] => _recorded_sales
                            [76] => _recorded_coupon_usage_counts
                            [77] => _download_permissions_granted
                            [78] => _order_stock_reduced
                        )

                    [internal_data_store_key_getters:protected] => Array
                        (
                            [_download_permissions_granted] => download_permissions_granted
                            [_recorded_sales] => recorded_sales
                            [_recorded_coupon_usage_counts] => recorded_coupon_usage_counts
                            [_order_stock_reduced] => stock_reduced
                            [_new_order_email_sent] => email_sent
                        )

                    [meta_type:protected] => post
                    [object_id_field_for_meta:protected] =>
                    [must_exist_meta_keys:protected] => Array
                        (
                        )

                )

            [stores:WC_Data_Store:private] => Array
                (
                    [coupon] => WC_Coupon_Data_Store_CPT
                    [customer] => WC_Customer_Data_Store
                    [customer-download] => WC_Customer_Download_Data_Store
                    [customer-download-log] => WC_Customer_Download_Log_Data_Store
                    [customer-session] => WC_Customer_Data_Store_Session
                    [order] => WC_Order_Data_Store_CPT
                    [order-refund] => WC_Order_Refund_Data_Store_CPT
                    [order-item] => WC_Order_Item_Data_Store
                    [order-item-coupon] => WC_Order_Item_Coupon_Data_Store
                    [order-item-fee] => WC_Order_Item_Fee_Data_Store
                    [order-item-product] => WC_Order_Item_Product_Data_Store
                    [order-item-shipping] => WC_Order_Item_Shipping_Data_Store
                    [order-item-tax] => WC_Order_Item_Tax_Data_Store
                    [payment-token] => WC_Payment_Token_Data_Store
                    [product] => WC_Product_Data_Store_CPT
                    [product-grouped] => WC_Product_Grouped_Data_Store_CPT
                    [product-variable] => WC_Product_Variable_Data_Store_CPT
                    [product-variation] => WC_Product_Variation_Data_Store_CPT
                    [shipping-zone] => WC_Shipping_Zone_Data_Store
                    [webhook] => WC_Webhook_Data_Store
                    [report-revenue-stats] => Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore
                    [report-orders] => Automattic\WooCommerce\Admin\API\Reports\Orders\DataStore
                    [report-orders-stats] => Automattic\WooCommerce\Admin\API\Reports\Orders\Stats\DataStore
                    [report-products] => Automattic\WooCommerce\Admin\API\Reports\Products\DataStore
                    [report-variations] => Automattic\WooCommerce\Admin\API\Reports\Variations\DataStore
                    [report-products-stats] => Automattic\WooCommerce\Admin\API\Reports\Products\Stats\DataStore
                    [report-variations-stats] => Automattic\WooCommerce\Admin\API\Reports\Variations\Stats\DataStore
                    [report-categories] => Automattic\WooCommerce\Admin\API\Reports\Categories\DataStore
                    [report-taxes] => Automattic\WooCommerce\Admin\API\Reports\Taxes\DataStore
                    [report-taxes-stats] => Automattic\WooCommerce\Admin\API\Reports\Taxes\Stats\DataStore
                    [report-coupons] => Automattic\WooCommerce\Admin\API\Reports\Coupons\DataStore
                    [report-coupons-stats] => Automattic\WooCommerce\Admin\API\Reports\Coupons\Stats\DataStore
                    [report-downloads] => Automattic\WooCommerce\Admin\API\Reports\Downloads\DataStore
                    [report-downloads-stats] => Automattic\WooCommerce\Admin\API\Reports\Downloads\Stats\DataStore
                    [admin-note] => Automattic\WooCommerce\Admin\Notes\DataStore
                    [report-customers] => Automattic\WooCommerce\Admin\API\Reports\Customers\DataStore
                    [report-customers-stats] => Automattic\WooCommerce\Admin\API\Reports\Customers\Stats\DataStore
                    [report-stock-stats] => Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\DataStore
                )

            [current_class_name:WC_Data_Store:private] => WC_Order_Data_Store_CPT
            [object_type:WC_Data_Store:private] => order
        )

    [meta_data:protected] => Array
        (
            [0] => WC_Meta_Data Object
                (
                    [current_data:protected] => Array
                        (
                            [id] => 80
                            [key] => is_vat_exempt
                            [value] => no
                        )

                    [data:protected] => Array
                        (
                            [id] => 80
                            [key] => is_vat_exempt
                            [value] => no
                        )

                )

        )

)

```
