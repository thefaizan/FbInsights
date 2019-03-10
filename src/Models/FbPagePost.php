<?php

namespace SmartHub\FbInsights\Models;

use Illuminate\Database\Eloquent\Model;

class FbPagePost extends Model
{
    protected $table = "fbinsights.page_posts";

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'post_id', 'page_id', 'post_insights'
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
