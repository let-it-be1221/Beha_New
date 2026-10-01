<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\HasConfidentialId;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * Beha User model.
 *
 * Spec §5 (Official ID), §6 (Confidential ID), §21 (RBAC).
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRoles;
    use HasConfidentialId;
    use Auditable;

    protected $table = 'users';

    protected $fillable = [
        'official_id',
        'confidential_id',
        'username',
        'email',
        'password',
        'must_change_password',
        'is_active',
        'failed_login_count',
        'locked_until',
        'current_team_id',
        'level',
        'last_login_at',
        'last_login_ip',
        'profile_photo_path',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'confidential_id', // NEVER expose in serialization; use Resource with policy
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'    => 'datetime',
            'last_login_at'        => 'datetime',
            'locked_until'         => 'datetime',
            'password'             => 'hashed',
            'must_change_password' => 'boolean',
            'is_active'            => 'boolean',
            'level'                => 'integer',
            'failed_login_count'   => 'integer',
        ];
    }

    // ─── Relations ────────────────────────────────────────────────────────

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    public function teamMember(): HasOne
    {
        return $this->hasOne(TeamMember::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    // ─── Scopes ──────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInTeam($query, int $teamId)
    {
        return $query->where('current_team_id', $teamId);
    }

    public function scopeAtLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    public function isTeamMember(): bool
    {
        return $this->level === 1 || $this->level === 2;
    }

    public function isTeamLeader(): bool
    {
        return $this->level === 3;
    }

    public function isBranchLeader(): bool
    {
        return $this->level === 4;
    }

    public function isGenerationLeader(): bool
    {
        return $this->level === 5;
    }

    public function registerLogin(string $ip): void
    {
        $this->update([
            'last_login_at'       => now(),
            'last_login_ip'       => $ip,
            'failed_login_count' => 0,
            'locked_until'        => null,
        ]);
        $this->recordAudit('login', category: 'auth');
    }

    public function registerFailedLogin(): void
    {
        $max = (int) config('beha.login_throttle.max_attempts', 5);
        $decay = (int) config('beha.login_throttle.decay_minutes', 15);

        $this->failed_login_count++;
        if ($this->failed_login_count >= $max) {
            $this->locked_until = now()->addMinutes($decay);
        }
        $this->save();
        $this->recordAudit(
            action: 'login.failed',
            category: 'auth',
            severity: \App\Enums\AuditSeverity::Warning,
        );
    }
}
