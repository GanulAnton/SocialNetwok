<?php

namespace App\Models;

use GoldSpecDigital\LaravelEloquentUUID\Database\Eloquent\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory, Uuid;

    protected $withCount = [
        'likes',
    ];

    protected $fillable = [
        'user_id',
        'text',
    ];

    /**
     * The "type" of the auto-incrementing ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public function likes(){

        return $this->belongsToMany(User::class,'likes','post_id','user_id');
    }

    public function attaches()
    {
        return $this->hasMany(Attach::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
