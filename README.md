# FILENAME CACHE BUSTING
## WordPress Plugin
Move version query string of the source (css or js) into the filename, between the name and the extension of the file.  

When the plugin is activated, it writes a rewrite rule to the .htaccess file between markers.  
To perform this action the .htaccess file must be writeable otherwise you have to change it manually (see example below).

## .htaccess example
```
# BEGIN FILENAME CACHE BUSTING
<IfModule mod_rewrite.c>
	Options +FollowSymlinks
	RewriteEngine On
	RewriteCond %{REQUEST_FILENAME} !-f
	RewriteCond %{REQUEST_FILENAME} !-d
	RewriteRule ^(.+)\.(\d+)\.(js|css)$ $1.$3 [L]
</IfModule>
# END FILENAME CACHE BUSTING

# BEGIN WordPress
...
```