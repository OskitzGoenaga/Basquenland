FROM php:8.2-apache

# Set the web root used by Apache in this image.
WORKDIR /var/www/html

# Copy website files into the container.
COPY . /var/www/html

# Ensure the app can create/update leaderboard CSV data.
RUN mkdir -p /var/www/html/data \
    && chown -R www-data:www-data /var/www/html/data \
    && chmod -R 775 /var/www/html/data

EXPOSE 80
