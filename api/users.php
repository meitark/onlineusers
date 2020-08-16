<?php

require_once "db.php";

use \Firebase\JWT\JWT;


class Users
{
    const REGISTERED_USERS_DB = "../db/registered_users.json";
    const LIVE_USERS_DB = "../db/live_users.json";
    const JWT_KEY = "blablabla";
    //Timeout in-case a user just closed the browser
    const LIVE_TIMEOUT_SECONDS = "10";

    public function processRequest()
    {
        //  /user/register
        //  /user/login
        //  /user/getlive
        //  /user/logout
        $requestMethod = $_SERVER["REQUEST_METHOD"];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', $uri);
        if ($uri[1] != 'api' || $uri[2] != 'user') {
            header("HTTP/1.1 404 Not Found");
            exit();
        }
        if ($requestMethod == 'GET') {
            switch ($uri[3]) {
                case 'getlive':
                    $this->authenticate();
                    $this->getliveHandler();
                    break;
                case 'getLiveUser':
                    $this->authenticate();
                    $this->getLiveUserHandler();
                    break;
                default:
                    # code...
                    break;
            }
        }
        if ($requestMethod ==  'POST') {
            switch ($uri[3]) {
                case 'register':
                    $this->registerHandler();
                    break;
                case 'login':
                    $this->loginHandler();
                    break;
                case 'getlive':
                    $this->authenticate();
                    $this->getliveHandler();
                    break;
                case 'logout':
                    $this->authenticate();
                    $this->logoutHandler();
                    break;
                default:
                    # code...
                    break;
            }
        }
    }

    public function purgeRegisteredUsers()
    {
        return unlink(Users::REGISTERED_USERS_DB);
    }

    public function purgeLiveUsers()
    {
        return unlink(Users::LIVE_USERS_DB);
    }

    public function response($code, $res)
    {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: OPTIONS,GET,POST,PUT,DELETE");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        http_response_code($code);
        echo json_encode($res);
        exit();
    }

    public function unauthorizedResponse()
    {
        $res = [
            'error' => 'Unauthorized'
        ];
        $this->response(401, $res);
    }

    public function invalidInputResponse()
    {
        $res = [
            'error' => 'Invalid input'
        ];
        $this->response(422, $res);
    }

    public function registerHandler()
    {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->invalidInputResponse();
        }
        $username = $_POST['username'];
        $password = $_POST['password'];
        $res = $this->register($username, $password);
        if ($res) {
            $this->response(200, $res);
        } else {
            //TODO: return the right error
            $this->response(500, $res);
        }
    }

    public function register($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        $db = new DB(Users::REGISTERED_USERS_DB);
        $regUsers = $db->read();
        if (false === $regUsers) {
            $regUsers = [];
        }
        //check if user already exists
        if (isset($regUsers[$username])) {
            return false;
        }
        //register
        $user = [
            'username' => $username,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created_at' => date('Y-m-d h:i:sa'),
            'logins' => 0
        ];
        $regUsers[$username] = $user;
        $db->write($regUsers);
        return true;
    }

    //TODO: consider moving this function to a utils library
    private function get_ip_address()
    {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip); // just to be safe

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
    }


    /**
     * updateUserInLiveList
     *
     * @param [type] $user
     * @return live_users_list
     */
    private function updateUserInLiveList($user)
    {
        $dbLiveUsers = new DB(Users::LIVE_USERS_DB);
        $liveUsers = $dbLiveUsers->read();
        if (false === $liveUsers) {
            $liveUsers = [];
        }
        $user['updated_at'] = date('Y-m-d h:i:sa');
        $user['ip'] = $this->get_ip_address();
        $user['ua'] = $_SERVER['HTTP_USER_AGENT'];
        //TODO: move record fields to a class of it's own for better refactoring
        $liveUsers[$user['username']] = $user;
        $liveUsers = $this->checkForTimeoutUsers($liveUsers);
        $dbLiveUsers->write($liveUsers);
        return $liveUsers;
    }

    public function getRegisteredUsers()
    {
        $dbRegUsers = new DB(Users::REGISTERED_USERS_DB);
        $regUsers = $dbRegUsers->read();
        return $regUsers;
    }

    public function setRegisteredUsers($regUsers)
    {
        //TODO: waste, need to create a member instance
        $dbRegUsers = new DB(Users::REGISTERED_USERS_DB);
        return $dbRegUsers->write($regUsers);
    }

    public function getUser($username)
    {
        if (empty($username)) {
            return false;
        }
        $regUsers = $this->getRegisteredUsers();
        if (false == $regUsers) {
            return false;
        }
        if (!isset($regUsers[$username])) {
            return false;
        }
        return $regUsers[$username];
    }

    public function getLiveUsers()
    {
        $dbLiveUsers = new DB(Users::LIVE_USERS_DB);
        $liveUsers = $dbLiveUsers->read();
        return $liveUsers;
    }

    public function getLiveUser($username)
    {
        if (empty($username)) {
            return false;
        }
        $liveUsers = $this->getLiveUsers();
        if (false == $liveUsers) {
            return false;
        }
        if (!isset($liveUsers[$username])) {
            return false;
        }
        return $liveUsers[$username];
    }

    public function getLiveUserHandler()
    {
        if (empty($_REQUEST['requested_user'])) {
            $this->invalidInputResponse();
        }
        $res = $this->getLiveUser($_REQUEST['requested_user']);
        $this->response(200, $res);
    }

    public function authenticate()
    {
        $username = isset($_REQUEST['username']) ? $_REQUEST['username'] : '';
        if (empty($username)) {
            $this->unauthorizedResponse();
        }
        try {
            switch (true) {
                case array_key_exists('HTTP_AUTHORIZATION', $_SERVER):
                    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
                    break;
                case array_key_exists('Authorization', $_SERVER):
                    $authHeader = $_SERVER['Authorization'];
                    break;
                default:
                    $authHeader = null;
                    break;
            }
            preg_match('/Bearer\s(\S+)/', $authHeader, $matches);
            if (!isset($matches[1])) {
                throw new \Exception('No Bearer Token');
            }
            $decoded = JWT::decode($matches[1], self::JWT_KEY, array('HS256'));
            if ($decoded == $username) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function loginHandler()
    {
        if (empty($_POST['username']) || empty($_POST['password'])) {
            $this->invalidInputResponse();
        }
        $username = $_POST['username'];
        $password = $_POST['password'];
        $res = $this->login($username, $password);
        if (!$res) {
            $this->response(500, $res);
        } else {
            $token = JWT::encode($username, self::JWT_KEY);
            //$decoded = JWT::decode($jwt, self::JWT_KEY, array('HS256'));
            $res = [
                'token' => $token,
            ];
            $this->response(200, $res);
        }
    }

    public function login($username, $password)
    {
        if (empty($username) || empty($password)) {
            return false;
        }
        $regUsers = $this->getRegisteredUsers();
        if (false == $regUsers) {
            return false;
        }
        if (!isset($regUsers[$username])) {
            return false;
        }
        $user = $this->getUser($username);
        $verified = password_verify($password, $user['password']);
        if ($verified) {
            $user['logins']++;
            $user['login_time'] = date('Y-m-d h:i:sa');
            $regUsers[$username] = $user;
            $res = $this->setRegisteredUsers($regUsers);
            $res = $this->updateUserInLiveList($user);
            if (!$res) {
                //some error in live users db
                //TODO: consider adding logging for the different errors
                return false;
            }
            return $user;
        }
        return false;
    }

    public function logoutHandler()
    {
        $username = $_REQUEST['username'];
        $res = $this->logout($username);
        $this->response(200, $res);
    }

    public function logout($username)
    {
        $dbLiveUsers = new DB(Users::LIVE_USERS_DB);
        $liveUsers = $dbLiveUsers->read();
        if (false === $liveUsers) {
            return true;
        }
        if (!isset($liveUsers[$username])) {
            //already logged out
            return true;
        }
        unset($liveUsers[$username]);
        $dbLiveUsers->write($liveUsers);
        return true;
    }

    public function getliveHandler()
    {
        $username = $_REQUEST['username'];
        $res = $this->getUpdateLiveUsers($username);
        $this->response(200, $res);
    }

    /**
     * getUpdateLiveUsers
     *
     * @param [type] $username
     * @return live users list (also updates username updated_at time)
     */
    public function getUpdateLiveUsers($username)
    {
        $user = $this->getUser($username);
        if (false == $user) {
            return false;
        }
        return $this->updateUserInLiveList($user);
    }

    public function checkForTimeoutUsers($liveUsers)
    {
        foreach ($liveUsers as $username => $user) {
            $userLastUpdate = strtotime($user['updated_at']);
            $now = time();
            if ($now - $userLastUpdate > self::LIVE_TIMEOUT_SECONDS) {
                unset($liveUsers[$username]);
            }
        }
        return $liveUsers;
    }
}
