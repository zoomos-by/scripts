Модуль предназначен для 
- выгрузки всей базы товаров в csv файл
- обновления цен и статусов из ZOOMOS

Папку zoomos скопируйте в /bitrix/modules . 
В файле config.php укажите Ваш ключ к ZOOMOS API (ZMS_KEY) и ID информационного блока с товарами (PRODUCTS_BLOCK_ID).
Установите модуль в админке (Рабочий стол -> Настройки -> Настройки продукта -> Модули)
Файлы _zms_update.php и _export.php скопируйте в корень сайта.


http://yourshop.com/_export.php
http://yourshop.com/_update_zms.php



