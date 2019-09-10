import { parseUrl } from 'query-string';
import { createMailTo, getErrorInformation, retryClicked } from '../gui';

it('Should show retry button when active', async () => {

  const button = document.createElement('button');
  button.id = 'retry_button';
  document.body.append(button);
  button.classList.add('hidden');

  const pendingClick = retryClicked().toPromise();

  // Should initially show the button, on subscription.
  expect(button.classList.contains('hidden')).toBeFalsy();

  // Click, so we trigger an retry event.
  button.click();

  // Check if the promise actually resolves.
  expect(await pendingClick).toBeDefined();

  // Should clean up and hide the button again.
  expect(button.classList.contains('hidden')).toBeTruthy();
});

it('getErrorInformation', () => {
  const table = document.createElement('table') as unknown as HTMLTableElement;
  table.innerHTML = `
        <tbody><tr>
            <th>Tijd</th>
            <td id="error_timestamp">2019-09-09T09:58:39.314Z</td>
        </tr>
        <tr>
            <th>Applicatie</th>
            <td>webauthn.test</td>
        </tr>
        <tr>
            <th>Request ID</th>
            <td>c91d0fd300303c3f6caf839222082b6a</td>
        </tr>
        <tr>
            <th>Foutcode</th>

            <td id="error_code" class="error_code" data-email="support@support.nl" data-email-link-text="E-mail foutmelding" data-email-subject="Ondersteuning voor foutmelding" data-email-intro="Er is een foutmelding opgetreden met de volgende gegevens:" data-email-closure="Met vriendelijke groet,">F253989</tr>

                    <tr>
                <th>User agent</th>
                <td>Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36</td>
            </tr>
                <tr>
            <th>IP-adres</th>
            <td>192.168.77.1</td>
        </tr>
    </tbody>
`;
  expect(getErrorInformation(table)).toEqual({
    closure: 'Met vriendelijke groet,',
    errorCode: 'F253989',
    intro: 'Er is een foutmelding opgetreden met de volgende gegevens:',
    linkText: 'E-mail foutmelding',
    subjectIntro: 'Ondersteuning voor foutmelding',
    url: 'support@support.nl',
    values: new Map([
      ['Tijd', '2019-09-09T09:58:39.314Z'],
      ['Applicatie', 'webauthn.test'],
      ['Request ID', 'c91d0fd300303c3f6caf839222082b6a'],
      ['Foutcode', 'F253989'],
      ['User agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36'],
      ['IP-adres', '192.168.77.1'],
    ]),
  });
});

it('createMailTo', () => {
  const link = createMailTo('This is the error', {
    closure: 'Met vriendelijke groet,',
    errorCode: 'F253989',
    intro: 'Er is een foutmelding opgetreden met de volgende gegevens:',
    linkText: 'E-mail foutmelding',
    subjectIntro: 'Ondersteuning voor foutmelding',
    url: 'support@support.nl',
    values: new Map([
      ['Tijd', '2019-09-09T09:58:39.314Z'],
      ['Applicatie', 'webauthn.test'],
      ['Request ID', 'c91d0fd300303c3f6caf839222082b6a'],
      ['Foutcode', 'F253989'],
      ['User agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36'],
      ['IP-adres', '192.168.77.1'],
    ]),
  });
  expect(link).toBeInstanceOf(HTMLAnchorElement);
  const parsed = parseUrl(link.href);
  expect(parsed.query.body).toEqual(`Er is een foutmelding opgetreden met de volgende gegevens:

Tijd: 2019-09-09T09:58:39.314Z
Applicatie: webauthn.test
Request ID: c91d0fd300303c3f6caf839222082b6a
Foutcode: F253989
User agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36
IP-adres: 192.168.77.1

This is the error

Met vriendelijke groet,
`);
  expect(parsed.query.subject).toEqual('Ondersteuning voor foutmelding F253989');
});
