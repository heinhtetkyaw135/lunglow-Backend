<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'avatar',
        'bio',
        'resisted_count',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'resisted_count' => 'integer',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ---- Relationships -----------------------------------------------------

    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }

    public function smokeLogs()
    {
        return $this->hasMany(SmokeLog::class);
    }

    public function goals()
    {
        return $this->hasMany(Goal::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }

    public function cravingLogs()
    {
        return $this->hasMany(CravingLog::class);
    }

    public function checkins()
    {
        return $this->hasMany(DailyCheckin::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function blogs()
    {
        return $this->hasMany(Blog::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'reporter_id');
    }

    public function requests()
    {
        return $this->hasMany(UserRequest::class);
    }

    public function peakCravingAlerts()
    {
        return $this->hasMany(PeakCravingAlert::class);
    }

    public function notifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function challenges()
    {
        return $this->belongsToMany(Challenge::class, 'challenge_user')
            ->withPivot('progress', 'last_log_date')
            ->withTimestamps();
    }

    // ---- Serialization ------------------------------------------------------

    // Shape returned to the SPA on login / /me.
    public function toAuthArray(): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'status' => $this->status,
            'avatar' => $this->avatar_url,
            'bio' => $this->bio,
            'resisted_count' => $this->resisted_count,
        ];
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (!$this->avatar) {
            return '/assets/avatar.png';
        }
        return \App\Support\MediaUrl::make($this->avatar);
    }
}
