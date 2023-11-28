Development environment
======================

The purpose of the development environment is only for running the different test and metric tools.

To get started, first setup the development environment. The development 'env' is a virtual machine. Every task described is run
from that machine.  

Requirements
-------------------
- Docker with Docker Compose
- OpenConext DevConf StepUp setup

Install
-------------------

### 1. Start OpenConext DevConf with WebAuthn in development mode 

``` cd /path/to/your/OpenConext-devconf/stepup ``` 
 
``` ./start-dev-env.sh webauthn:/path/to/your/Stepup-Webauthn ```

If everything goes as planned you can develop inside the virtual machine

``` docker exec -it stepup-webauthn-1 bash ```


### 2. Build frontend assets:

``` yarn ```

``` yarn encore dev ```

``` ./bin/console assets:install ```

### 3. Create configuration files

Copy and configure:
 
```cp .env.dist .env```

```cp config/openconext/parameters.yaml.dist config/openconext/parameters.yaml```

### 4. Create database
``` 
 bin/console doctrine:migrations:migrate
``` 

If everything goes as planned you can go to:

[https://webauthn.dev.openconext.local](https://webauthn.dev.openconext.local)

### Development

All frond-end logic is written in sass and typescript. You can run a watcher to update these automatically
