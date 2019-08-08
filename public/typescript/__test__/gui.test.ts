import { retryClicked } from '../gui';

it('Should show retry button when active', async () => {

  const button = document.createElement('button');
  button.id = 'retryButton';
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
