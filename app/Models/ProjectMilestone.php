<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMilestone extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PLANNING = 'planning';
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DELAYED = 'delayed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'color',
        'icon',
        'status',
        'start_date',
        'due_date',
        'completed_at',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'milestone_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(ProjectTimesheet::class, 'milestone_id')->latest('work_date')->latest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function totalTaskCount(): int
    {
        if ($this->relationLoaded('tasks')) {
            return $this->tasks->count();
        }

        return (int) $this->tasks()->count();
    }

    public function completedTaskCount(): int
    {
        if ($this->relationLoaded('tasks')) {
            return $this->tasks
                ->filter(fn (ProjectTask $task): bool => $task->status === 'done' || $task->completed_at !== null)
                ->count();
        }

        return (int) $this->tasks()
            ->where(fn ($query) => $query->where('status', 'done')->orWhereNotNull('completed_at'))
            ->count();
    }

    public function overdueTaskCount(): int
    {
        if ($this->relationLoaded('tasks')) {
            return $this->tasks
                ->filter(fn (ProjectTask $task): bool => $task->due_date && $task->due_date->lt(now()->startOfDay()) && $task->status !== 'done')
                ->count();
        }

        return (int) $this->tasks()
            ->whereDate('due_date', '<', now()->toDateString())
            ->where('status', '!=', 'done')
            ->count();
    }

    public function progressPercentage(): int
    {
        $totalTasks = $this->totalTaskCount();

        if ($totalTasks > 0) {
            return (int) round(($this->completedTaskCount() / $totalTasks) * 100);
        }

        return $this->status === self::STATUS_COMPLETED ? 100 : 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null
            && $this->due_date->lt(now()->startOfDay())
            && ! in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED], true);
    }

    public function displayStatus(): string
    {
        if ($this->status === self::STATUS_COMPLETED) {
            return self::STATUS_COMPLETED;
        }

        if ($this->isOverdue() || $this->overdueTaskCount() > 0) {
            return self::STATUS_DELAYED;
        }

        return $this->status;
    }
}
