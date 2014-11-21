<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @version 1.0.3 (20141120)
 */
class MY_Controller extends CI_Controller {

    public function __construct() {
        parent::__construct();
    }
    
}

require(APPPATH.'/core/Restserver_Controller.php');

/* End of file MY_Controller.php */
/* Location: ./system/core/MY_Controller.php */
