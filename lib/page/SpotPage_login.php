<?php

class SpotPage_login extends SpotPage_Abs
{
    private $_loginForm;
    private $_params;

    public function __construct(Dao_Factory $daoFactory, Services_Settings_Container $settings, array $currentSession, array $params)
    {
        parent::__construct($daoFactory, $settings, $currentSession);

        $this->_loginForm = $params['loginform'];
        $this->_params = $params;
    }

    // ctor

    public function render()
    {
        $result = new Dto_FormResult('notsubmitted');

        // Check permissions
        $this->_spotSec->fatalPermCheck(SpotSecurity::spotsec_perform_login, '');

        /*
         * Create a default SpotUser so the form is always able to render
         * the values of the form
         */
        $credentials = ['username' => '',
            'password'             => '', ];

        // Instantiate the Spot user system
        $svcUserAuth = new ServiceS_User_Authentication($this->_daoFactory, $this->_settings);

        // set the page title
        $this->_pageTitle = 'spot: login';

        // bring the form action into the local scope
        $formAction = $this->_loginForm['action'];
        
        // Check redirect for chevrons, deny if found.
        if (preg_match('/[<>]/i', $this->_params['data']['performredirect'])) {			
		$result->addError(_('Script is not allowed'));
		}
        
        // Are we already submitting the form login?
        if (!empty($formAction)) {
            // make sure we can simply assume all fields are there
            $credentials = array_merge($credentials, $this->_loginForm);

            $tryLogin = $svcUserAuth->authenticate($credentials['username'], $credentials['password']);
            if (!$tryLogin) {
                /* Create an audit event */
                if ($this->_settings->get('auditlevel') != SpotSecurity::spot_secaudit_none) {
                    $spotAudit = new SpotAudit($this->_daoFactory, $this->_settings, $this->_currentSession['user'], $this->_currentSession['session']['ipaddr']);
                    $spotAudit->audit(SpotSecurity::spotsec_perform_login, 'incorrect user or pass', false);
                } // if

                $result->addError(_('Login Failed'));
            } else {
                $result->setResult('success');
                $this->_currentSession = $tryLogin;
            } // else
        } else {
            // When the user is already logged in, show this as a warning
            if ($this->_currentSession['user']['userid'] != $this->_settings->get('nonauthenticated_userid')) {
                $result->addError(_('You are already logged in'));
            } // if
        } // else

        //- display stuff -#
        $this->template('login', ['loginform' => $credentials,
            'result'                          => $result,
            'http_referer'                    => $this->_loginForm['http_referer'],
            'data'                            => $this->_params['data'], ]);
    }

    // render
} // class SpotPage_login
