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

### 2. Prepare the MetadataStatementService
We use the Fido JWS MDS Blob file to verify if Webauthn tokens are trustworthy. Here we follow the principle. If a 
token is verified by Fido and has at least a level 1 score. The token is good enough for us.

2 files need to be present in the `config/openconext/mds` folder. They are:

```config/openconext/mds/blob.jwt```
```config/openconext/mds/fido2-mds.cer```

The blob containing the registry of metadata statements can be found here: https://fidoalliance.org/metadata/ (see the Obtaining blob section)

The Blob file is signed by the FIDO Alliance. To verify the signature we need the appropriate certificate. This certificate is not downloaded on demand, but we
also track it. This is the location the cert can be found on the fido page linked in the paragraph above. Also in the Obtaining blob section. 

The provided dist files should result in a working application. But might not work with brand-new tokens.

### 3. Build frontend assets:

``` yarn ```

``` yarn encore dev ```

``` ./bin/console assets:install ```

### 4. Create configuration files

Copy and configure:

```cp config/openconext/parameters.yaml.dist config/openconext/parameters.yaml```

If everything goes as planned you can go to:

[https://webauthn.dev.openconext.local](https://webauthn.dev.openconext.local)

Adding a language
-------------------

1. Add `translations/messages.<locale>.yml`, copying the keys from `messages.en.yml`.
2. Service name locale matching is handled by `Surfnet\GsspBundle\Service\ServiceName\ServiceNameResolver`,
   which lives in the `surfnet/stepup-gssp-bundle` dependency, not in this repo. It matches
   locales by their first 2 characters (e.g. `nl_NL` -> `nl`). This assumes a 2-letter ISO 639-1
   language code. A 3-letter code (ISO 639-2, e.g. `fil`) will not match correctly, changing
   that behavior requires a change in `surfnet/stepup-gssp-bundle`, not this repo.
3. The resolver's fallback order is: exact locale match -> `en` -> first available
   `DisplayName` in the SAML mdui:UIInfo extension. The `en` fallback is hardcoded in
   gssp-bundle, not config-driven, and not overridable from this repo.

