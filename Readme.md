# Abaca lightweight php framework

**This project was first developed in early 2010, not maintained any more.**

**Just a copy of old project. DO NOT use this project in production.**

A lightweight php framework for web projects with many small modules. It suggests some rules to orgnize your project files.

Served 20+ dynamic million requests per day on a 50+ servers cluster across 3 data centers in production for a teenager social network in China.

A typical file structure is:

/var/www
	|- **Abaca**
	|- com.somesite
		|- **abaca**
			|- config
			|- class
				|- User.class.php
				|- File.class.php
				|- Record.class.php
				|- ...
			|- cache
			|- log
			|- view
		|- www
			|- *index.php*
		|- mobile
		|- **abaca.php**
	|- com.othersite.www
		|- **abaca**
		|- **abaca.php**
		|- *index.php*
		
> **Abaca** is the framework directory, **abaca** is your project shared components directory, **abaca.php** is your project basic setting file. *index.php* requires abaca.php to use the framework.

## Settings in abaca.php

### LocalPath

Required, directory of **abaca.php **

```php
define ( 'LocalPath', str_replace ( '\\', '/', dirname ( __FILE__ ) ) . '/' );
```

### AbacaPath

Required, the directory where Abaca is placed.

```php
define ( 'LocalPath', str_replace ( '\\', '/', dirname ( __FILE__ ) ) . '/' );
```

### Some more settings

1. RemotePath, optional, often set as the url the site serves
2. LocalClassPath, optinoal, defauts to abaca/class, where php classes are
3. CachePath, optional, defauts to abaca/cache, where runtime temporary files are
4. SlowPageTime, optional, defauts to 1 (second). If a page comsumes more time than this, a log will be gernerated

and more can be found in *functions/abaca.function.php*.
