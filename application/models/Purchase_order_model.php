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
        $this->db->select('
            purchase_order.*,
            supplier.nama AS nama_supplier,
            COALESCE(SUM(po_detail.sub_total), 0) AS total_nilai
        ');
        $this->db->join('supplier', 'supplier.id = purchase_order.id_supplier', 'left');
        $this->db->join('po_detail', 'po_detail.id_po = purchase_order.id', 'left');
        $this->db->group_by('purchase_order.id');
    }

    private function applySearchFilter($search)
    {
        if (!empty($search)) {
            $this->db->group_start()
                ->like('purchase_order.no_po', $search)
                ->or_like('supplier.nama', $search)
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
        $this->db->join('supplier', 'supplier.id = purchase_order.id_supplier', 'left');
        $this->applySearchFilter($search);
        return $this->db->count_all_results('purchase_order');
    }

    public function get_po_by_id($id)
    {
        $this->selectWithSupplier();
        $this->db->where('purchase_order.id', $id);
        return $this->db->get('purchase_order')->row_array();
    }

    public function generate_po_number()
    {
        $year = date('Y');
        $count = $this->db->count_all('purchase_order') + 1;
        return 'PO/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    public function get_satuan_options()
    {
        $query = $this->db->query("
            SELECT COLUMN_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'po_detail'
              AND COLUMN_NAME = 'satuan'
        ");
        $row = $query->row();

        if (!$row || empty($row->COLUMN_TYPE)) {
            return [];
        }

        preg_match_all("/'([^']+)'/", $row->COLUMN_TYPE, $matches);
        return $matches[1] ?? [];
    }

    public function insert_po($po_data, $items)
    {
        $this->db->trans_start();

        $this->db->insert('purchase_order', $po_data);
        $id_po = $this->db->insert_id();

        if ($id_po && !empty($items)) {
            $rows = [];
            foreach ($items as $item) {
                $qty   = (float) ($item['qty'] ?? 0);
                $harga = (float) ($item['price'] ?? 0);

                $rows[] = [
                    'id_po'     => $id_po,
                    'id_barang' => (int) ($item['id_barang'] ?? 0),
                    'qty'       => $qty,
                    'satuan'    => $item['unit'] ?? '',
                    'harga'     => $harga,
                    'sub_total' => $qty * $harga,
                ];
            }
            $this->db->insert_batch('po_detail', $rows);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function update_po($id_po, $po_data, $items)
    {
        $this->db->trans_start();

        // Update Header
        $this->db->where('id', $id_po);
        $this->db->update('purchase_order', $po_data);

        // Hapus detail lama & re-insert yang baru
        $this->db->where('id_po', $id_po);
        $this->db->delete('po_detail');

        if (!empty($items)) {
            $rows = [];
            foreach ($items as $item) {
                $qty   = (float) ($item['qty'] ?? 0);
                $harga = (float) ($item['price'] ?? 0);

                $rows[] = [
                    'id_po'     => $id_po,
                    'id_barang' => (int) ($item['id_barang'] ?? 0),
                    'qty'       => $qty,
                    'satuan'    => $item['unit'] ?? '',
                    'harga'     => $harga,
                    'sub_total' => $qty * $harga,
                ];
            }
            $this->db->insert_batch('po_detail', $rows);
        }

        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function delete_po($id_po)
    {
        $this->db->trans_start();
        $this->db->where('id_po', $id_po)->delete('po_detail');
        $this->db->where('id', $id_po)->delete('purchase_order');
        $this->db->trans_complete();
        return $this->db->trans_status();
    }

    public function get_po_detail($id_po)
    {
        $this->db->select('po_detail.*, barang.kode_barang, barang.nama AS nama_barang');
        $this->db->join('barang', 'barang.id = po_detail.id_barang', 'left');
        $this->db->where('po_detail.id_po', $id_po);
        return $this->db->get('po_detail')->result_array();
    }
}