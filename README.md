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

## Relations
MaphperLoader supports all Maphper relationships including: One, Many, and ManyMany

### One
Here is some sample JSON for a One Relationship
```json
{
    "blog" : {
        "type" : "database",
        "table" : "blog",
        "primaryKey" : "id",
        "relations" : [
            {
                "name" : "author",
                "to" : "author",
                "type" : "one",
                "localKey" : "authorId",
                "foreignKey" : "id"
            }
        ]
    },
    "author" : {
        "type" : "database",
        "table" : "author",
        "primaryKey" : "id"
    }
}
```
A one relationship has the following properties
* `name` - the name of the relation when being accessed
* `to` - the maphper being connected to
* `type` - the type of relation
* `localKey` - the key in the current Maphper being linked from
* `foreignKey` - the key in the other Maphper being linker to

### Many
```json
{
    "blog" : {
        "type" : "database",
        "table" : "blog",
        "primaryKey" : "id"
    },
    "author" : {
        "type" : "database",
        "table" : "author",
        "primaryKey" : "id",
        "relations" : [
            {
                "name" : "blogs",
                "to" : "blog",
                "type" : "many",
                "localKey" : "id",
                "foreignKey" : "authorId"
            }
        ]
    }
}
```
A many relation has the same properties of a One relation except the `type` is set to `many`

### ManyMany
```json
{
    "actors" : {
        "type" : "database",
        "table" : "actor",
        "primaryKey" : "aid",
        "relations" : [
            {
                "name" : "movies",
                "to" : "movies",
                "type" : "ManyMany",
                "intermediate" : "cast",
                "intermediateKey" : "movieId",
                "foreignKey" : "mid"
            }
        ]
    },
    "movies" : {
        "type" : "database",
        "table" : "movie",
        "primaryKey" : "mid",
        "relations" : [
            {
                "name" : "actors",
                "to" : "actors",
                "type" : "ManyMany",
                "intermediate" : "cast",
                "intermediateKey" : "actorId",
                "foreignKey" : "aid"
            }
        ]
    },
    "cast" : {
        "type" : "database",
        "table" : "cast",
        "primaryKey" : ["movieId", "actorId"]
    }
}
```
A ManyMany relation must have an intermediate Maphper to connect the two Maphpers
Both Maphpers being connected must also have the ManyMany relationship defined
In the above example that is the cast Maphper

The ManyMany Relation has the following properties
* `name` - the name of the relation when being accessed
* `to` - the Maphper being connected to
* `type` - the type of relation, in this case "ManyMany"
* `intermediate` - the intermediate Maphper
* `intermediateKey` - the key of the column in the intermediate maphper that connects to the other Maphper
* `foreignKey` - must be the primary key of the other Maphper
