<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class GroupMembershipPivot extends Pivot
{
    /**
     * The table associated with the model.
     */
    protected $table = 'group_memberships';

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'joined_at' => 'datetime',
        'permissions' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'role',
        'joined_at',
        'permissions',
    ];
}
