<?php defined('BASEPATH') or exit('No direct script access allowed');

class Penerimaan_barang_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    // Mengambil semua daftar penerimaan beserta relasinya
    public function get_all_penerimaan($filter = [])
    {
        $this->db->select('
            pb.id, 
            pb.no_penerimaan, 
            pb.tanggal_terima, 
            po.no_po, 
            s.nama AS supplier, 
            u.nama AS diterima_oleh, 
            COUNT(pd.id) AS total_item,
            pb.keterangan
        ');
        $this->db->from('penerimaan_barang pb');
        $this->db->join('purchase_order po', 'po.id = pb.id_po', 'left');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->join('users u', 'u.id = pb.id_user', 'left');
        $this->db->join('penerimaan_detail pd', 'pd.id_penerimaan = pb.id', 'left');

        if (!empty($filter['q'])) {
            $this->db->group_start();
            $this->db->like('pb.no_penerimaan', $filter['q']);
            $this->db->or_like('po.no_po', $filter['q']);
            $this->db->or_like('s.nama_supplier', $filter['q']);
            $this->db->group_end();
        }

        if (!empty($filter['dari'])) {
            $this->db->where('DATE(pb.tanggal_terima) >=', $filter['dari']);
        }

        if (!empty($filter['sampai'])) {
            $this->db->where('DATE(pb.tanggal_terima) <=', $filter['sampai']);
        }

        $this->db->group_by('pb.id');
        $this->db->order_by('pb.tanggal_terima', 'DESC');

        return $this->db->get()->result();
    }

    // Mengambil data PO dan item detailnya untuk AJAX Form
    public function get_po_by_no($no_po)
    {
        $this->db->select('po.id, po.no_po, s.nama_supplier AS supplier');
        $this->db->from('purchase_order po');
        $this->db->join('supplier s', 's.id = po.id_supplier', 'left');
        $this->db->where('po.no_po', $no_po);
        $po = $this->db->get()->row();

        if (!$po) return NULL;

        // Ambil item detail PO
        $this->db->select('
            b.kode_barang AS kode, 
            b.nama_barang AS nama, 
            pod.qty AS qty_po, 
            pod.satuan
        ');
        $this->db->from('po_detail pod');
        $this->db->join('barang b', 'b.id = pod.id_barang', 'left');
        $this->db->where('pod.id_po', $po->id);
        $items = $this->db->get()->result();

        return [
            'supplier' => $po->supplier,
            'items'    => $items
        ];
    }
}