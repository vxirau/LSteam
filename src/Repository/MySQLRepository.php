<?php
declare(strict_types=1);

namespace SallePW\SlimApp\Repository;

use DateTime;
use PDO;
use SallePW\SlimApp\Model\Search;
use SallePW\SlimApp\Model\User;
use SallePW\SlimApp\Model\Repository;
use SallePW\SlimApp\Repository\PDOSingleton;

final class MySQLRepository
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private PDOSingleton $database;

    public function __construct(PDOSingleton $database)
    {
        $this->database = $database;
    }



    public function save(User $user): bool
    {
        $query = <<<'QUERY'
        INSERT INTO User(username, email, password, birthday, phone, activated, token, created_at)
        VALUES(:username,:email, :password, :birthday,:phone,:activated, :token, :created_at)
QUERY;
        $statement = $this->database->connection()->prepare($query);
        $username = $user->username();
        $email = $user->email();
        $password = $user->password();
        $birthday = $user->birthday()->format(self::DATE_FORMAT);
        $phone = $user->phone();
        $createdAt = $user->createdAt()->format(self::DATE_FORMAT);
        $activated = FALSE;

        $token = $user->token();

        $statement->bindParam('username', $username, PDO::PARAM_STR);
        $statement->bindParam('email', $email, PDO::PARAM_STR);
        $statement->bindParam('password', $password, PDO::PARAM_STR);
        $statement->bindParam('birthday', $birthday, PDO::PARAM_STR);
        $statement->bindParam('phone', $phone, PDO::PARAM_STR);
        $statement->bindParam('activated', $activated, PDO::PARAM_BOOL);
        $statement->bindParam('token', $token, PDO::PARAM_STR);
        $statement->bindParam('created_at', $createdAt, PDO::PARAM_STR);

        $ok = $statement->execute();

        return $ok;
    }

    public function validateUser($email, $password): bool{
        $ok = true;

        $stmt = $this->database->connection()->prepare('SELECT * FROM User WHERE email=? AND password=?');
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->bindParam(2, $password, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() == 0){
            $ok = false;
        }

        return $ok;
    }

    public function getUser($usrEmail): User{


        $stmt = $this->database->connection()->prepare('SELECT * FROM User WHERE email=? OR username=?');
        $stmt->bindParam(1, $usrEmail, PDO::PARAM_STR);
        $stmt->bindParam(2, $usrEmail, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() == 1){
            $row = $stmt->fetch();

            $u = new User(
                $row['username'],
                $row['email'],
                $row['password'],
                new DateTime($row['birthday']),
                $row['phone'],
                $row['token'],
                new DateTime($row['created_at']),
                floatval($row['wallet']),
                $row['uuid']
            );

        }else{
            $u = new User(
                "TODOMAL",
                "TODOMAL",
                "TODOMAL",
                new DateTime(),
                "TODOMAL",
                "TODOMAL",
                new DateTime(),
                0.0,
                "TODOMAL"
            );

        }

        return $u;
    }

    public function checkIfEmailExists($email): bool{
        $ok = true;

        $stmt = $this->database->connection()->prepare('SELECT * FROM User WHERE email=?');
        $stmt->bindParam(1, $email, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() == 0){
            $ok = false;
        }

        return $ok;
    }

    public function checkIfUsernameExists($usr): bool{
        $ok = true;

        $stmt = $this->database->connection()->prepare('SELECT * FROM User WHERE username=?');
        $stmt->bindParam(1, $usr, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() == 0){
            $ok = false;
        }

        return $ok;
    }

    public function checkToken($token): bool{
        $ok = true;

        $stmt = $this->database->connection()->prepare('SELECT * FROM User WHERE token=?');
        $stmt->bindParam(1, $token, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() == 0){
            $ok = false;
        }

        return $ok;
    }

    public function checkActivation($token): bool{
        $ok = true;
        $validation = 0;

        $stmt = $this->database->connection()->prepare('SELECT activated FROM User WHERE token=?');
        $stmt->bindParam(1, $token, PDO::PARAM_STR);
        $stmt->execute();

        if($stmt->rowCount() == 1){
            $row = $stmt->fetch();
            if($row['activated'] == true){
                $ok = false;
            }
        }

        return $ok;
    }

    public function updateActivation($token) {
        $stmt = $this->database->connection()->prepare('UPDATE User SET activated = true, wallet = 50 WHERE token=?');
        $stmt->bindParam(1, $token, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function updatePass($user, $newPass) {
        $stmt = $this->database->connection()->prepare('UPDATE User SET password = ? WHERE username=?');
        $stmt->bindParam(1, $newPass, PDO::PARAM_STR);
        $stmt->bindParam(2, $user, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function updatePhone($user, $phone) {
        $stmt = $this->database->connection()->prepare('UPDATE User SET phone = ? WHERE username=?');
        $stmt->bindParam(1, $phone, PDO::PARAM_STR);
        $stmt->bindParam(2, $user, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function updateUuid($email, $uuid){
        $stmt = $this->database->connection()->prepare('UPDATE User SET uuid = ? WHERE username=?');
        $stmt->bindParam(1, $uuid, PDO::PARAM_STR);
        $stmt->bindParam(2, $email, PDO::PARAM_STR);
        $stmt->execute();
    }

    public function updateWallet($wallet, $email){
        $stmt = $this->database->connection()->prepare('UPDATE User SET wallet = ? WHERE username=?');
        $stmt->bindParam(1, $wallet, PDO::PARAM_STR);
        $stmt->bindParam(2, $email, PDO::PARAM_STR);
        $stmt->execute();
    }


    public function getUserGames($usrEmail) {

        $stmt = $this->database->connection()->prepare('SELECT id FROM User WHERE email=? OR username=?');
        $stmt->bindParam(1, $usrEmail, PDO::PARAM_STR);
        $stmt->bindParam(2, $usrEmail, PDO::PARAM_STR);
        $stmt->execute();
        $rowID = $stmt->fetch();
        $id = $rowID['id'];

        $stmt = $this->database->connection()->prepare('SELECT gameID FROM `User-Game-Bought` WHERE userID = ?');
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $u = [];
        $u['comprados'] = [];
        $tmp=$stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($tmp as &$value){
            foreach($value as &$kk){
                array_push($u['comprados'], $kk);
            }
        }

        $stmt = $this->database->connection()->prepare('SELECT gameID FROM `User-Game-Wishlist` WHERE userID = ?');
        $stmt->bindParam(1, $id, PDO::PARAM_INT);
        $stmt->execute();
        $u['fav'] = [];
        $tmp=$stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($tmp as &$value){
            foreach($value as &$kk){
                array_push($u['fav'], $kk);
            }
        }

        return $u;
    }
}