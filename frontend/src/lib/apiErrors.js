export function getApiErrorMessage(error, fallback = 'Something went wrong. Please try again.') {
  const responseData = error?.response?.data;

  if (responseData?.message) return responseData.message;

  const firstValidationMessage = Object.values(responseData?.errors || {})
    .flat()
    .find(Boolean);

  return firstValidationMessage || fallback;
}

export function getApiFieldErrors(error) {
  return error?.response?.data?.errors || {};
}
