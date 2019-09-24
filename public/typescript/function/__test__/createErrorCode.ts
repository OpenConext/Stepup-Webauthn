import { createErrorCode } from '../createErrorCode';

it('createErrorCode', () => {
  expect(createErrorCode('Test exception')).toEqual('F121094');
  expect(createErrorCode('Test "1234"')).toEqual('F806987');
  expect(createErrorCode('Test "456"')).toEqual('F806987');
  expect(createErrorCode('Test \'456\'')).toEqual('F806987');
  expect(createErrorCode('Test \'1234\'')).toEqual('F806987');

  expect(createErrorCode('Foo \'1234\' Bar \'5678\'')).toEqual('F111311');
  expect(createErrorCode('Foo \'8765\' Bar \'4321\'')).toEqual('F111311');
});
