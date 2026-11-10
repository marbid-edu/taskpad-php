<?php
declare(strict_types=1);

final class Task
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $priority,
        public ?string $due,
        public bool $completed,
        public string $createdAt,
    ) {}

    public static function create(array $input): self
    {
        return new self(bin2hex(random_bytes(16)), trim($input['title']),
            trim($input['description'] ?? ''), $input['priority'] ?? 'Medium',
            ($input['due'] ?? '') === '' ? null : $input['due'], false, gmdate('c'));
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public static function fromArray(array $row): self
    {
        foreach (['id', 'title', 'description', 'priority', 'createdAt'] as $key) {
            if (!isset($row[$key]) || !is_string($row[$key])) {
                throw new RuntimeException('Invalid task record.');
            }
        }
        if (!array_key_exists('due', $row) || ($row['due'] !== null && !is_string($row['due']))
            || !isset($row['completed']) || !is_bool($row['completed'])
            || Validator::validateCreate(['title' => $row['title'], 'description' => $row['description'],
                'priority' => $row['priority'], 'due' => $row['due'] ?? '']) !== []) {
            throw new RuntimeException('Invalid task record.');
        }
        return new self($row['id'], $row['title'], $row['description'], $row['priority'],
            $row['due'], $row['completed'], $row['createdAt']);
    }
}
