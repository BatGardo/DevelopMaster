<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseModel extends Model
{
    use HasFactory;
    protected $table = 'cases';

    protected $fillable = [
        'title','description','user_id','status','executor_id',
        'claimant_name','debtor_name','deadline_at'
    ];

    protected $casts = ['deadline_at'=>'datetime'];

    public function owner()  { return $this->belongsTo(User::class, 'user_id'); }
    public function executor(){ return $this->belongsTo(User::class, 'executor_id'); }

    public function actions(){ return $this->hasMany(CaseAction::class, 'case_id')->latest(); }
    public function documents(){ return $this->hasMany(CaseDocument::class, 'case_id')->latest(); }
}
