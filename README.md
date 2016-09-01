# Maphper-Loader
An Easy Way to Create Maphper Instances

## Dependencies
This library has dependencies on Dice and Maphper with can both be found at [Level-2](https://github.com/Level-2)

## Purpose
This project was started to make it easier to create instances of the Maphper class found at Level-2 with little configuration
Each Loader just requires a file to be passed to it and you can call the `getMaphper` method to get the maphper you want

## Use
Lets take this first example from Maphper's README

```php
$pdo = new PDO('mysql:dbname=maphpertest;host=127.0.0.1', 'username', 'password');
$blogSource = new \Maphper\DataSource\Database($pdo, 'blog', 'id');
$blogs = new \Maphper\Maphper($blogSource);
```

This can be changed to have a file called `config.json` with the maphper config info
config.json
```json
{
    "blogs" : {
        "type" : "database",
        "table" : "blog",
        "primaryKey" : "id"
    }
}
```
And then the php becomes
```php
$loader = new \MaphperLoader\Json("config.json", new \Dice\Dice());
$blogs = $loader->getMaphper("blogs");
```

MaphperLoader uses dice to handle dependencies such as PDO. You just need to add a rule for PDO and you are all set.
