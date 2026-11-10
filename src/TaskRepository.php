<?php
declare(strict_types=1);

final class TaskRepository
{
    public function __construct(private string $path) {}

    /** @return list<Task> */
    public function all(): array
    {
        return $this->access(null);
    }

    public function add(Task $task): void
    {
        $this->access(static function (array &$tasks) use ($task): void { $tasks[] = $task; });
    }

    public function complete(string $id): void
    {
        $this->change($id, false);
    }

    public function delete(string $id): void
    {
        $this->change($id, true);
    }

    private function change(string $id, bool $delete): void
    {
        $this->access(static function (array &$tasks) use ($id, $delete): void {
            foreach ($tasks as $index => $task) {
                if ($task->id === $id) {
                    if ($delete) { array_splice($tasks, $index, 1); }
                    else { $task->completed = true; }
                    return;
                }
            }
            throw new DomainException('That task no longer exists.');
        });
    }

    /** Locks the entire read/modify/write operation to prevent lost updates. */
    private function access(?callable $update): array
    {
        $handle = @fopen($this->path, $update === null ? 'rb' : 'r+b');
        if (!$handle) { throw new RuntimeException('Task storage could not be opened.'); }
        try {
            if (!flock($handle, $update === null ? LOCK_SH : LOCK_EX)) {
                throw new RuntimeException('Task storage could not be locked.');
            }
            $json = stream_get_contents($handle);
            // An empty or malformed existing file is an error, never silently reset it.
            $rows = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            if (!str_starts_with(ltrim($json), '[') || !is_array($rows) || !array_is_list($rows)) {
                throw new RuntimeException('Task storage must contain a JSON array.');
            }
            $tasks = array_map(static fn ($row) => is_array($row)
                ? Task::fromArray($row) : throw new RuntimeException('Invalid task record.'), $rows);
            $ids = array_map(static fn (Task $task) => $task->id, $tasks);
            if (count(array_unique($ids)) !== count($ids)) {
                throw new RuntimeException('Duplicate task identifiers in storage.');
            }
            if ($update !== null) {
                $update($tasks);
                $encoded = json_encode(array_map(static fn (Task $task) => $task->toArray(), $tasks),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n";
                rewind($handle);
                $offset = 0;
                while ($offset < strlen($encoded)) {
                    $written = fwrite($handle, substr($encoded, $offset));
                    if ($written === false || $written === 0) { throw new RuntimeException('Storage write failed.'); }
                    $offset += $written;
                }
                if (!ftruncate($handle, strlen($encoded)) || !fflush($handle)) {
                    throw new RuntimeException('Storage write failed.');
                }
            }
            return $tasks;
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
