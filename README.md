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
no configuration required to a web service that can read .htaccess files, but if you run in nginx add configuration below
```conf
server {
    listen       80;
    server_name  example.com www.example.com;
    root /fatfree-skeleton/www/;    
    # exp for window  root D:/fatfree-skeleton/www/
    location / {
        index index.html index.htm index.php;      
        try_files $uri /index.php?$query_string;
    }
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
    location ~ \.(hta|ini|env)$ {
        # deny all traffic to config files
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
