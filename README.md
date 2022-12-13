# WordPress 5.8.6

## Docker 加速地址

```json
{
  "registry-mirrors": ["https://nh656izg.mirror.aliyuncs.com"]
}
```

## deploy and test

```
rsync -avzP /Users/yaoyingying/Projects/shareit/wordpress5/src/wp-content/plugins/woocommerce-gateway-payermax/ root@yaoin.net:/var/www/yaoin.net/wp-content/plugins/woocommerce-gateway-payermax/

```

## Sync to woocommerce-example

```
rsync -avzP ${HOME}/Projects/shareit/wordpress5/src/wp-content/plugins/woocommerce-gateway-payermax/ ${HOME}/Projects/shareit/woocommerce-example/src/wp-content/plugins/woocommerce-gateway-payermax/
```
