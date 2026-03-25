FROM php:8.2-apache

# Set the web root used by Apache in this image.
WORKDIR /var/www/html

# Copy website files into the container.
COPY . /var/www/html

# Ensure writable persistent data directory exists.
RUN mkdir -p /var/lib/basquenland \
    && chown -R www-data:www-data /var/lib/basquenland \
    && chmod -R 775 /var/lib/basquenland

EXPOSE 80
