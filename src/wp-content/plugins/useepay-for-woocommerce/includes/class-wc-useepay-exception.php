<?php
/*####################################################################
 # Copyright ©2020 UseePay Ltd. All Rights Reserved.                 #
 # This file may not be redistributed in whole or significant part.  #
 # This file is part of the UseePay package and should not be used   #
 # and distributed for any other purpose that is not approved by     #
 # UseePay Ltd.                                                      #
 # https://www.useepay.com                                           #
 ####################################################################*/

class WC_UseePay_Exception extends Exception
{
    private $localization_message;
    private $error_message;


    public function __construct($error_message, $localization_message)
    {
        $this->error_message;
        $this->localization_message = $localization_message;
        parent::__construct($error_message);
    }

    public function get_error_message(){
        return $this->error_message;
    }

    public function get_localization_message()
    {
        return $this->localization_message;
    }
}