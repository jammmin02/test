<?php
// namespace App\Core;
namespace Tripmate\Backend\Core;

// PDO 클래스 로드
use PDO;

// 1. DB 연결 관리를 위한 클래스 작성
class DB {
  // DB 접속 메서드
  public function getConnection() {
  // 1. DB 접속 정보
  $host = getenv('DB_HOST') ?: 'localhost';
  $db = getenv('DB_NAME') ?:'tripmate';
  $user = getenv('DB_USER') ?:'root';
  $pass = getenv('DB_PASSWORD') ?:'1234';
  
  // 2. DSN (Data Source Name) 작성 
  $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
  
  // 3. PDD 연결 시도 (에러는 임시로 간단하게 처리)
  $pdo = new PDO($dsn, $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

  return $pdo;
}

}

