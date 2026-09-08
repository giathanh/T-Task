<?php

namespace App\Enums;

enum ProjectStatus: string
{
    case Planning = 'planning';
    case InProgress = 'in_progress';
    case OnHold = 'on_hold';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Planning => 'Lên kế hoạch',
            self::InProgress => 'Đang thực hiện',
            self::OnHold => 'Tạm dừng',
            self::Completed => 'Hoàn thành',
            self::Archived => 'Lưu trữ',
        };
    }
}
