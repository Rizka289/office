<?php 
defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_order_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    private function selectWithSupplier()
    {
        $this->db->select('purchase_order.*, supplier.nama_supplier');
        $this->db->join('supplier', 'supplier.id = purchase_order.id_supplier', 'left');
    }

    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('purchase_order.no_po', $search)
                ->or_like('supplier.nama_supplier', $search)
                ->group_end();
        }
    }

    public function get_po_paginated($search = '', $limit = 10, $offset = 0)
    {
        $this->selectWithSupplier();
        $this->applySearchFilter($search);
        $this->db->order_by('purchase_order.id', 'DESC');
        return $this->db->get('purchase_order', $limit, $offset)->result_array();
    }

    public function count_po($search = '')
    {
        $this->applySearchFilter($search);
        return $this->db->count_all_results('purchase_order');
    }

    public function generate_po_number()
    {
        $year = date('Y');
        $count = $this->db->count_all('purchase_order') + 1;
        return 'PO/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}