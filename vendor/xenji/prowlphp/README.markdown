# ProwlPHP

[![Build Status](https://secure.travis-ci.org/xenji/ProwlPHP.png)](http://travis-ci.org/xenji/ProwlPHP)
[![endorse](http://api.coderwall.com/xenji/endorsecount.png)](http://coderwall.com/xenji)

## License
The project source forked from Fenric had no license information. My modifications, which is a rewrite of 80-90% of
the original source, are published under Apache 2.0 License. Read the LICENSE file for further information.

## Requirements
- PHP 5.3
- cURL with SSL support.

## Important
Versions below 1.0.0 support PHP 5.2, from release 1.0.0 on PHP 5.3 will be supported exclusively. You can use the 0.3.3 branch,
if you really need support for PHP 5.2, but I will only handle bug related issues on that branch, no feature requests.

## Using Composer

To install ProwlPHP with composer you simply need to create a *composer.json* in your project root and add:

``` json
{
    "require": {
        "xenji/ProwlPHP": ">=1.0.2"
    }
}
```

Then run

``` bash
$ wget -nc http://getcomposer.org/composer.phar
$ php composer.phar install
```

You have now ProwlPHP installed in *vendor/xenji/ProwlPHP*

And an handy autoload file to include in you project in *vendor/.composer/autoload.php*

## PEAR Channel
ProwlPHP can be installed from PEAR.

```
xenji@xenjibook:~/pear channel-discover pear.xenji.com

xenji@xenjibook:~/pear install xenji/Prowl
```

## Documentation
This project is badly documented at the moment. The wiki at Fenric's repo has been switched off so I have to write the docs from the scratch. Please visit the wiki pages
from time to time to get updates. In the meantime you can take a look at the example.php, located in the examples directory.
This file shows the main functionality, but no extra stuff.

**Please remember to you use your own API keys - in the examples and in the unit tests. Thank you for not spamming me.**

## Changelog
See the issues tab and the corresponding milestones for changelog information.
