import { set } from 'ramda';

import {
  base64UrlSafeToUInt8,
  base64UrlSafeToUInt8Id,
  base64UrlSafeToUInt8Ids,
  idLens,
  optionalBase64UrlSafeToUInt8Ids,
  removeEmptyAndUndefined,
} from '../common';

it('base64UrlSafeToUInt8', () => {
  expect(base64UrlSafeToUInt8('z4Ag4oiIIOKEnQ==')).toEqual((new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157])).buffer);
});

it('idLens', () => {
  expect(set(idLens, 2, { id: 1, foo: 'bar' })).toEqual({
    id: 2,
    foo: 'bar',
  });
});

it('base64ToUInt8Id', () => {
  expect(base64UrlSafeToUInt8Id({ id: 'z4Ag4oiIIOKEnQ==', foo: 'bar' })).toEqual({
    id: (new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157])).buffer,
    foo: 'bar',
  });
});

it('base64ToUInt8Ids', () => {
  expect(base64UrlSafeToUInt8Ids([{ id: 'z4Ag4oiIIOKEnQ==', foo: 'bar' }, {
    id: 'NDM1MzQ1MzQ=',
    bar: 'foo',
  }])).toEqual([
    {
      id: (new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157])).buffer,
      foo: 'bar',
    },
    {
      id: (new Uint8Array([52, 51, 53, 51, 52, 53, 51, 52])).buffer,
      bar: 'foo',
    },
  ]);
});

it('optionalBase64ToUInt8Ids can convert', () => {
  expect(optionalBase64UrlSafeToUInt8Ids([{ id: 'z4Ag4oiIIOKEnQ==', foo: 'bar' }, {
    id: 'NDM1MzQ1MzQ=',
    bar: 'foo',
  }])).toEqual([
    {
      id: (new Uint8Array([207, 128, 32, 226, 136, 136, 32, 226, 132, 157])).buffer,
      foo: 'bar',
    },
    {
      id: (new Uint8Array([52, 51, 53, 51, 52, 53, 51, 52])).buffer,
      bar: 'foo',
    },
  ]);
});

it('optionalBase64ToUInt8Ids can be empty', () => {
  expect(optionalBase64UrlSafeToUInt8Ids(undefined)).toEqual(undefined);
});

it('removeEmptyAndUndefined', () => {
  expect(removeEmptyAndUndefined({
    test: undefined,
    foo: [],
    bar: 'bar',
  })).toStrictEqual({
    bar: 'bar',
  });
});
