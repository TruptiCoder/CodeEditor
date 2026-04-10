<?php
namespace local_codejudge;

defined('MOODLE_INTERNAL') || die();

class api_client {
    private $api_url;

    public function __construct() {
        // In a real plugin, this would be a setting
        $this->api_url = get_config('local_codejudge', 'api_url');
        if (empty($this->api_url)) {
            $this->api_url = 'http://localhost:5000/judge';
        }
    }

    public function submit_code($language, $code, $input) {
        $curl = new \curl();
        $data = [
            'language' => $language,
            'code' => $code,
            'input' => $input
        ];
        
        $options = [
            'CURLOPT_HTTPHEADER' => ['Content-Type: application/json'],
            'CURLOPT_RETURNTRANSFER' => true
        ];

        // Moodle's curl class might handle JSON differently, but let's assume standard POST
        // For simplicity using raw PHP curl here to ensure JSON body is sent correctly
        // as Moodle's curl helper often sends form-data.
        
        $ch = curl_init($this->api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode != 200) {
            return ['status' => 'error', 'message' => 'API Error: ' . $httpcode];
        }

        return json_decode($response, true);
    }
}
