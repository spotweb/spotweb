<?php

/**
 * Override the Service_User_Record class so we can override userEmailExists()
 * to not require database access.
 */
class ServicesValidateUserRecord extends Services_User_Record
{
    /**
     * @param string $user
     *
     * @return Dto_FormResult
     */
    public function validateUserEmailExists($user)
    {
        $result = new Dto_FormResult();

        if (($user['mail'] == 'john@example.com') || ($user['mail'] == 'spotwebadmin@example.com')) {
            $result->addError(_('Mailaddress is already in use'));
        }

        return $result;
    }
}
