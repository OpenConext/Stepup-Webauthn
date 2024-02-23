describe('template spec', () => {
  //implements https://chromedevtools.github.io/devtools-protocol/tot/WebAuthn/
  function addVirtualAuthenticator () {
    return Cypress.automation("remote:debugger:protocol", {
      command: "WebAuthn.enable",
      params: {},
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
            automaticPresenceSimulation: true,
          },
        },
      }).then((result) => {
        console.log("WebAuthn.addVirtualAuthenticator", result);
        return result.authenticatorId;
      });
    });
  }

  it('Registers a FIDO2 token using the Demo SP', () => {

    cy.visit('https://webauthn.dev.openconext.local/')
    addVirtualAuthenticator()

    // cy.contains('Registreer gebruiker').click()
    //
    // cy.contains('Demo service provider ACS')
  })
})