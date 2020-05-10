<?php

interface Dao_User
{
    public function findUserIdForName($username);

    public function userEmailExists($mail);

    public function getUser($userid);

    public function getUserList();

    public function getUserListForDisplay();

    public function deleteUser($userid);

    public function setUser($user);

    public function setUserPassword($userarr);

    public function setUserRsaKeys($userId, $publicKey, $privateKey);

    public function getUserPrivateRsaKey($userId);

    public function addUser($user);

    public function authUser($username, $passhash);

    public function setUserAvatar($userId, $imageEncoded);

    public function getGroupPerms($groupId);

    public function getPermissions($userId);

    public function getGroupList($userId);

    public function removePermFromSecGroup($groupId, $perm);

    public function setDenyForPermFromSecGroup($groupId, $perm);

    public function addPermToSecGroup($groupId, $perm);

    public function getSecurityGroup($groupId);

    public function setSecurityGroup($group);

    public function addSecurityGroup($group);

    public function removeSecurityGroup($group);

    public function setUserGroupList($userId, $groupList);
} // Dao_User
