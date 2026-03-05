#!/bin/bash
# Modifie la config Nginx d'Azure pour pointer vers public/ (Symfony)
sed -i 's|root /home/site/wwwroot;|root /home/site/wwwroot/public;|g' /etc/nginx/sites-enabled/default

# Ajoute try_files pour le routage Symfony (après la ligne index)
sed -i '/index.*index\.php/a\        try_files $uri /index.php$is_args$args;' /etc/nginx/sites-enabled/default

nginx -s reload
