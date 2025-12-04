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
        'alert_id', 'feedback', 'plan', 'result', 'is_approve'
    ];

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }
    
    public function getPlanJsonAttribute()
    {
        if (empty($this->plan)) {
            return null;
        }

        $decoded = json_decode($this->plan, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;    // 혹시 예전 데이터가 그냥 텍스트일 수도 있으니
        }

        return $decoded;
    }

    public function getResultJsonAttribute()
    {
        if (empty($this->result)) {
            return null;
        }

        $decoded = json_decode($this->result, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            return null;
        }

        return $decoded;
    }
}
