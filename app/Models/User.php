<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name','email','password','role_id','is_temporary_password'
    ];

    protected $hidden = [
        'password','remember_token'
    ];

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function projects() {
        return $this->hasMany(Project::class, 'created_by');
    }

    public function isSuperAdmin() {
        // change email check to whatever you seed
        return $this->email === 'superadmin@example.com';
    }
}