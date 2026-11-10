# TaskPad PHP

**CIS 435 — Mohamed Arbid**

Repository: https://github.com/marbid-edu/taskpad-php

A plain PHP task tracker with JSON persistence. No framework, database, JavaScript, or package installation is required to run the application. Tested with PHP **8.4.27**; this implementation requires PHP 8.1+ (`never`, `array_is_list`).

## Run locally in VS Code

Open `C:\Users\moham\taskpad-php` as the VS Code folder. Open Terminal → New Terminal, then run:

```powershell
php -S localhost:8000 -t public
```

Open **http://localhost:8000**. Leave the terminal running; press **Ctrl+C** to stop it. If port 8000 is occupied, use 8001 and open that port instead.

If `php` is not recognized, reopen VS Code after PHP installation or use your installed executable directly:

```powershell
& 'C:\Users\moham\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe\php.exe' -S localhost:8000 -t public
```

Use `public` as the document root, so the browser cannot download the JSON store or PHP source. The development server is for local coursework use.

## Use

- Enter a required title. Description and due date are optional; priority defaults to Medium.
- Add task submits POST, validates on the server, saves, and returns a **303 redirect** to the list. Invalid submissions also redirect, preserving entries and inline errors in the session.
- Search title or description without case sensitivity. Combine text and priority with AND. Example: `/?q=project&priority=High`. Filter values are accepted without case sensitivity.
- Counts show all tasks, open tasks (labeled “In progress”), and completed tasks. “Showing X of Y” reports filtered results. Open tasks appear first, then newest first within each state.
- Complete and Delete submit POST, preserve filters, and display a one-time session flash. Complete is idempotent. Delete is immediate and permanent; a stale task ID gives a friendly error.
- Reload after any mutation to confirm that PRG prevents resubmission.

## Validation and security

Title is trimmed, required, and limited to 150 bytes. Description is trimmed and limited to 2,000 bytes. These are UTF-8 byte limits, so some non-ASCII characters consume more than one byte. Priority must be Low, Medium, or High. A due date must round-trip as a real `YYYY-MM-DD` date, including leap-year checks. Past dates are allowed. Array-shaped request values are safely ignored.

Text is stored as text and escaped at output with `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`; descriptions preserve line breaks after escaping. Every mutation uses a session CSRF token verified with `hash_equals`. Cookies use HttpOnly and SameSite=Lax, and Secure under HTTPS. The page sets HTML UTF-8, a restrictive content security policy, and `nosniff` headers. No user input chooses the JSON file path or redirect destination.

## Files and data

```text
public/index.php          GET list/form and POST request handling
public/assets/style.css   Responsive styles and keyboard focus states
src/Task.php              Task model, creation, JSON conversion
src/Validator.php         Server validation
src/TaskRepository.php    Locked JSON persistence
data/tasks.json           Initially []; actual tasks stay here
docs/uml-usecase.pdf      UML use case diagram
docs/uml-class.pdf        UML class diagram
docs/uml/*.puml           Editable PlantUML equivalents
docs/render-uml.py        Reproducible PDF drawing source (ReportLab)
docs/TESTING.md           Manual checks and recorded verification
docs/GROUP-EVALUATION.md  Evaluation form availability / student name
screenshots/              Desktop, tablet, and mobile evidence
tests/run.php             Dependency-free model/storage checks
```

The one-page controller combines the recommended create/actions endpoints while keeping validation and storage in separate classes. `Task` fields are `id`, `title`, `description`, `priority` (Low/Medium/High), `due` (date or null), `completed` (boolean), and `createdAt` (UTC timestamp). IDs are 128-bit random hex strings. All task reads and complete read/modify/write operations use advisory locks to avoid lost updates between cooperating PHP requests.

The data file must exist, contain a JSON array, and be readable/writable by PHP. Corrupt records produce an error and are never silently reset. Back up the file before manual edits. To deliberately clear your own tasks, stop the server, back up `data/tasks.json`, then replace its contents with `[]`. File writes are locked but not crash-atomic; this small coursework app does not replace a transactional database. It has no login: all visitors to one server share one task list.

## Tests and screenshots

```powershell
php tests/run.php
Get-ChildItem public,src,tests -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
```

The automated PHP checks use temporary files and leave your task store untouched. Follow `docs/TESTING.md` for browser checks. Screenshots use demonstration tasks and were captured on a staged copy; the delivered store starts empty. Each device has eight captures: empty, create-error, create-success, list-before-actions, list-filtered, complete-after, delete-before, delete-after. Viewport sizes are desktop 1440×1100, tablet 820×1180, and mobile 390×844; full-page captures can be taller.

## UML editing

PDFs were drawn with ReportLab and visually checked after rendering. Corresponding PlantUML sources are included in `docs/uml`; PlantUML itself was not required to build the PDFs. To regenerate the supplied PDFs, install Python and ReportLab (`python -m pip install reportlab`) and run `python docs/render-uml.py`. The diagram identifies the procedural page controller explicitly rather than pretending it is a PHP class. The activity diagram is optional and is not included.

## Before submitting

Review the project and understand the PHP flow. The original welcome page is backed up during installation. All supplied documents identify Mohamed Arbid. The instructor's group evaluation attachment was not provided; add the actual completed form to the repository if required, even for a solo submission. The pasted rubric is saved in `docs/assignment-requirements.txt` for reference.

This project is available at https://github.com/marbid-edu/taskpad-php. Nothing has been submitted to Canvas. Use the repository URL for the assignment submission, and add the instructor’s completed group evaluation form if required. The published JSON store starts empty.
