<?php
namespace App\Repositories\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;

class SocialAccountRepository extends BaseRepository
{
    // 프로퍼티 정의
    protected Model $model;
    /**
     * SocialAccount 모델 주입
     */
    public function __construct(SocialAccount $model)
    {
        $this->model = $model;
    }

    /**
     * 소셜 계정 정보로 SocialAccount 모델 조회
     *
     * @param string $provider       소셜 제공자 (google, line)
     * @param string $providerUserId 소셜 제공자에서 제공하는 사용자 ID
     * @return SocialAccount|null
     */
    public function findByProviderAndProviderUserId(
        string $provider,
        string $providerUserId
    ): ?SocialAccount {
        // provider와 provider_user_id로 소셜 계정 조회
        return $this->model
            ->where('provider', $provider)
            ->where('provider_user_id', $providerUserId)
            ->first();
    }

    /**
     * user_id로 소셜 계정 조회 
     * 1:1 관계
     */
    public function findByUserId(
        int $userId
    ): ?SocialAccount{   
        // user_id로 소셜 계정 조회
        return $this->model
            ->where('user_id', $userId)
            ->first();  
    }

    /**
     * 소셜 계정 생성
     * @param int $userId
     * @param string $provider
     * @param string $providerUserId
     * @return SocialAccount
     */
    public function createForUSer(
        int $userId,
        string $provider,
        string $providerUserId
    ): SocialAccount {
        // 새 소셜 계정 생성
        return $this->model->create([
            'user_id' => $userId,
            'provider' => $provider,
            'provider_user_id' => $providerUserId,
        ]);
    }

    /**
     * 소셜 계정 삭제
     * @param int $userId
     * @return void
     */
    public function deleteByUserId(
        int $userId
    ): void {
        $this->model
            ->newQuery()
            ->where('user_id', $userId)
            ->delete();
    }
}