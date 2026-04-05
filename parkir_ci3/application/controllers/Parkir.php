<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Parkir extends CI_Controller {

    private $python_api = 'http://python-api:8000';

    public function index()
    {
        $data = $this->_call_dashboard_api('senin', 8);
        $this->load->view('dashboard', $data);
    }

    public function prediksi_ajax()
    {
        $hari = $this->input->post('hari');
        $jam  = (int) $this->input->post('jam');
        $result = $this->_call_dashboard_api($hari, $jam);
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    private function _call_dashboard_api($hari, $jam)
    {
        $url     = $this->python_api . '/dashboard';
        $payload = json_encode(['hari' => $hari, 'jam' => (int) $jam]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $err      = curl_error($ch);
        curl_close($ch);

        if ($err || !$response) {
            return $this->_fallback_data($hari, $jam);
        }

        $res = json_decode($response, true);
        if (!$res || $res['status'] !== 'success') {
            return $this->_fallback_data($hari, $jam);
        }

        return $res;
    }

    private function _fallback_data($hari, $jam)
    {
        $labels = [];
        for ($i = 0; $i < 24; $i++) $labels[] = str_pad($i, 2, '0', STR_PAD_LEFT) . ':00';
        $empty = array_fill(0, 24, 0);
        $empty_stat = ['mobil'=>0,'motor'=>0,'total'=>0,'chart_mobil'=>$empty,'chart_motor'=>$empty,
                       'total_hari'=>0,'total_mobil'=>0,'total_motor'=>0,'jam_puncak'=>'-','rata_rata'=>0];
        return [
            'status'       => 'error',
            'hari'         => ucfirst($hari),
            'jam_label'    => str_pad($jam,2,'0',STR_PAD_LEFT).':00 WIB',
            'chart_labels' => $labels,
            'rf'           => $empty_stat,
            'svr'          => $empty_stat,
            'rekomendasi'  => '⚠️ Koneksi ke Python API gagal. Pastikan FastAPI sudah berjalan.',
        ];
    }
}
