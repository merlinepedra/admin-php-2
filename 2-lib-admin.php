<?php
class Admin {
  // (A) CONSTRUCTOR - CONNECT TO DATABASE
  private $pdo = null;
  private $stmt = null;
  public $error = "";
  function __construct () {
    $this->pdo = new PDO(
      "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
      DB_USER, DB_PASSWORD, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
  }

  // (B) DESTRUCTOR - CLOSE CONNECTION
  function __destruct () {
    if ($this->stmt !== null) { $this->stmt = null; }
    if ($this->pdo !== null) { $this->pdo = null; }
  }

  // (C) HELPER FUNCTION - RUN SQL QUERY
  function query ($sql, $data=null) {
    $this->stmt = $this->pdo->prepare($sql);
    $this->stmt->execute($data);
  }

  // (D) GET USER BY ID OR EMAIL
  function get ($id) {
    $this->query(sprintf("SELECT * FROM `users` WHERE `%s`=?",
      is_numeric($id) ? "user_id" : "user_email"
    ), [$id]);
    return $this->stmt->fetch();
  }

  // (E) SAVE USER
  function save ($name, $email, $password, $id=null) {
    // (E1) SQL & DATA
    $sql = $id==null
      ? "INSERT INTO `users` (`user_name`, `user_email`, `user_password`) VALUES (?,?,?)"
      : "UPDATE `users` SET `user_name`=?, `user_email`=?, `user_password`=? WHERE `user_id`=?" ;
    $data = [$name, $email, password_hash($password, PASSWORD_DEFAULT)];
    if ($id!=null) { $data[] = $id; }

    // (E2) RUN SQL
    $this->query($sql, $data);
    return true;
  }

  // (F) VERIFICATION
  function verify ($email, $password) {
    // (F1) GET USER
    $user = $this->get($email);
    $pass = is_array($user);

    // (F2) CHECK PASSWORD
    if ($pass) { $pass = password_verify($password, $user["user_password"]); }

    // (F3) REGISTER MEMBER INTO SESSION
    if ($pass) {
      foreach ($user as $k=>$v) { $_SESSION["admin"][$k] = $v; }
      unset($_SESSION["admin"]["user_password"]);
    }

    // (F4) RESULT
    if (!$pass) { $this->error = "Invalid email/password"; }
    return $pass;
  }
}

// (G) DATABASE SETTINGS - CHANGE TO YOUR OWN
define("DB_HOST", "localhost");
define("DB_NAME", "test");
define("DB_CHARSET", "utf8mb4");
define("DB_USER", "root");
define("DB_PASSWORD", "");

// (H) START!
session_start();
$_ADM = new Admin();