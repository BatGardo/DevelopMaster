<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseAction extends Model
{
    use HasFactory;

    protected $fillable = ['case_id','user_id','type','notes'];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
