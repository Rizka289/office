<?php 
class Language extends MY_Controller{

    function __construct(){
        parent::__construct();
        $this->load->helper('cookie');     
    }

    function switch_lang($lang){
        // $this->session->set_userdata('site_lang', $lang);
        // setcookie('site_lang', $lang, time() + (86400 * 30), "/"); // 30 hari   
        make_site_lang_cookie($lang);
        redirect($_SERVER['HTTP_REFERER']);

        // $this->session->unset_userdata('site_lang');
        // $this->session->unset_userdata('lang');        
    }    
}
// $this->load->library('session');
// var_dump($this->session->userdata('site_lang'));
?>    