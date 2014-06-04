yii2-firebirddb - Firebird Adapter for Yii 2.x
============================

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

* Unpack the adapter to `protected/extensions`
* In your `protected/config/main.php`, add the following:

```php
<?php
...
  'components' => array(
  ...
    'db' => array(
      'connectionString'=>'firebird:dbname=localhost:C:\Path\To\Db\MyDB.GDB',
      'class' => 'ext.YiiFirebird.CFirebirdConnection',
    ),
    ...
  ),
...
```

Restriction
-----------
Some restrictions imposed by Database:
* Rename tables
* Using DDL and DML statement in the same transaction and the same table. (Ex: Create table and insert).

Thanks to
---------

@idlesign, @robregonm, @edgardmessias, @mr-rfh, @mlorentz75
