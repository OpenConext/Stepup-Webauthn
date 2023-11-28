import { pipe, reduce, replace, slice, splitEvery, toString } from 'ramda';

/**
 * Simple hashing function for message to error codes.
 */
// tslint:disable-next-line:no-bitwise
const hashChars = reduce<string, number>((hash, char) => Math.abs(((hash << 5) - hash) + char.charCodeAt(0)), 0);
const splitStringToChars = splitEvery(1);
const removeQuotedVariables: (val: string) => string = replace(/".*?"|'.*?'/g, '');
const take6Chars = slice(0, 6);
const prefixWithF = (val: string) => `F${val}`;

/**
 * This is the javascript version of the Art class in php.
 */
export const createErrorCode = pipe<string, string, string[], number, string, string, string>(
  removeQuotedVariables,
  splitStringToChars,
  hashChars,
  toString,
  take6Chars,
  prefixWithF,
);
