Mobile Connect SDK
==================

Recommended Setup
-----------------

PHP 5.3.*
Composer 1.0
PHPUnit 4.8.*
Apache 2.*


Installation
------------

To install the SDK you will need to download and install Composer, visit their website for more details - https://getcomposer.org/

Execute the following to add the packagist link to your composer.json and complete the installation:

```
php composer.phar install
```

After Composer has successfully installed the dependencies for the Mobile Connect SDK you will have the SDK source code available for use within your application.

Usage
-----

We have supplied a demo app that demonstrates how the Mobile Connect SDK might be used to authenticate a user via their mobile device. This can be downloaded from the Packagist website as follows:

```
php composer.phar install
```


Development Tools
-----------------

You should be able to run all tests from the root of the SDK application as follows:

```
phpunit src/test/
```

It is recommended you run all tests to verify the dependencies are working and you target environment will work with the Mobile Connect SDK libraries.
