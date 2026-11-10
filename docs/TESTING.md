# Verification guide

**CIS 435 — Mohamed Arbid**

## Recorded checks

- PHP 8.4.27 lint for the PHP source files.
- 14 dependency-free PHP checks: validation, defaults, persistent JSON, completion, deletion, stale IDs, corrupt JSON, and wrong JSON root type.
- 18 HTTP checks: PRG, preserved inline errors, one-time flash, optional defaults, XSS escaping, CSRF rejection, combined filtering, counts, completion/deletion, GET cannot delete, array input, and storage error handling. See `http-test-results.txt`.
- 43 browser checks across desktop, tablet, and phone: validation and success messages, filtering, completion, deletion, filter preservation, no horizontal overflow, and no page errors. See `browser-test-results.txt`.
- 24 full-page screenshots in `screenshots/`. Before/after pairs use the same demonstration tasks within each device run. The staged data file is restored to empty afterwards.

## Manual walkthrough

1. Run the server using the README. The empty page has zero counts and a clear create form.
2. Add “Review PHP” with a description, High priority, and a valid date. Confirm a success message and total/open counts of one. Refresh; it must remain one task.
3. Disable native form validation in browser developer tools or send a direct POST to test an empty title or impossible date. Server errors must appear inline, and the submitted fields must remain filled.
4. Add a task containing `<b>plain text</b>`. It must display literal angle brackets, not bold HTML. Do the same in description and search.
5. Add another task with only a title. Confirm Medium priority and no due date.
6. Search for part of a description with different capitalization. Combine it with a matching priority; then choose a nonmatching priority. Verify the matching count and “No matching tasks” state. Clear filters.
7. Complete a filtered task. Confirm completed styling, counts, flash, and retained filters. Refresh to confirm persistence and no repeated flash. Stop/restart the server and verify the data remains.
8. Delete a task. Confirm it disappears, counts change, and refresh does not repeat deletion. GET query parameters alone must never mutate data.
9. Use keyboard Tab through all inputs/buttons and the skip link. Resize to phone/tablet widths; fields and controls should remain usable with no sideways scrolling.
10. On a disposable copy, make tasks.json invalid. Confirm a visible storage error; attempts to create must preserve the corrupt file. Restore the original JSON afterwards.

The HTTP/browser checks were run against a disposable staged copy. The PHP test script is included and safe to rerun on the installed project. No Canvas submission or GitHub publication is part of testing.
