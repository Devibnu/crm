<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'full_name', 'username', 'email', 'password', 'avatar', 'role',
    'module_permissions', 'company', 'country', 'contact', 'current_plan', 'status',
    'billing', 'tax_id', 'language', 'task_done', 'project_done',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'module_permissions' => 'array',
            'password' => 'hashed',
        ];
    }

    public static function defaultModulePermissionsForRole(string $role): array
    {
        return match ($role) {
            'admin' => [
                'customers' => 'full',
                'tickets' => 'full',
                'inbox' => 'full',
                'whatsapp' => 'full',
                'invoice' => 'full',
            ],
            'maintainer' => [
                'customers' => 'manage',
                'tickets' => 'manage',
                'inbox' => 'manage',
                'whatsapp' => 'manage',
                'invoice' => 'manage',
            ],
            'author' => [
                'customers' => 'view',
                'tickets' => 'manage',
                'inbox' => 'handle',
                'whatsapp' => 'handle',
                'invoice' => 'view',
            ],
            'editor' => [
                'customers' => 'view',
                'tickets' => 'handle',
                'inbox' => 'handle',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
            'marketing', 'sales', 'service' => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
            default => [
                'customers' => 'view',
                'tickets' => 'view',
                'inbox' => 'view',
                'whatsapp' => 'view',
                'invoice' => 'view',
            ],
        };
    }

    public function resolvedModulePermissions(): array
    {
        return array_merge(
            self::defaultModulePermissionsForRole($this->role),
            $this->module_permissions ?? [],
        );
    }

    public function getAbilityRules(): array
    {
        $abilityRules = $this->role === 'admin'
            ? [['action' => 'manage', 'subject' => 'Admin']]
            : [];

        $subjectMap = [
            'customers' => 'CrmCustomers',
            'tickets' => 'CrmTickets',
            'inbox' => 'CrmInbox',
            'whatsapp' => 'CrmWhatsapp',
            'invoice' => 'BackofficeInvoice',
        ];

        $actionMap = [
            'full' => ['manage', 'create', 'read', 'update', 'delete'],
            'manage' => ['create', 'read', 'update', 'delete'],
            'handle' => ['read', 'update'],
            'view' => ['read'],
        ];

        foreach ($this->resolvedModulePermissions() as $module => $permissionLevel) {
            $subject = $subjectMap[$module] ?? null;
            $actions = $actionMap[$permissionLevel] ?? [];

            if (! $subject) {
                continue;
            }

            foreach ($actions as $action) {
                $abilityRules[] = [
                    'action' => $action,
                    'subject' => $subject,
                ];
            }
        }

        return collect($abilityRules)
            ->unique(fn (array $rule) => $rule['action'].':'.$rule['subject'])
            ->values()
            ->all();
    }

    public function canAccess(string $action, string $subject): bool
    {
        foreach ($this->getAbilityRules() as $rule) {
            if (($rule['subject'] ?? null) !== $subject) {
                continue;
            }

            if (($rule['action'] ?? null) === 'manage' || ($rule['action'] ?? null) === $action) {
                return true;
            }
        }

        return false;
    }

    public function toAuthResponse(): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'username' => $this->username,
            'avatar' => $this->avatar ?? '',
            'email' => $this->email,
            'role' => $this->role,
            'modulePermissions' => $this->resolvedModulePermissions(),
        ];
    }

    public function toListResponse(): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'company' => $this->company ?? '',
            'role' => $this->role,
            'username' => $this->username,
            'country' => $this->country ?? '',
            'contact' => $this->contact ?? '',
            'email' => $this->email,
            'currentPlan' => $this->current_plan,
            'status' => $this->status,
            'avatar' => $this->avatar ?? '',
            'billing' => $this->billing,
            'modulePermissions' => $this->resolvedModulePermissions(),
            'taskDone' => $this->task_done,
            'projectDone' => $this->project_done,
            'taxId' => $this->tax_id ?? '',
            'language' => $this->language,
        ];
    }
}
