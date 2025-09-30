<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class CaseDocument extends Model
{
    use HasFactory;

    protected $fillable = ['case_id', 'uploaded_by', 'title', 'path', 'file_size', 'mime_type'];

    protected $casts = [
        'file_size' => 'integer',
    ];

    protected $appends = ['human_size'];

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getHumanSizeAttribute(): ?string
    {
        if (! $this->file_size) {
            return null;
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($this->file_size, 0);
        $pow = $bytes > 0 ? floor(min(count($units) - 1, log($bytes, 1024))) : 0;
        $pow = is_finite($pow) ? $pow : 0;
        $bytes /= pow(1024, $pow);

        $decimals = $pow === 0 ? 0 : 1;

        return number_format($bytes, $decimals) . ' ' . $units[$pow];
    }
}
