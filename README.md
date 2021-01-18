# FILENAME CACHE BUSTING (WordPress Plugin)
Move version query string of the source (css or js) into the filename, between the name and the extension of the file.  
Admin or external scripts are excluded.

When the plugin is activated, it writes a rewrite rule to the .htaccess file between markers.  
To perform this action the .htaccess file must be writeable otherwise you have to change it manually (see example below).

## Resource example
Before: `//example.com/wp-content/themes/my-theme/style.css?ver=5.0.4`  
After: `//example.com/wp-content/themes/my-theme/style.5.0.4.css`

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

## Tips / Avoid caching issues
Use file modification time (`filemtime`) as a version number in your theme to ensure the browser reloads the resource when it changes (cache invalidation).

### Example
`function.php`
```php
function addScripts(){
  $js = '/js/my-script.js';
  $version = filemtime(get_stylesheet_directory() . $js);
  wp_register_script('my-script', get_stylesheet_directory_uri().$js, null, $version, true);
  wp_enqueue_script( 'my-script' );
}
add_action( 'wp_enqueue_scripts', 'addScripts' );
```