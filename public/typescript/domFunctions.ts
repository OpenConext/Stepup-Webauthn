import { curry } from 'ramda';

/**
 *
 * @example
 * <table>
 *   <tr>
 *     <th>Name</th>
 *     <td>Foo</td>
 *   </tr>
 *   <tr>
 *     <th>Last name</th>
 *     <td>Bar</td>
 *   </tr>
 * </table
 *
 * Return set with:
 *  Name: Foo
 *  Last name: Bar
 *
 */
export let extractedTableValues = (errorTable: HTMLTableElement) => {
  const headers = errorTable.getElementsByTagName('th');
  const values = new Map();
  for (const header of Array.from(headers)) {
    const value = header.parentElement!.getElementsByTagName('td')[0].textContent;
    values.set(header.textContent, typeof value === 'string' ? value.trim() : '');
  }
  return values;
};

/**
 * Get string attribute and assert if the type is actually a string.
 */
export const getStringAttribute = curry((element: HTMLElement, name: string): string => {
  const value = element.getAttribute(name);
  if (typeof value !== 'string') {
    throw new Error(`"${name}" should be a string`);
  }
  return value;
});

export const assertElement = (element: unknown): HTMLElement => {
  if (!(element instanceof HTMLElement)) {
    throw new Error('Should be a dom element');
  }
  return element;
};
