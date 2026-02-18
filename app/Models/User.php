<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Reminder;
use App\Models\SavingsGoal;
use App\Models\Tag;
use App\Models\Ticket;
use App\Models\UserDashboardWidget;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'avatar_url',
        'currency',
        'date_format',
        'language',
        'dark_mode',
        'report_frequency',
        'budget_alerts_enabled',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'dark_mode' => 'boolean',
            'budget_alerts_enabled' => 'boolean',
        ];
    }

    public function tickets()
{
    return $this->hasMany(Ticket::class);
}

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function budgets()
    {
        return $this->hasMany(Budget::class);
    }

    public function savingsGoals()
    {
        return $this->hasMany(SavingsGoal::class);
    }

    public function dashboardWidgets()
    {
        return $this->hasMany(UserDashboardWidget::class);
    }

    public function reminders()
    {
        return $this->hasMany(Reminder::class);
    }
}
