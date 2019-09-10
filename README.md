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

``` yarn encore dev ```

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

### Development

All frond-end logic is written in sass and typescript. You can run a watcher to update these automatically

Configuration
-------------------

### WebAuthn Creation/request profiles

For this application default creation/request profiles are created. The application
now only support 'default' profile. [config/packages/webauthn.yaml](config/packages/webauthn.yaml)

You can override the default one, see all configuration option on
[webauthn-framework](https://github.com/web-auth/webauthn-framework/blob/master/doc/symfony/index.md) 

### Trust store [src/Service/InMemoryAttestationCertificateTrustStore.php](src/Service/InMemoryAttestationCertificateTrustStore.php)

All WebAuthn request Attestation Statement with a certificate trust path. All trusted certificates should be stored on disk.
The directory can be configured inside the parameters.yml file [config/packages/parameters.yml](config/packages/parameters.yml)

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

Quick application production deployment guide
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

```composer dump-env prod```

### 3. Build public assets

```
 yarn encore prod
 ./bin/console assets:install
```

### 4. Create database and schema 

```
 bin/console doctrine:database:create
 bin/console doctrine:migration:migrate
```

### 5. Warm-up cache

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
