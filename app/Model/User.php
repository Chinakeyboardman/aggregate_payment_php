<?php

declare (strict_types=1);
namespace App\Model;

use Hyperf\Database\Model\SoftDeletes;

/**
 * @property int $id 
 * @property string $username 
 * @property string $password 
 * @property string $token 
 * @property string $name 
 * @property string $phone 
 * @property int $status 
 * @property int $is_admin 
 * @property int $province_id 
 * @property int $city_id 
 * @property int $area_id 
 * @property int $police_station 
 * @property string $duties 
 * @property int $role_id 
 * @property int $creator 
 * @property string $login_time 
 * @property \Carbon\Carbon $updated_at 
 * @property \Carbon\Carbon $created_at 
 * @property string $deleted_at 
 */
class User extends Model
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';
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
    protected $casts = ['id' => 'integer', 'status' => 'integer', 'is_admin' => 'integer', 'province_id' => 'integer', 'city_id' => 'integer', 'area_id' => 'integer', 'police_station' => 'integer', 'role_id' => 'integer', 'creator' => 'integer', 'updated_at' => 'datetime', 'created_at' => 'datetime'];

    protected $guarded = [];

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }
}