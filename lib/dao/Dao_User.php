<?php

interface Dao_User {

	function findUserIdForName($username);
	function userEmailExists($mail);
	function getUser($userid);
	function getUserList();
	function getUserListForDisplay();
	function deleteUser($userid);
	function setUser($user);
	function setUserPassword($user);
	function setUserRsaKeys($userId, $publicKey, $privateKey);
	function getUserPrivateRsaKey($userId);
	function addUser($user);
	function authUser($username, $passhash);
	function setUserAvatar($userId, $imageEncoded);
	function getGroupPerms($groupId);
	function getPermissions($userId);
	function getGroupList($userId);
	function removePermFromSecGroup($groupId, $perm);
	function setDenyForPermFromSecGroup($groupId, $perm);
	function addPermToSecGroup($groupId, $perm);
	function getSecurityGroup($groupId);
	function setSecurityGroup($group);
	function addSecurityGroup($group);
	function removeSecurityGroup($group);
	function setUserGroupList($userId, $groupList);

} # Dao_User
