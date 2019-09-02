Stepup WebAuthn GSSP
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-Webauthn.svg?branch=master" alt="build:">
</a></br>

GSSP WebAuthn implementation.

Integrates https://github.com/web-auth/webauthn-framework as GSSP.

Development environment
======================

The purpose of the development environment is only for running the different test and metric tools.

To get started, first setup the development environment. The dev. env. is a virtual machine. Every task described is run
from that machine.  

Requirements
-------------------
- Vagrant 2.2.x
    - vagrant-hostsupdater (1.1.1.160, global, optional)
    - vagrant-vbguest (0.19.0, global)
- Virtualbox

Install
-------------------

### 1. Create virtual machine

``` cd homestead ``` 
 
``` composer install ```

Go to root of the project (```cd ..```) 

``` vagrant up ```

If everything goes as planned you can develop inside the virtual machine

``` vagrant ssh ```

### 2. Build frontend assets:

``` yarn install ```

``` yarn encore dev ``` or ``` yarn encore prod ``` for production 

``` ./bin/console assets:install ```

### 3. Create configuration files

Copy and configure:
 
```cp .env.vm .env```

```cp config/packages/parameters.yml.dist config/packages/parameters.yml```

### 4. Create database
``` 
 bin/console doctrine:migration:migrate
``` 

If everything goes as planned you can go to:

[https://webauthn.test](https://webauthn.test)

Debugging
-------------------
Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm and ```xon``` on cli env. 

Tests and metrics
======================

To run all required test you can run the following commands from the dev env:

```bash 
 composer test 
```

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Quick application deployment guide
=====================

### 1. Install dependencies

```
 yarn install
 composer install
```

### 2. Create configuration files

Copy and configure:
 
```.env.dist``` to  ```.env```
```config/packages/parameters.yml.dist``` to ```config/packages/parameters.yml```

### 4. Create env local file

```
 composer dump-env prod
```

### 5. Build public assets

```
 yarn encore prod
 ./bin/console assets:install
```

### 6. Create database and schema 

```
 bin/console doctrine:database:create
 bin/console doctrine:migration:migrate
```

### 7. Warm-up cache

```
APP_ENV=prod APP_DEBUG=0 php bin/console cache:clear
```

Version release instructions
=====================

Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management for more information on the release strategy used in Stepup projects.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
