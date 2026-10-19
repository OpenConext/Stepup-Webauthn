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

Note: Registering tokens on a .local domain (e.g. https://webauthn.dev.openconext.local) does not work in Firefox with
a self-signed certificate. `navigator.credentials.create()` throws `CredentialsContainer request is not allowed.`
      

### 2. Prepare the MetadataStatementService
We use the Fido JWS MDS Blob file to verify if Webauthn tokens are trustworthy. Here we follow the principle. If a 
token is verified by Fido and has at least a level 1 score. The token is good enough for us.

2 files need to be present in the `config/openconext/mds` folder. They are:

```config/openconext/mds/blob.jwt```
```config/openconext/mds/root.crt```

The `blob.jwt` file is signed by the FIDO Alliance by an certificate that is issued under the GlobalSign R46 root 
certificate `root.crt`.
The FIDO metadata blob (`blob.jwt`) containing the registry of metadata statements and the CA root certificate can be found here: https://fidoalliance.org/metadata/

The provided `.dist` files contain the CA certificate and a mds metadata. This result in a working application, but 
recently produced FIDO tokens may not work, because their metadata or attestation certificate is not present in the 
included `blob.jwt.dist` file. Download the latest blob from https://fidoalliance.org/metadata/ and replace the 
`blob.jwt` file with it to get the most recent FIDO metadata.

### 3. Build frontend assets:

``` yarn ```

``` yarn encore dev ```

``` ./bin/console assets:install ```

### 4. Create configuration files

Copy and configure:

```cp config/openconext/parameters.yaml.dist config/openconext/parameters.yaml```

If everything goes as planned you can go to:

[https://webauthn.dev.openconext.local](https://webauthn.dev.openconext.local)

