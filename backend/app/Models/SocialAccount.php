<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAccount extends Model
{
  /**
     * 테이블명 
     * @var string
     */
    protected $table = 'social_accounts';

    /**
     * 기본키 컬럼명
     * @var string
     */
    protected $primaryKey = 'social_account_id';

    /**
     * 타임스탬프 사용 여부
     * @var bool
     */
    public $timestamps = true;

    /**
     * mass assignment 설정
     * @var array
     */
    protected $fillable = [
        'user_id',
        'provider',
        'provider_user_id',
    ];

    // SocialAccount 1:1 User
    public function user()
    {
      return $this->belongsTo(User::class, 'user_id', 'user_id');
    }
}