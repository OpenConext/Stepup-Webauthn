#!/usr/bin/env bash
echo "pulling the images"
docker compose pull
echo "starting the images"
docker compose up -d
echo "intialising the environment"
docker compose exec -T webauthn bash -c '
  cp /var/www/html/config/openconext/parameters.yaml.dist /var/www/html/config/openconext/parameters.yaml && \
  composer install --prefer-dist -n -o --no-scripts && \
  ./bin/console assets:install --verbose && \
  ./bin/console cache:clear && \
  chown -R www-data:www-data /var/www/html/var/ && \
  ./bin/console doctrine:migrations:migrate --no-interaction
'
