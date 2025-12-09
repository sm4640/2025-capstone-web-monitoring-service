<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    protected $table = 'alerts';

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'name', 'severity', 'instance', 'summary', 'callback_url', 'status', 'labels', 'annotations',
    ];

    protected $casts = [
        'labels'      => 'array',
        'annotations' => 'array',
    ];


    public function logs()
    {
        return $this->hasMany(AlertLog::class);
    }
}
