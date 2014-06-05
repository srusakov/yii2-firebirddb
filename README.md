yii2-firebirddb - Firebird Adapter for Yii 2.x
==============================================

This is an updated version of the adapter YiiFirebird originally posted by
idlesign. It has been enhanced to be compatible with yii2.

This version is marked 2.0

Requirements
------------

* PHP 5.4
* PDO_Firebird extension enabled.
* Firebird 2.5 (not tested on previous versions)
* Yii 1.1.9


Installation
------------

* Modify your composer.json:

```json
...
"require": {
    "srusakov/firebirddb": "*"
	},
  "repositories":[{
      "type":"git",
      "url":"http://github.com/srusakov/yii2-firebirddb",
  }]
...
```

* Modify your common/config/main.php:

```php
return [
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'firebird:dbname=HOSTNAME:DATABASENAME.fdb;charset=UTF8',
            'username' => 'sysdba',
            'password' => 'masterkey',
            'charset' => 'utf8',
            'pdoClass' => 'srusakov\firebirddb\PDO',
            'schemaMap' => [
                                'firebird' => 'srusakov\firebirddb\Schema', // FireBird
                            ],
        ],
]
```

Restriction
-----------
Some restrictions imposed by Database:
* Rename tables
* Using DDL and DML statement in the same transaction and the same table. (Ex: Create table and insert).

Caution!
--------
This driver is not well tested in production ebvironment! Use it at your own risk!

Thanks to
---------

@idlesign, @robregonm, @edgardmessias, @mr-rfh, @mlorentz75
