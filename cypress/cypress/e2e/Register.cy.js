describe('template spec', () => {
  function addVirtualAuthenticator () {
    return Cypress.automation("remote:debugger:protocol", {
      command: "WebAuthn.enable",
      params: {"enableUI":true},
    }).then((result) => {
      console.log("WebAuthn.enable", result);
      return Cypress.automation("remote:debugger:protocol", {
        command: "WebAuthn.addVirtualAuthenticator",
        params: {
          options: {
            protocol: "ctap2",
            transport: "usb",
            hasResidentKey: true,
            hasUserVerification: true,
            isUserVerified: true,
          },
        },
      }).then((result) => {
        console.log("WebAuthn.addVirtualAuthenticator", result);
        return result.authenticatorId;
      });
    });
  }

  it('Registers a FIDO2 token using the Demo SP', () => {
    //Set the document.domain to "webauthn.dev.openconext.local", the virtual authenticator reads the document.domain and sets the RPID accordingly,
    //if the non-https version is not visited first the authenticator will use "openconext.local" as an RPID
    //this consequently leads to an RPIDhash missmatch with the sever RPID.(https://github.com/cypress-io/cypress/issues/2193)
    cy.visit('https://webauthn.dev.openconext.local/')
    // console.log('domain when starting = ' + document.domain)
    // const domain = document.location.href.match(/\/\/([^:/]+)/)[1]
    // document.domain = domain
    // console.log('domain after modifying = ' + document.domain)
    // cy.document().its(domain).should('eq', 'webauthn.dev.openconext.local')
    addVirtualAuthenticator()

    cy.contains('Registreer gebruiker').click()
  })
})