# Haigha: Alice fixtures for tables

<img src="http://3.bp.blogspot.com/-9_Jt3gdf6fU/T-28_RUpoFI/AAAAAAAACBg/xgAxK-2fgLY/s1600/MarchHare.jpg" style="width: 100%" />

[Alice](https://github.com/nelmio/alice) is an *awesome* database fixtures library.
It works with Doctrine out-of-the-box, but if you don't use the Doctrine ORM, you'll need custom persisters...

This is where **Haigha** comes in:

> *Haigha lets you use Alice directly with database tables!*

## Features

* Supports all standard Alice functionality (ranges, optional data, references, inheritence, etc)
* Supports Faker data providers
* Supports any PDO connection
* No need to write classes, directly persist from yml to your sql database

## Example fixture file

Haigha uses Alice to load fixture files, so the format is identical ([Details](https://github.com/nelmio/alice)). The only thing to keep in mind is that you use tablenames instead of classnames. Prefix your tablenames with `table.`. For example, if your tablename is called `user`, you use it like this:
```yaml
table.group:
  group_random_users:
    name: Random users

table.user:
  random_user{0..9}:
    group_id: @group_random_users
    username: <userName()>
    firstname: <firstName()>
    lastname: <lastName()>
    password: <password()>
    email: <email()>
```

## How to use Haigha in your application

Simply add the following to your `require` or `require-dev` section in your [composer.json](http://getcomposer.org) and run `composer update`:
```json
"require": {
  "linkorb/haigha": "~1.0"
}
```

You can now use Haigha in your applications, or use the included command-line tool to load fixtures into your database:

## Command-line usage

You can load schema to database by:

### Database url

A full URL containing username, password, hostname and dbname:

```
./vendor/bin/haigha fixtures:load examples/random_users.yml mysql://username:password@hostname/dbname
```

### Just a dbname

In this case [linkorb/database-manager](https://github.com/linkorb/database-manager) is used for loading database connection details (server, username, password, etc) from .conf files (read project readme for more details).

In a nutshell - you must have a `database_name.conf` file at `/share/config/database/` as described at [database-manager's documentation](https://github.com/linkorb/database-manager#database-configuration-files).

```bash
./vendor/bin/haigha fixtures:load examples/random_users.yml dbname
```

## Library usage:

You can use Haigha in your own application like this:

```php
// Instantiate a new Alice loader
$loader = new Nelmio\Alice\Fixtures\Loader();

// Add the Haigha instantiator
$instantiator = new Haigha\TableRecordInstantiator();
$loader->addInstantiator($instantiator);

// Load (Haigha) objects from a Alice yml file
$objects = $loader->load('examples/random_users.yml');

// Instantiate the Haigha PDO persister, and pass a PDO connection
$persister = new PdoPersister($pdo);

// Persist the Haigha objects on the PDO connection
$persister->persist($objects);
```

## License

MIT (see [LICENSE.md](LICENSE.md))

## Brought to you by the LinkORB Engineering team

<img src="http://www.linkorb.com/d/meta/tier1/images/linkorbengineering-logo.png" width="200px" /><br />
Check out our other projects at [linkorb.com/engineering](http://www.linkorb.com/engineering).

Btw, we're hiring!
