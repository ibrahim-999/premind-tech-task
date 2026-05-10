<?php

namespace App\Workflow\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Workflow extends Model
{
    protected $fillable = ['name', 'subject_type', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }

    public function publishedVersions(): HasMany
    {
        return $this->versions()->where('is_published', true);
    }

    public function activePublishedVersion()
    {
        return $this->publishedVersions()->orderByDesc('version_number')->first();
    }
}
