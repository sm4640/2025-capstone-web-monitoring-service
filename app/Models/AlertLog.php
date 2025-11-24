<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertLog extends Model
{
    protected $table = 'alert_logs';

    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'alert_id', 'feedback', 'plan', 'result',
    ];

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }
}
