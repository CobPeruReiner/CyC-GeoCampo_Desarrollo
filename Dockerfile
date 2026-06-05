FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
  libzip-dev \
  zip \
  unzip \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  libpng-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install gd zip mysqli pdo pdo_mysql \
  && apt-get clean \
  && rm -rf /var/lib/apt/lists/*

# Configuración PHP para subida de archivos
RUN echo "upload_max_filesize=15M" > /usr/local/etc/php/conf.d/uploads.ini \
  && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini \
  && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/uploads.ini \
  && echo "max_execution_time=120" >> /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /app

EXPOSE 4007

CMD ["php", "-S", "0.0.0.0:4007", "-t", "/app"]