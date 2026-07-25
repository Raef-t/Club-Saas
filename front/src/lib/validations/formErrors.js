export function getFieldErrors(zodError) {
  const errors = {};

  for (const issue of zodError?.issues || []) {
    const field = issue.path?.[0];
    if (field && !errors[field]) {
      errors[field] = issue.message;
    }
  }

  return errors;
}
