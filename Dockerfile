# Используем официальный образ PHP с Apache
FROM php:8.1-apache

# Установка необходимых PHP-расширений
RUN docker-php-ext-install pdo pdo_mysql

# Включение модуля mod_rewrite для Apache
RUN a2enmod rewrite

# Копирование файлов приложения в директорию Apache
COPY public/ /var/www/html/

# Копирование файлов API
COPY api/ /var/www/html/api/

# Установка прав доступа (опционально)
RUN chown -R www-data:www-data /var/www/html

# Настройка рабочей директории
WORKDIR /var/www/html

# Открытие порта 80
EXPOSE 80

# Команда запуска Apache в фореgrounде
CMD ["apache2-foreground"]
