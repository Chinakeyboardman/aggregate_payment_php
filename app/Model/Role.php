<?php

declare (strict_types=1);
namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;

/**
 * @property int $id 
 * @property string $name 
 * @property int $status 
 * @property int $level 
 * @property int $creator_id 
 * @property string $permissions 
 * @property string $comment
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $deleted_at 
 */
class Role extends BaseModel
{
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];
    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'level' => 'integer', 'creator_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}