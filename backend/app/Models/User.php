<?php
namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    /**
     * 테이블명 
     * @var string
     */
    protected $table = 'users';

    /**
     * 기본키 컬럼명
     * @var string
     */
    protected $primaryKey = 'user_id';

    /**
     * 기본키 타입
     * @var string
     */
    protected $keyType = 'int';

    /**
     * timestamp 사용 여부
     * @var bool
     */
    public $timestamps = true;


    /**
     * 자동 증가 여부
     * @var bool
     */
    public $incrementing = true;

    /**
     * mass assignment 설정 
     * @var array
     */
    protected $fillable = [
        'email_norm',
        'name'
    ];


    // User 1:N Trip
    public function trips()
    {
        return $this->hasMany(Trip::class, 'user_id', 'user_id');
    }

    // User 1:1 SocialAccount
    public function socialAccount()
    {
        return $this->hasOne(SocialAccount::class, 'user_id', 'user_id');
    }
    
}