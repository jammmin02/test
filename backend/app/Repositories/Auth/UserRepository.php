<?php
    namespace App\Repositories\Auth;

    use App\Repositories\BaseRepository;
    use App\Models\User; 
    use Illuminate\Database\Eloquent\Model;

    class UserRepository extends BaseRepository
    {
        // 프로퍼티 정의
        protected Model $model;
        /**
         * User 모델 주입
         */
        public function __construct(User $model)
        {
            $this->model = $model;
        }

        /**
         * email_norm으로 user 조회
         * @param string $emailNorm
         * @return User|null
         */
        public function findByEmailNorm(
            string $emailNorm
        ): ?User {
            return $this->model
                ->where('email_norm', $emailNorm)
                ->first();
        }

        /**
         * 기본 user 생성
         * - 신규 회원가입 시 사용
         * @param string $emailNorm
         * @return User
         */
        public function createWithEmail(
            string $emailNorm
        ): User {
            return $this->model->create([
                'email_norm' => $emailNorm,
                'name' => null,
            ]);
        }      
        
    }