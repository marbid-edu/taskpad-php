<?php
declare(strict_types=1);

final class Validator
{
    /** @return array<string,string> */
    public static function validateCreate(array $input): array
    {
        $errors = [];
        $title = trim($input['title'] ?? '');
        if ($title === '' || strlen($title) > 150) {
            $errors['title'] = 'Enter a title of 1 to 150 bytes.';
        }
        if (strlen($input['description'] ?? '') > 2000) {
            $errors['description'] = 'Keep the description within 2,000 bytes.';
        }
        if (!in_array($input['priority'] ?? 'Medium', ['Low', 'Medium', 'High'], true)) {
            $errors['priority'] = 'Choose low, medium, or high priority.';
        }
        $due = $input['due'] ?? '';
        if ($due !== '') {
            try { $date = DateTimeImmutable::createFromFormat('!Y-m-d', $due); }
            catch (ValueError) { $date = false; }
            if (!$date || $date->format('Y-m-d') !== $due) {
                $errors['due'] = 'Enter a real date in YYYY-MM-DD format.';
            }
        }
        return $errors;
    }

}
