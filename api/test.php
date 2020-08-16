<?php

require_once "db.php";
require_once "users.php";

function test_db_write() {
    $db = new DB("../db/db.json");
    $db->write("hihihi");
}

function test_db_read() {
    $db = new DB("../db/db.json");
    $obj = $db->read();
    if ($obj == "hihihi") {
        echo "test_db_read() passed\n";
    } else {
        echo "test_db_read() FAILED\n";
    }
}

function test_register_user() {
    $users = new Users();
    $users->purgeRegisteredUsers();
    $res = $users->register("testuser","testpassword");
    if (!$res) {
        echo "test_register_user() FAILED\n";
    }
    //try registering existing user
    $res = $users->register("testuser","testpassword");
    if ($res) {
        echo "test_register_user() FAILED\n";
    }
    echo "test_register_user() passed\n";
}

function test_login_user() {
    $users = new Users();
    $users->purgeRegisteredUsers();
    $res = $users->register("testuser","testpassword");
    if (!$res) {
        echo "test_login_user() FAILED 1\n";
        return;
    }
    //try login with wrong password
    $res = $users->login("testuser", "blabla");
    if ($res !== false) {
        echo "test_login_user() FAILED 2\n";
        return;
    }
    $res = $users->login("testuser", "testpassword");
    if (isset($res['username'], $res['password'], $res['created_at'], $res['logins']) && $res['logins'] == 1) {
        echo "test_login_user() passed\n";
    } else {
        echo "test_login_user() FAILED 3\n";
        return;
    }
    //test 2 logins update the logins count
    $res = $users->login("testuser", "testpassword");
    if (isset($res['username'], $res['password'], $res['created_at'], $res['logins']) && $res['logins'] == 2) {
        echo "test_login_user(2) passed\n";
    } else {
        echo "test_login_user() FAILED 4\n";
        return;
    }
}

function test_getUpdateLiveUsers() {
    $users = new Users();
    $users->purgeRegisteredUsers();
    $users->purgeLiveUsers();
    $res = $users->register("testuser1","testpassword");
    if (!$res) {
        echo "test_getUpdateLiveUsers() FAILED 1\n";        
    }
    $res = $users->register("testuser2","testpassword");
    if (!$res) {
        echo "test_getUpdateLiveUsers() FAILED 2\n";        
    }
    $res = $users->register("testuser3","testpassword");
    if (!$res) {
        echo "test_getUpdateLiveUsers() FAILED 3";        
    }
    
    $res = $users->login("testuser1", "testpassword");
    if (!$res) {
        echo "test_getUpdateLiveUsers() FAILED 4";        
    }    
    $res = $users->login("testuser2", "testpassword");
    if (!$res) {
        echo "test_getUpdateLiveUsers() FAILED 5";        
    }    
    $res = $users->login("testuser3", "testpassword");
    if (!$res) {
        echo "test_getUpdateLiveUsers() FAILED 6";        
    }    
    $res = $users->getUpdateLiveUsers("testuser3");
}

function test_checkForTimeoutUsers() {
    $users = new Users();
    $users->purgeRegisteredUsers();
    $users->purgeLiveUsers();
    $res = $users->register("testuser1","testpassword");
    if (!$res) {
        echo "test_checkForTimeoutUsers() FAILED 1\n";        
    }
    $res = $users->register("testuser2","testpassword");
    if (!$res) {
        echo "test_checkForTimeoutUsers() FAILED 2\n";        
    }
    $res = $users->login("testuser1", "testpassword");
    if (!$res) {
        echo "test_checkForTimeoutUsers() FAILED 4";        
    }    
    $res = $users->login("testuser2", "testpassword");
    if (!$res) {
        echo "test_checkForTimeoutUsers() FAILED 5";        
    }
    $liveUsers = $users->getUpdateLiveUsers("testuser1");
    $liveUsers['testuser1']['updated_at'] = date('Y-m-d h:i:sa',time()-(Users::LIVE_TIMEOUT_SECONDS+1));
    $liveUsers = $users->checkForTimeoutUsers($liveUsers);
    if (isset($liveUsers['testuser2']) && !isset($liveUsers['testuser1'])) {
        //expecting to purge testuser1 from live users list
        echo "test_checkForTimeoutUsers() passed";
    } else {
        echo "test_checkForTimeoutUsers() FAILED 6";
    }
}

test_db_write();
test_db_read();
test_register_user();
test_login_user();
test_getUpdateLiveUsers();
test_checkForTimeoutUsers();