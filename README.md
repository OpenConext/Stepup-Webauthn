Stepup WebAuthn GSSP
===================

[![test-integration](https://github.com/OpenConext/Stepup-Webauthn/actions/workflows/test-integration.yml/badge.svg)](https://github.com/OpenConext/Stepup-Webauthn/actions/workflows/test-integration.yml)
</br>

GSSP WebAuthn implementation.

Integrates https://github.com/web-auth/webauthn-framework as GSSP.

Configuration
-------------------

### WebAuthn Creation/request profiles

For this application default creation/request profiles are created. The application
now only support 'default' profile. [config/packages/webauthn.yaml](config/packages/webauthn.yaml)

You can override the default one, see all configuration option on
[webauthn-framework](https://github.com/web-auth/webauthn-framework/blob/master/doc/symfony/index.md) 

### Trust store [src/Service/InMemoryAttestationCertificateTrustStore.php](src/Service/InMemoryAttestationCertificateTrustStore.php)

- Off all the different type of WebAuthn Attestation Statements [https://www.w3.org/TR/webauthn/#sctn-attestation-types]() the trust store only accepts Attestation Statements with a certificate trust path.
- Should match trusted certificates should be stored on disk.

The directory can be configured inside the parameters.yml file [config/packages/parameters.yml](config/packages/parameters.yml)

Installation
======================

See one of the following guides:

[Development guide](docs/development.md)

[Production installation](docs/deployment.md)

Setting the desired Symfony application environment
===================================================
There are 2 ways you can influence the desired Symfony application environment.

1. Set the `app_env` parameter in `config/openconext/parameters.yaml` to `dev`, `test` or `prod`
2. Override the `app_env` param by providing an environment variable named `APP_ENV`

- The default value for the application environment will be `prod`
- Do not try to use a .env file to override the `app_env` param. That file will not be evaluated by Symfony as we decided not use the DotEnv component.


Tests and metrics
======================

To run all required test you can run the following commands from the dev env:

```bash 
 composer check 
```

Every part can be run separately. Check "scripts" section of the composer.json file for the different options.

Version release instructions
=====================

Please read: https://github.com/OpenConext/Stepup-Deploy/wiki/Release-Management for more information on the release strategy used in Stepup projects.

Other resources
======================

 - [Developer documentation](docs/index.md)
 - [Issue tracker](https://www.pivotaltracker.com/n/projects/1163646)
 - [License](LICENSE)
