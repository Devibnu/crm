<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'milestone_id',
        'assignee_id',
        'title',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_at',
        'sort_order',
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

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ProjectMilestone::class, 'milestone_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(ProjectTaskChecklist::class)->orderBy('sort_order')->orderBy('id');
    }

    public function totalChecklistCount(): int
    {
        return $this->relationLoaded('checklists')
            ? $this->checklists->count()
            : $this->checklists()->count();
    }

    public function completedChecklistCount(): int
    {
        return $this->relationLoaded('checklists')
            ? $this->checklists->where('is_completed', true)->count()
            : $this->checklists()->where('is_completed', true)->count();
    }

    public function checklistCompletionPercentage(): int
    {
        $total = $this->totalChecklistCount();

        if ($total === 0) {
            return 0;
        }

        return (int) round(($this->completedChecklistCount() / $total) * 100);
    }
}
