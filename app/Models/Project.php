<?php

namespace App\Models;

use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Enums\ProjectStatus;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'description', 'status', 'due_date', 'created_by'])]
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'due_date' => 'date',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    /**
     * Total and per-status issue counts, grouped by issue type.
     *
     * @return array<string, array{total: int, by_status: array<string, int>}>
     */
    public function issueStats(): array
    {
        $rows = $this->issues()
            ->selectRaw('type, status, count(*) as aggregate')
            ->groupBy('type', 'status')
            ->get();

        return collect(IssueType::cases())->mapWithKeys(function (IssueType $type) use ($rows) {
            $rowsForType = $rows->filter(fn (Issue $row) => $row->type === $type);

            return [$type->value => [
                'total' => (int) $rowsForType->sum('aggregate'),
                'by_status' => collect(IssueStatus::cases())->mapWithKeys(
                    fn (IssueStatus $status) => [
                        $status->value => (int) ($rowsForType->first(fn (Issue $row) => $row->status === $status)?->aggregate ?? 0),
                    ]
                )->all(),
            ]];
        })->all();
    }
}
