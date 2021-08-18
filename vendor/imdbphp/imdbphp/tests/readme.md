About
-----

These tests mostly retrieve pages from IMDb and assert the parsing.

The goal is to ensure that if the imdb site layout changes we'll be able to easily tell what has broken.

The tests should attempt to cover all possibilities for a method(the data is there, the data isn't there, the data is partially there)

The tests should try to be resilient to changes to the underlying data in imdb. It is a publicly editable database so things can change and will change for newer films.

Because each method could download a new page we try to re-use pages where possible (the matrix is used for most title page tests)

Running the tests
-----------------
If you've got composer - from the root of the repo:
```
composer install
composer test
```

Otherwise get the PHPUnit phar from [github](https://github.com/sebastianbergmann/phpunit)
```
php phpunit.phar -c tests/phpunit.xml tests
```
