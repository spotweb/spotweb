<?php

class SpotPage_createuser2 extends SpotPage_Abs
{

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession)
    {
        parent::__construct($daoFactory, $settings, $currentSession);
        if (!isset($_POST['secure']) || $_POST['secure'] != 1) {
            http_response_code(401);
            echo "Not authorized!";
            die;
        }
    } # ctor

    public function render()
    {
        if (isset($_POST['secure']) && $_POST['secure'] == 1) {

            $result = new Dto_FormResult('notsubmitted');

            /*
             * Create a default SpotUser so the form is always able to render
             * the values of the form
             */
            $spotUser = array(
                'username' => $_POST['username'],
                'firstname' => $_POST['username'],
                'lastname' => 'None',
                'mail' => $_POST['mail']
            );


            $other = array(
                "xsrfid" => "none",
                "submitcreate" => "Add",
                "action" => "create",
                "http_referer" => "https://spotweb.nzbfinder.ws/?page=createuser2"
            );

            # Instantiate the Spot usersystem
            $svcActnCreateUser = new Services_Actions_CreateUser($this->_settings, $this->_daoFactory);

            $spotUser = array_merge($spotUser, $other);
            $result = $svcActnCreateUser->createNewUser($spotUser, $this->_currentSession);

            #- display stuff -#
            $this->template('createuser2', array(
                'createuserform' => $spotUser,
                'result' => $result
            ));
        } # render
    }
} # class SpotPage_createuser2
