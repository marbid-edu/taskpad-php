# University hosting

**CIS 435 — Mohamed Arbid**

Live URL: https://www-personal.umd.umich.edu/~marbid/taskpad/index.php

The application was uploaded through the university's documented SFTP service, login.umd.umich.edu. The previously empty HTML.disabled folder was preserved. The new HTML folder contains only the application page, stylesheet, and the university's documented PHP enablement directive.

The server runs PHP 8.2.35. Its PHP process can read the university home drive but cannot open its JSON file for writing, even with file mode 0666. The hosted page therefore provisions a separate, server-owned application directory beneath PHP's configured file-session storage directory. Its directory permissions are 0700 and its JSON file permissions are 0600. This is a dedicated shared task store, separate from each visitor's session; task data remains across browser sessions. Provider maintenance of that server storage may remove it, so this is a classroom demo rather than storage for important records.

The standard VS Code/GitHub application still uses data/tasks.json normally. Only this university-hosting copy contains the storage fallback and /~marbid/taskpad URL prefix.

The empty home-drive seed tasks.json was changed to 0666 with the user's approval while diagnosing the host's write restriction. It contains no user tasks. Actual hosted task data is in the server-owned private store; no source or JSON data directory is under HTML.

All 16 live HTTP checks passed: page rendering, UTF-8 response, stylesheet, validation, PRG, create/persistence, flash, combined filtering, completion/filter preservation, deletion, and private-file URL rejection. All demonstration test tasks were removed. The deployment diagnostic endpoint returns 404 after verification.

The GitHub repository is https://github.com/marbid-edu/taskpad-php. Nothing was submitted to Canvas.
