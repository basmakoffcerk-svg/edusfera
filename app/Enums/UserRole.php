<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Tutor = 'tutor';
    case Student = 'student';
    case Parent = 'parent';

    /**
     * @return list<self>
     */
    public static function allPanelRoles(): array
    {
        return [self::Admin, self::Tutor, self::Student, self::Parent];
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isTutor(): bool
    {
        return $this === self::Tutor;
    }

    public function isStudent(): bool
    {
        return $this === self::Student;
    }

    public function isParent(): bool
    {
        return $this === self::Parent;
    }

    public function canBook(): bool
    {
        return in_array($this, [self::Student, self::Parent], true);
    }
}
