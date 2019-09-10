import { assertElement, extractedTableValues, getStringAttribute } from '../domFunctions';

it('extractedTableValues', () => {
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

            <td id="error_code" data-email="support@support.nl" data-email-link-text="E-mail foutmelding" data-email-subject="Ondersteuning voor foutmelding" data-email-intro="Er is een foutmelding opgetreden met de volgende gegevens:" data-email-closure="Met vriendelijke groet,">F253989</tr>

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
  const values = extractedTableValues(table);
  expect(values).toEqual(new Map([
    ['Tijd', '2019-09-09T09:58:39.314Z'],
    ['Applicatie', 'webauthn.test'],
    ['Request ID', 'c91d0fd300303c3f6caf839222082b6a'],
    ['Foutcode', 'F253989'],
    ['User agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.132 Safari/537.36'],
    ['IP-adres', '192.168.77.1'],
  ]));
});

describe('getStringAttribute', () => {

  it('should exists', () => {
    const element = document.createElement('div');
    expect(() => getStringAttribute(element, 'foo')).toThrow();
  });

  it('should be a string', () => {
    const element = document.createElement('div');
    element.setAttribute('foo', 'bar');
    const stringAttribute = getStringAttribute(element);
    expect(stringAttribute('foo')).toEqual('bar');
  });

});

describe('assertElement', () => {

  it('should be an element', () => {
    expect(() => assertElement(null)).toThrow();
  });

  it('should be a string', () => {
    const element = document.createElement('div');
    expect(assertElement(element)).toBe(element);
  });

});
