<?php
declare(strict_types=1);
require __DIR__ . '/../src/Validator.php';
require __DIR__ . '/../src/Task.php';
require __DIR__ . '/../src/TaskRepository.php';
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException('FAIL: ' . $label); }
    $checks++; echo "PASS: $label\n";
}
check(isset(Validator::validateCreate(['title' => '   '])['title']), 'Blank title rejected');
check(isset(Validator::validateCreate(['title' => str_repeat('a', 151)])['title']), 'Long title rejected');
check(isset(Validator::validateCreate(['title' => 'Test', 'description' => str_repeat('a', 2001)])['description']), 'Long description rejected');
check(isset(Validator::validateCreate(['title' => 'Test', 'priority' => 'urgent'])['priority']), 'Invalid priority rejected');
check(isset(Validator::validateCreate(['title' => 'Test', 'due' => '2026-02-30'])['due']), 'Impossible date rejected');
check(Validator::validateCreate(['title' => 'Test', 'due' => '2028-02-29']) === [], 'Leap date accepted');
$path = tempnam(sys_get_temp_dir(), 'taskpad-');
file_put_contents($path, "[]\n");
try {
    $repo = new TaskRepository($path);
    $task = Task::create(['title' => ' <script>alert(1)</script> ']);
    $repo->add($task);
    check($task->priority === 'Medium' && $task->due === null && !$task->completed, 'Optional defaults');
    check(count((new TaskRepository($path))->all()) === 1, 'JSON persists across instances');
    check($repo->all()[0]->title === '<script>alert(1)</script>', 'Title trimmed and text stored faithfully');
    $repo->complete($task->id); $repo->complete($task->id);
    check($repo->all()[0]->completed, 'Completion persists and is idempotent');
    $repo->delete($task->id);
    check($repo->all() === [], 'Delete persists');
    try { $repo->delete('missing'); check(false, 'Missing ID rejected'); }
    catch (DomainException) { check(true, 'Missing ID rejected'); }
    file_put_contents($path, '{broken');
    try { $repo->add($task); check(false, 'Corrupt JSON rejected'); }
    catch (JsonException) { check(file_get_contents($path) === '{broken', 'Corrupt JSON preserved'); }
    file_put_contents($path, '{}');
    try { $repo->all(); check(false, 'JSON object rejected'); }
    catch (RuntimeException) { check(file_get_contents($path) === '{}', 'JSON object rejected and preserved'); }
} finally { unlink($path); }
echo "$checks checks passed. Your actual tasks.json was not touched.\n";

