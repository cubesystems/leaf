Leaf PHP framework
==============================

## System requirements

* 32/64-bit BSD/Linux platform
* Apache 2.0+ with mod_rewrite module
* PHP 5.3+ and at least following modules:
  * gd
  * iconv
  * multibyte support (mbstring)
  * curl
  * tidy
* MySQL 5.0+

## Running the application in dev mode
1. create local config.php outside webroot and setup database access
2. setup base database

```
php cli/seed.php
```
3. start dev server

```
./cli/dev_server.sh
```

## Running the application on production server
setup LEAF_PRODUCTION for CLI:

```
echo "export LEAF_PRODUCTION=1" >> ~/.profile
```

update crontab, so it begins with following lines:

```
PATH=/etc:/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin::/usr/local/sbin
LEAF_PRODUCTION=1
MAILTO=my@email.com
```

Set/export following environment variables for production server:

```
LEAF_PRODUCTION=1
```

Or if host is on skynet, edit "~/cgi-bin/php" to contain LEAF_PRODUCTION
variable before "exec" line:

```
export PHPRC
export LEAF_PRODUCTION=1
exec /usr/local/bin/php-cgi
```


## Setup in NGINX
leafapplication.conf

```
	# XXX denotes server specific options
	
  # server configuration
  server {
		listen XXX;
		server_name XXX;


		root /path/to/leaf/application/webroot;

    # leaf specific rewrite rules
    location / {
      index  index.php index.html;

      if (-e $request_filename) {
        break;
      }

      if ($request_uri ~ "^/(admin|images|styles|xml_templates|shared|files|REVISION)(.*)$") {
        break;
      }

      if ($request_uri ~ "^/favicon\.ico$") {
        break;
      }

      rewrite ^/(.+)$ /index.php?objects_path=$1 last;
    }
    
    # deny cli webaccess (imo, cli script dir webaccess is a security threat in leaf)
    location ^~ /cli/ {
      deny all;
    }

    # deny tpl
    location ~* (\.tpl)$ {
      deny all;
    }
    
    # here comes all other ngix stuff as fastCGI, status, auth etc

}

```
