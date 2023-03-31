<?php

declare (strict_types=1);
namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
/**
 * @property int $id 
 * @property string $name 
 * @property int $status 
 * @property int $is_menu 
 * @property string $menu_name 
 * @property int $menu_status 
 * @property int $menu_level 
 * @property int $parent_id 
 * @property int $menu_parent_id 
 * @property int $is_login 
 * @property string $key 
 * @property string $route 
 * @property int $level 
 * @property string $comment 
 * @property int $sort 
 * @property \Carbon\Carbon $created_at 
 * @property \Carbon\Carbon $updated_at 
 * @property string $deleted_at 
 */
class Permission extends BaseModel
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permission';
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
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'is_menu' => 'integer', 'menu_status' => 'integer', 'menu_level' => 'integer', 'parent_id' => 'integer', 'menu_parent_id' => 'integer', 'is_login' => 'integer', 'level' => 'integer', 'sort' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}