<?php

namespace App\Support;

/**
 * The relationship a guardian has to a student — 'Parent' | 'Guardian'.
 *
 * The guardian's default lives on `users.active_role`; the authoritative
 * per-student value is `students.relationship` (mirrored from the server's
 * `guardian_student.relationship` pivot).
 */
final class Relationship
{
    public const PARENT = 'Parent';

    public const GUARDIAN = 'Guardian';

    public const DEFAULT = self::GUARDIAN;

    /** @var list<string> */
    public const VALUES = [self::PARENT, self::GUARDIAN];

    public static function normalize(?string $value): string
    {
        $value = trim((string) $value);

        return in_array($value, self::VALUES, true) ? $value : self::DEFAULT;
    }
}
