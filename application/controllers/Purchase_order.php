<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Purchase_order extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireRole(['super_admin', 'staff_purchasing']);
        $this->load->model('Purchase_order_model');
        $this->load->model('Supplier_model');
        $this->load->model('Barang_model');
        $this->load->helper('url');
    }

    public function index()
    {
        $data['title']          = translate('app_purchasing');
        $data['page_title']     =  translate('app_purchasing');
        $data['active_menu']    = 'purchase_order';
        $data['supplier']       = $this->Supplier_model->get_all_supplier();
        $data['barang']         = $this->Barang_model->get_all_for_select();
        $data['satuan_options'] = $this->Purchase_order_model->get_satuan_options();
        $data['csrf_name']      = $this->security->get_csrf_token_name();
        $data['csrf_hash']      = $this->security->get_csrf_hash();

        $this->load->view('templates/header', $data);
        $this->load->view('staff_purchasing/po_view', $data);
        $this->load->view('templates/footer', $data);
    }

    public function list_data()
    {
        $search  = $this->input->get('search', true);
        $page    = max(1, (int) $this->input->get('page', true));
        $perPage = 10;
        $offset  = ($page - 1) * $perPage;

        $total = $this->Purchase_order_model->count_po($search);
        $data  = $this->Purchase_order_model->get_po_paginated($search, $perPage, $offset);

        $this->jsonResponse([
            'status'       => true,
            'data'         => $data,
            'total'        => (int) $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'total_pages'  => (int) ceil($total / $perPage),
        ]);
    }

    public function get_detail($id)
    {
        $po = $this->Purchase_order_model->get_po_by_id($id);
        if (!$po) {
            $this->jsonResponse(['status' => false, 'message' => 'Data PO tidak ditemukan.']);
            return;
        }

        $items = $this->Purchase_order_model->get_po_detail($id);

        $this->jsonResponse([
            'status' => true,
            'data'   => [
                'header' => $po,
                'items'  => $items
            ]
        ]);
    }

    public function simpan()
    {
        $id_po       = $this->input->post('id_po', true); // Untuk Edit (Opsional)
        $id_supplier = $this->input->post('id_supplier', true);
        $tanggal     = $this->input->post('tanggal', true);
        $note        = $this->input->post('note', true);
        $items       = $this->input->post('items');

        if (empty($id_supplier) || empty($tanggal) || empty($items)) {
            $this->jsonResponse(['status' => false, 'message' => 'Supplier, tanggal, dan minimal 1 baris detail barang wajib diisi!']);
            return;
        }

        $id_user = $this->session->userdata('id_user')
            ?: $this->session->userdata('id')
            ?: $this->session->userdata('user_id');

        if (empty($id_user)) {
            $this->jsonResponse(['status' => false, 'message' => 'Sesi login kadaluarsa. Silakan login kembali.']);
            return;
        }

        $barang_ids   = array_map('intval', array_column($this->Barang_model->get_all_barang(), 'id'));
        $satuan_valid = $this->Purchase_order_model->get_satuan_options();

        foreach ($items as $i => $item) {
            $id_barang = $item['id_barang'] ?? '';
            $qty       = $item['qty'] ?? null;
            $satuan    = $item['unit'] ?? '';

            if ($id_barang === '' || $id_barang === null || !in_array((int) $id_barang, $barang_ids, true)) {
                $this->jsonResponse(['status' => false, 'message' => 'Baris barang ke-' . ($i + 1) . ' harus dipilih dari daftar barang.']);
                return;
            }
            if ($qty === null || $qty === '' || (float) $qty <= 0) {
                $this->jsonResponse(['status' => false, 'message' => 'Qty pada baris barang ke-' . ($i + 1) . ' harus lebih dari 0.']);
                return;
            }
            if (!in_array($satuan, $satuan_valid, true)) {
                $this->jsonResponse(['status' => false, 'message' => 'Satuan (' . $satuan . ') pada baris ke-' . ($i + 1) . ' tidak valid.']);
                return;
            }
        }

        $po_data = [
            'id_supplier'         => $id_supplier,
            'id_user'             => $id_user,
            'tanggal_jatuh_tempo' => $tanggal,
            'keterangan'          => trim($note),
        ];

        if (!empty($id_po)) {
            // Mode Update / Edit
            $res = $this->Purchase_order_model->update_po($id_po, $po_data, $items);
            $msg = 'Purchase Order berhasil diperbarui';
        } else {
            // Mode Insert / Baru
            $po_data['no_po']      = $this->Purchase_order_model->generate_po_number();
            $po_data['status_qc']  = 'menunggu';
            $po_data['created_at'] = date('Y-m-d H:i:s');

            $res = $this->Purchase_order_model->insert_po($po_data, $items);
            $msg = 'Purchase Order baru berhasil disimpan';
        }

        $this->jsonResponse(
            $res
                ? ['status' => true, 'message' => $msg]
                : ['status' => false, 'message' => 'Gagal menyimpan data ke database']
        );
    }

    public function hapus($id)
    {
        if (empty($id)) {
            $this->jsonResponse(['status' => false, 'message' => 'ID Purchase Order tidak valid.']);
            return;
        }

        $deleted = $this->Purchase_order_model->delete_po($id);

        $this->jsonResponse(
            $deleted
                ? ['status' => true, 'message' => 'Purchase Order berhasil dihapus.']
                : ['status' => false, 'message' => 'Gagal menghapus Purchase Order.']
        );
    }

    private function jsonResponse($payload)
    {
        $payload['csrf_hash'] = $this->security->get_csrf_hash();
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($payload));
    }
}