<?php

declare (strict_types=1);
namespace App\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\SoftDeletes;
/**
 * @property int $id 
 * @property string $title 
 * @property string $original_name 
 * @property string $filename 
 * @property string $path 
 * @property string $type 
 * @property int $size 
 * @property string $module 
 * @property string $md5 
 * @property int $user_id 
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property string $deleted_at 
 */
class File extends BaseModel
{
    use SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'file';
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
    protected $casts = ['id' => 'integer', 'size' => 'integer', 'user_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
    public function getList($where = [], $order = [], $offset = 0, $limit = 0) : array
    {
        $query = $this->query()->select($this->table . '.id', $this->table . '.title', $this->table . '.original_name', $this->table . '.filename', $this->table . '.path', $this->table . '.type', $this->table . '.size', $this->table . '.user_id', $this->table . '.created_at');
        // 循环增加查询条件
        foreach ($where as $k => $v) {
            if ($v) {
                $query = $query->where($this->table . '.' . $k, $v);
            }
        }
        // 追加排序
        if ($order && is_array($order)) {
            foreach ($order as $k => $v) {
                $query = $query->orderBy($this->table . '.' . $k, $v);
            }
        }
        // 是否分页
        if ($limit) {
            $query = $query->offset($offset)->limit($limit);
        }
        $query = $query->get();
        return $query ? $query->toArray() : [];
    }
}