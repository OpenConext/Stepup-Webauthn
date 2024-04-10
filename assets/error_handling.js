window.addEventListener("unhandledrejection", (event) => {
    const requestInfo = event.target.requestInformation;
    const timestamp = new Date();

    // Hide the form
    document.getElementById("form").classList.add('hidden');

    // Show the error report
    document.getElementById("errorData").classList.remove('hidden');
    let errorFields = document.querySelectorAll('[data-content-for]');
    let written = false;
    errorFields.forEach((element) => {
        fieldName = element.dataset.contentFor;
        written = false;
        // 1. The timestamp is filled with the current time
        if (fieldName === 'timestamp') {
            element.innerHTML = timestamp.toISOString();
            written = true;
        }
        // Other fields are displayed from the requestInformation that is present in the event
        if (requestInfo.hasOwnProperty(fieldName)) {
            element.innerHTML = requestInfo[fieldName];
            written = true;
        }
        // Show the error message from the uncaught exception
        if (fieldName === 'errorMessage' && requestInfo['errorMessage'] === undefined ) {
            element.innerHTML = event.reason.message;
            written = true;
        }
        // If no sensible data was available to write error field, remove the tr
        if (!written) {
            element.parentElement.remove();
        }
    });
});
