/**
 * Variable from template, @see templates\default\registration.html.twig
 */
declare const translations: { [key: string]: string };

/**
 * See translate.twig for all supported translations.
 */
export const translate = (key: string) => {
  return translations[key] || key;
};
