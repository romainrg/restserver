<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** 
 * Restserver (Librairie REST Serveur)
 * @author Yoann VANITOU
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @link https://github.com/maltyxx/restserver
 */
require(__DIR__.'/../libraries/Restserver/Restserver_interface.php');

abstract class Restserver_controller extends MY_Controller implements Restserver_interface
{
    public function __construct()
    {
        parent::__construct();
		$this->load->library('restserver');
    }

    /**
     * Remap
     * @param string $call
     * @param array $params
     */
    public function _remap($call, array $params = array())
    {
        $this->restserver->run($this, $call, $params);
    }
}

/* End of file Restserver_Controller.php */
/* Location: ./core/Restserver_Controller.php */
