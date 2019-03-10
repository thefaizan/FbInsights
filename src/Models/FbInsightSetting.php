<?php

namespace SmartHub\FbInsights\Models;

use Illuminate\Database\Eloquent\Model;

class FbInsightSetting extends Model
{
    protected $table = "fbinsights.settings";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'meta_key', 'page_insights'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        // 'password', 'remember_token',
    ];
}
