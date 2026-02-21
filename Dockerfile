FROM php:8.2-cli

WORKDIR /app

# Copy service files
COPY . /app

# Install common tools and composer (optional)
RUN apt-get update \
  && apt-get install -y --no-install-recommends unzip git curl ca-certificates \
  && rm -rf /var/lib/apt/lists/* \
  && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# If a composer.json exists, install deps
RUN if [ -f composer.json ]; then composer install --no-dev --prefer-dist; fi

# Default port (Render sets $PORT env var at runtime)
ENV PORT 8080

# Start the built-in PHP server listening on $PORT
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT} -t ."]
