# fatfree-skeleton
Simple FATFREE customizable MVC, functionally patterned, arrangement and writing custom function automatically also PWA Integration.
## Installation
Just clone or download the scripts then install using composer
```html
composer install
```
## Get start
access www folder via browser
```text
http://localhost/fatfree-skeleton/www 
```
we have .htaccess file configure but if you run in nginx web service you can custom example config below
```conf
server {
    server_name _; 
    root /fatfree-skeleton/www/;    
    location / {
        index index.html index.htm index.php;        
        location ~ \.php$ {
            fastcgi_intercept_errors on;
            fastcgi_buffer_size 16k;
            fastcgi_buffers 4 16k;
            fastcgi_connect_timeout 600;
            fastcgi_send_timeout 600;
            fastcgi_read_timeout 600;
            fastcgi_pass php-fpm;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
        try_files $uri /index.php?$query_string;
    }
    location ~ \.(ini|log|sh|exe)$ {
        deny all;
    }
}
```
edit file www/app/setup.ini to run as development mode;
```ini
DEV.auto=true
DEV.model=true
DEV.minified=false
```
and to run as production mode;
```ini
DEV.auto=false
DEV.model=false
DEV.minified=true
```
