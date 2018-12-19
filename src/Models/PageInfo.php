<?php

namespace SmartHub\FbInsights\Models;

use Illuminate\Database\Eloquent\Model;

class PageInfo extends Model
{
    protected $table = "pages_info";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'page_name', 'page_id', 'page_category', 'page_access_token', 'page_insights'
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
