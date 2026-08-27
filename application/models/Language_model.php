<?php 
/* 
    @Author: Yoceline Witaya 
*/ 
class Language_model extends CI_Model{
    function __construct(){
        parent::__construct();
    }
    
    function set_params($params){
        if ($params) {
            foreach ($params as $k => $v) {
                $this->db->where($k, $v);
            }
        }
    }

    function set_search($search){
        if ($search) {
            $n = 0;
            $this->db->group_start();
            foreach ($search as $key => $val) {
                if ($n == 0) {
                    $this->db->like($key, $val);
                } else {
                    $this->db->or_like($key, $val);
                }
                $n++;
            }
            $this->db->group_end();
        }
    }

    function set_join(){
        /* $this->db->join('','','left'); */
    }

    function set_select(){
        $this->db->select("*");
    }

    function get_all_language($params = null, $search = null, $limit = null, $start = null, $order = null, $dir = null) {
        $this->set_select();
        $this->set_params($params);
        $this->set_search($search);
        $this->set_join();

        if ($order) {
            $this->db->order_by($order, $dir);
        } else {
            $this->db->order_by('language_id', "asc");
        }

        if ($limit) {
            $this->db->limit($limit, $start);
        }
        
        return $this->db->get('languages')->result_array();
    }  
    function get_all_language_count($params,$search){
        $this->db->from('languages');
        $this->set_params($params);
        $this->set_search($search);
        return $this->db->count_all_results();
    }
    function get_all_language_item($params = null, $search = null, $limit = null, $start = null, $order = null, $dir = null) {
        $this->set_select_item();
        $this->set_params($params);
        $this->set_search($search);
        $this->set_join_item();

        if ($order) {
            $this->db->order_by($order, $dir);
        } else {
            $this->db->order_by('language_item_id', "asc");
        }

        if ($limit) {
            $this->db->limit($limit, $start);
        }
        
        return $this->db->get('language_items')->result_array();
    }  
    function get_all_language_item_count($params,$search){
        $this->db->from('language_items');
        $this->set_params($params);
        $this->set_search($search);
        return $this->db->count_all_results();
    }    

    /* 
        ================
        CRUD Language
        ================
    */        
    
    /* function to add new language */
    function add_language($params){
        $this->db->insert('languages',$params);
        return $this->db->insert_id();
    }
    
    /* function to get language by id */
    function get_language($id){
        $this->set_select();
        $this->set_join();
        return $this->db->get_where('languages',array('language_id'=>$id))->row_array();
    }
    function get_language_custom($where){
        $this->set_select();
        $this->set_join();
        return $this->db->get_where('languages',$where)->row_array();
    }
    function get_language_custom_result($where){
        $this->set_select();
        $this->set_join();
        return $this->db->get_where('languages',$where)->result_array();
    }

    /* function to update language */
    function update_language($id,$params){
        $this->db->where('language_id',$id);
        return $this->db->update('languages',$params);
    }
    function update_language_custom($where,$params){
        $this->db->where($where);
        return $this->db->update('languages',$params);
    }

    /* function to delete language */
    function delete_language($id){
        return $this->db->delete('languages',array('language_id'=>$id));
    }
    function delete_language_custom($where){
        return $this->db->delete('languages',$where);
    }

    /* function to check data exists language */
    function check_data_exist($params){
        $this->db->where($params);
        $query = $this->db->get('languages');
        if ($query->num_rows() > 0){
            return true;
        }
        else{
            return false;
        }
    }
    /* function to check data exists language of two condition */
    function check_data_exist_two_condition($where_not_in,$where_exist){
        if ($where_not_in) {
            foreach ($where_not_in as $k => $v) {
                $this->db->where($k.' !=', $v);
            }
        }
        if ($where_exist) {
            $n = 0;
            $this->db->group_start();
            foreach($where_exist as $key => $val) {
                if ($n == 0) {
                    $this->db->where($key, $val);
                } else {
                    $this->db->where($key, $val);
                }
                $n++;
            }
            $this->db->group_end();
        }
        $this->db->limit(1,0);
        $query = $this->db->get('languages');
        if ($query->num_rows() > 0){
            return true;
        }else{
            return false;
        }
    }
    
}
?>