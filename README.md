Stepup WebAuthn GSSP
===================

<a href="#">
    <img src="https://travis-ci.org/OpenConext/Stepup-Webauthn.svg?branch=master" alt="build:">
</a></br>

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

- Only allows WebAuthn Attestation Statements with a certificate trust path. 
- Should match trusted certificates should be stored on disk.

The directory can be configured inside the parameters.yml file [config/packages/parameters.yml](config/packages/parameters.yml)

Debugging
-------------------
Xdebug is configured when provisioning your development Vagrant box. 
It's configured with auto connect IDE_KEY=phpstorm and ```xon``` on cli env. 

Installation
======================

See one of the following guides:

[Development guide](docs/development.md)

[Production installation](docs/deployment.md)

Tests and metrics
======================

To run all required test you can run the following commands from the dev env:

```bash 
 composer test 
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
