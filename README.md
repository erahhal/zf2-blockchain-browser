zf2-blockchain-browser
======================

Introduction
------------
This is a Zend Framework 2 based blockchain browser that uses bitcoind RPC to import
data into a SQL database.

The DB could easily use over 100GB when loaded, so make sure you have enough disk space.
Currently, the code doesn't handle multisig transactions well, so the import code
will crash at about block 170k.

To import, run 

Installation
------------


Web Server Setup
----------------

### Nginx Setup

To setup apache, setup a virtual host to point to the public/ directory of the
project and you should be ready to go! It should look something like below:

server {
    listen       80;
    server_name  <domain>;
    root    /var/www/<domain>/public;
    index index.php index.html;

    # max file size
    client_max_body_size 32m;
    client_body_buffer_size 128k;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        # fastcgi_pass    127.0.0.1:9000;
	fastcgi_pass    unix:/var/run/php5-fpm.sock;
        fastcgi_index   index.php;
        fastcgi_param   SCRIPT_FILENAME    $document_root$fastcgi_script_name;
        include         fastcgi_params;
    }
}
