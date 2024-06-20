# Use the official PHP image from the Docker Hub
FROM php:7.4-apache

# Copy the PHP script to the Apache web server root
COPY index.php /var/www/html/

# Expose port 80 to the outside world
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
