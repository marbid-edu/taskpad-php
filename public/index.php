<?php
declare(strict_types=1);
session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'none'");
require __DIR__ . '/../src/Validator.php';
require __DIR__ . '/../src/Task.php';
require __DIR__ . '/../src/TaskRepository.php';

function e(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function scalar(array $source, string $key, string $default = ''): string {
    return isset($source[$key]) && is_string($source[$key]) ? $source[$key] : $default;
}
function redirectToList(string $query, string $priority): never {
    $params = array_filter(['q' => $query, 'priority' => $priority], static fn ($v) => $v !== '');
    header('Location: /' . ($params ? '?' . http_build_query($params) : ''), true, 303);
    exit;
}

$repository = new TaskRepository(__DIR__ . '/../data/tasks.json');
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$q = substr(trim(scalar($_GET, 'q')), 0, 150);
$filterPriority = ucfirst(strtolower(scalar($_GET, 'priority')));
if (!in_array($filterPriority, ['Low', 'Medium', 'High'], true)) { $filterPriority = ''; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $q = substr(trim(scalar($_POST, 'q')), 0, 150);
    $filterPriority = ucfirst(strtolower(scalar($_POST, 'filter_priority')));
    if (!in_array($filterPriority, ['Low', 'Medium', 'High'], true)) { $filterPriority = ''; }
    $input = ['title' => scalar($_POST, 'title'), 'description' => scalar($_POST, 'description'),
        'priority' => scalar($_POST, 'priority', 'Medium'), 'due' => scalar($_POST, 'due')];
    try {
        if (!hash_equals($_SESSION['csrf'], scalar($_POST, 'csrf'))) {
            throw new DomainException('Your session expired. Please try again.');
        }
        $action = scalar($_POST, 'action');
        if ($action === 'create') {
            $errors = Validator::validateCreate($input);
            if ($errors) {
                $_SESSION['errors'] = $errors;
                $_SESSION['old'] = $input;
                $_SESSION['flash'] = ['error', 'Please check the highlighted fields.'];
                redirectToList($q, $filterPriority);
            }
            $repository->add(Task::create($input));
            $_SESSION['flash'] = ['success', 'Task added. You have got this!'];
        } elseif ($action === 'complete' || $action === 'delete') {
            $id = scalar($_POST, 'id');
            if ($action === 'complete') { $repository->complete($id); }
            else { $repository->delete($id); }
            $_SESSION['flash'] = ['success', $action === 'complete' ? 'Task marked complete.' : 'Task deleted.'];
        } else { throw new DomainException('Unknown action. Please use the task buttons.'); }
    } catch (DomainException $exception) {
        $_SESSION['flash'] = ['error', $exception->getMessage()];
    } catch (Throwable $exception) {
        error_log('TaskPad storage: ' . $exception->getMessage());
        $_SESSION['flash'] = ['error', 'Unable to save changes. Check data/tasks.json and its write permissions.'];
        if (scalar($_POST, 'action') === 'create') { $_SESSION['old'] = $input; }
    }
    redirectToList($q, $filterPriority);
}
$flash = $_SESSION['flash'] ?? null;
$errors = $_SESSION['errors'] ?? [];
$old = $_SESSION['old'] ?? ['title' => '', 'description' => '', 'priority' => 'Medium', 'due' => ''];
unset($_SESSION['flash'], $_SESSION['errors'], $_SESSION['old']);
$storageError = false;
try { $tasks = $repository->all(); }
catch (Throwable $exception) {
    error_log('TaskPad storage: ' . $exception->getMessage());
    $tasks = []; $storageError = true; http_response_code(503);
}
$total = count($tasks);
$completed = count(array_filter($tasks, static fn (Task $task) => $task->completed));
$visible = array_values(array_filter($tasks, static fn (Task $task) =>
    ($q === '' || stripos($task->title . ' ' . $task->description, $q) !== false)
    && ($filterPriority === '' || $task->priority === $filterPriority)));
usort($visible, static fn (Task $a, Task $b) => ($a->completed <=> $b->completed)
    ?: strcmp($b->createdAt, $a->createdAt));
function hiddenContext(): void {
    global $q, $filterPriority;
    echo '<input type="hidden" name="csrf" value="' . e($_SESSION['csrf']) . '">';
    echo '<input type="hidden" name="q" value="' . e($q) . '">';
    echo '<input type="hidden" name="filter_priority" value="' . e($filterPriority) . '">';
}
function fieldError(string $key): void {
    global $errors;
    if (isset($errors[$key])) { echo '<span class="field-error" id="' . e($key) . '-error">' . e($errors[$key]) . '</span>'; }
}
function errorAttributes(string $key): string {
    global $errors;
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="' . $key . '-error"' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TaskPad — Make room for what matters</title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<a class="skip" href="#main">Skip to tasks</a>
<header class="topbar"><a class="brand" href="/" aria-label="TaskPad home"><span class="brand-icon" aria-hidden="true">✓</span> TaskPad<span class="brand-tag">PHP</span></a><span class="top-note">A little focus. A lot of progress.</span></header>
<main id="main" class="shell">
    <section class="intro"><div><p class="eyebrow">YOUR EVERYDAY WORKSPACE</p><h1>Small steps.<br><span>Clearer days.</span></h1><p class="subtitle">Get it out of your head and onto your task list.</p></div><div class="intro-note"><span aria-hidden="true">✦</span><p>One thing at a time.<br>You're making progress.</p></div></section>
    <?php if ($flash): ?><div class="notice <?= e($flash[0]) ?>" role="status"><?= e($flash[1]) ?></div><?php endif; ?>
    <?php if ($storageError): ?><div class="notice error" role="alert">Tasks could not be loaded. Check data/tasks.json and its permissions. Existing data has not been reset.</div><?php endif; ?>
    <section class="stats" aria-label="Overall task counts"><div><span class="stat-label">Total tasks</span><strong><?= $total ?></strong></div><div><span class="stat-label">In progress</span><strong><?= $total - $completed ?><span class="stat-dot"></span></strong></div><div><span class="stat-label">Completed</span><strong><?= $completed ?><span class="stat-check" aria-hidden="true">✓</span></strong></div></section>
    <div class="workspace">
    <aside class="panel composer"><div class="panel-heading"><span class="eyebrow">MAKE A LITTLE PLAN</span><h2>Add a task</h2><p>What would you like to get done?</p></div>
        <form method="post" action="/">
            <?php hiddenContext(); ?><input type="hidden" name="action" value="create">
            <label for="title">Task title <span class="required">*</span></label>
            <input id="title" name="title" value="<?= e($old['title']) ?>" maxlength="150" required placeholder="e.g. Finish the project outline"<?= errorAttributes('title') ?>><?php fieldError('title'); ?>
            <label for="description">Description <span class="optional">optional</span></label>
            <textarea id="description" name="description" rows="4" maxlength="2000" placeholder="Add a few details…"<?= errorAttributes('description') ?>><?= e($old['description']) ?></textarea><?php fieldError('description'); ?>
            <div class="form-row"><div><label for="priority">Priority</label><select id="priority" name="priority"<?= errorAttributes('priority') ?>><?php foreach (['Low', 'Medium', 'High'] as $priority): ?><option value="<?= $priority ?>" <?= $old['priority'] === $priority ? 'selected' : '' ?>><?= ucfirst($priority) ?></option><?php endforeach; ?></select><?php fieldError('priority'); ?></div>
            <div><label for="due">Due date <span class="optional">optional</span></label><input type="date" id="due" name="due" value="<?= e($old['due']) ?>"<?= errorAttributes('due') ?>><?php fieldError('due'); ?></div></div>
            <button class="primary" type="submit"><span aria-hidden="true">＋</span> Add task</button><p class="form-hint">Required: title. Default priority: medium.</p>
        </form>
    </aside>
    <section class="task-section" aria-labelledby="list-title">
        <div class="list-heading"><h2 id="list-title">Your tasks <span class="count"><?= count($visible) ?></span></h2><span class="muted">A little more done, every day.</span></div>
        <form class="filters" method="get" action="/" role="search"><div><label class="sr-only" for="q">Search tasks</label><input type="search" name="q" id="q" value="<?= e($q) ?>" maxlength="150" placeholder="Search title or description…"></div><div><label class="sr-only" for="filter-priority">Filter by priority</label><select id="filter-priority" name="priority"><option value="">All priorities</option><?php foreach (['Low', 'Medium', 'High'] as $priority): ?><option value="<?= $priority ?>" <?= $filterPriority === $priority ? 'selected' : '' ?>><?= ucfirst($priority) ?></option><?php endforeach; ?></select></div><button class="secondary" type="submit">Filter</button><?php if ($q !== '' || $filterPriority !== ''): ?><a class="clear" href="/">Clear</a><?php endif; ?></form>
        <p class="result-note">Showing <?= count($visible) ?> of <?= $total ?> tasks · Open tasks first</p>
        <div class="task-list">
        <?php foreach ($visible as $task): ?>
            <article class="task-card <?= $task->completed ? 'is-complete' : '' ?>">
                <div class="task-main"><span class="task-indicator" aria-hidden="true"><?= $task->completed ? '✓' : '' ?></span><div class="task-copy"><h3><?= e($task->title) ?></h3><?php if ($task->description !== ''): ?><p class="description"><?= nl2br(e($task->description)) ?></p><?php endif; ?><div class="metadata"><span class="badge <?= e(strtolower($task->priority)) ?>"><?= ucfirst(e($task->priority)) ?> priority</span><?php if ($task->due): ?><span class="due">Due <time datetime="<?= e($task->due) ?>"><?= e($task->due) ?></time></span><?php endif; ?><span class="task-status"><?= $task->completed ? 'Completed' : 'In progress' ?></span></div></div></div>
                <div class="task-actions"><?php if (!$task->completed): ?><form method="post" action="/"><?php hiddenContext(); ?><input type="hidden" name="id" value="<?= e($task->id) ?>"><button class="complete-button" name="action" value="complete" aria-label="Complete <?= e($task->title) ?>">✓ Complete</button></form><?php endif; ?><form method="post" action="/"><?php hiddenContext(); ?><input type="hidden" name="id" value="<?= e($task->id) ?>"><button class="delete-button" name="action" value="delete" aria-label="Delete <?= e($task->title) ?>">Delete</button></form></div>
            </article>
        <?php endforeach; ?>
        <?php if (!$visible && !$storageError): ?><div class="empty-state"><span class="empty-icon" aria-hidden="true">✓</span><h3><?= $total ? 'No matching tasks' : 'A fresh start awaits' ?></h3><p><?= $total ? 'Try another search or clear your filters.' : 'Add your first task and give your day a little direction.' ?></p></div><?php endif; ?>
        </div>
    </section>
    </div>
    <footer><span>TaskPad / CIS 435 · Mohamed Arbid</span><span>Built for a little more clarity.</span></footer>
</main>
</body>
</html>
