<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseDocument extends Model
{
    use HasFactory;
    protected $fillable = ['case_id','uploaded_by','title','path'];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
