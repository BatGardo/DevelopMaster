<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name','email','password'];
    protected $hidden = ['password','remember_token'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    // ...
    public function casesOwned() { return $this->hasMany(CaseModel::class,'user_id'); }
    public function casesAssigned() { return $this->hasMany(CaseModel::class,'executor_id'); }

    public function isAdmin()    { return $this->role === 'admin'; }
    public function isExecutor() { return $this->role === 'executor'; }
    public function isViewer()   { return $this->role === 'viewer'; }
    public function isApplicant(){ return $this->role === 'applicant'; }

}


