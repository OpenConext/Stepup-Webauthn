import { deSerializedPublicKeyCredentialCreationOptions } from './functions';
import { SerializedPublicKeyCredentialCreationOptions } from './models';

declare const publicKeyOptions: SerializedPublicKeyCredentialCreationOptions;
debugger;
const publicKey: PublicKeyCredentialCreationOptions = deSerializedPublicKeyCredentialCreationOptions(publicKeyOptions);

navigator.credentials.create({ publicKey })
  .then(
    (data) => {
      console.log(data);
      // const publicKeyCredential = {
      //   id: data.id,
      //   type: data.type,
      //   rawId: arrayToBase64String(new Uint8Array(data.rawId)),
      //   response: {
      //     clientDataJSON: arrayToBase64String(new Uint8Array(data.response.clientDataJSON)),
      //     attestationObject: arrayToBase64String(new Uint8Array(data.response.attestationObject)),
      //   },
      // };

    debugger;
      // window.location = '/request_post?data='+btoa(JSON.stringify(publicKeyCredential));
    },
    (error) => {
      console.log(error); // Example: timeout, interaction refused...
    },
  );
