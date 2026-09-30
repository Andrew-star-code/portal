# Только для локальной проверки: Apache + PHP, как на сервере.
FROM php:8.3-apache
RUN a2enmod rewrite headers expires deflate \
 && sed -ri 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf \
 && printf 'upload_max_filesize=600M\npost_max_size=610M\nmax_input_time=900\nmax_execution_time=900\n' > /usr/local/etc/php/conf.d/uploads.ini
