<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Mattiverse\Userstamps\Traits\Userstamps;
use App\Models\HIPUser;

class Document extends Model
{
    use Userstamps;

    protected $fillable = [
        'member_id',
        'document_name',
        'document_path',
        'document_type',
        'document_size',
    ];

    public function member()
    {
        return $this->belongsTo(HIPUser::class, 'member_id');
    }

    public function getDocumentUrlAttribute(): ?string
    {
        if (! $this->document_path) {
            return null;
        }

        return url('storage/' . ltrim($this->document_path, '/'));
    }
}
