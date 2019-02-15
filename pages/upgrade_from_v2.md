# Upgrading Ultimate URLs from a Version Prior to v3.0.0

v3.0.0 of Ultimate URLs (a.k.a. _USU_) makes a **major** change to both the plugin's installation procedures and the way it handles the generation of its alternate URLs.  This document identifies the steps you will need to take to remove previous versions' **core-file** changes prior to any update to v3.0.0 or later.

**Note**: If your store _still_ runs on Zen Cart 1.5.0 or earlier, the notifications required below _will not operate properly_; you'll need to continue using your current installation of _USU_.

## /includes/functions/html_output.php

Within that module's `zen_href_link` function, find and remove the following section:

```php
// START alternative URLs patch
    global $altURLs;
	$link = null;
    if(isset($altURLs)) {
      $link = $altURLs->href_link($page, $parameters, $connection, $add_session_id, $static, $use_dir_ws_catalog);
      if($link !== null) return $link;
    }
    // END alternative URLs patch
```

Check the `global` directive at the very beginning of the function, ensuring that the variables' listed includes `zco_notifier`, adding that variable name if not present.


If the following code block is not present at the beginning of the function, just after the `global` directive, add the code block at that point.

```php
    $link = null;
    $zco_notifier->notify('NOTIFY_SEFU_INTERCEPT', array(), $link, $page, $parameters, $connection, $add_session_id, $static, $use_dir_ws_catalog);
    if($link !== null) return $link;
``` 

## /admin/includes/functions/html_output.php

Within that module's `zen_catalog_href_link` function, find and remove the following section:

```php
// START alternative URLs patch
    global $altURLs;
	$link = null;
    if(isset($altURLs)) {
      $link = $altURLs->href_link($page, $parameters, $connection, $add_session_id, $static, $use_dir_ws_catalog);
      if($link !== null) return $link;
    }
    // END alternative URLs patch
```

If the following code block is not present at the beginning of the function, just after the `function` statement, add the code block at that point.

```php
    global $zco_notifier;
    $link = null;
    $zco_notifier->notify('NOTIFY_SEFU_INTERCEPT_ADMCATHREF', array(), $link, $page, $parameters, $connection);
    if($link !== null) return $link;
```

## /admin/categories.php

Find this code-block towards the beginning of the module and remove it:

```php
  // If the action will affect the cache entries
  if (preg_match("/(insert|update|setflag)/i", $action)) {
    usu_reset_cache_data('true');
  }
```


## /admin/product.php

Find this code-block towards the beginning of the module and remove it:

```php
  // If the action will affect the cache entries
  if (preg_match("/(insert|update|setflag)/i", $action)) {
    usu_reset_cache_data('true');
  }
```