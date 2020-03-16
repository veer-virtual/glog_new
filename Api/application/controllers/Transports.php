<?php defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Transports extends REST_Controller
{

    public function __construct()
    {

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        // Construct our parent class
        parent::__construct();
        $this->load->model('transports_model');
        $this->load->library('image_lib');
        $this->load->helper('url');
        $this->load->library('email');
        $this->load->library('session');
        $this->load->library('encrypt');
        $this->load->helper('common');

        $this->methods['user_get']['limit'] = 500; //500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; //100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; //50 requests per hour per user/key
    }     
  

	//check driver NFC_TAG_ID
    public function check_driver_tag_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $nfc_tag = trim($userid->nfc_tag_id);
        $result = $this->transports_model->check_tag($nfc_tag);
        if ($result) {
            $message = array('status' => 1, 'msg' => 'Tag ID verified!', 'data' => $result[0]);
            $this->response($message, 200);
        } else {
            $obj = new \stdClass();
            $message = array('status' => 0, 'msg' => 'Invalid driver..', 'data' => $obj);
            $this->response($message, 200);
        }
    }
	   

	   
	 //Driver Transport Jobs
    public function transport_job_list_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $driver_tag = trim($details->driver_id);

        $jobs = $this->transports_model->get_driver_job_list($driver_tag);
        if ($jobs) {
            $message = array('status' => 1, 'msg' => 'Job Details..', 'data' => $jobs);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'No Jobs found', 'data' => array());
            $this->response($message, 200);
        }
    }

	 //Driver Transport Jobs
    public function transport_job_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $driver_tag = trim($details->driver_id);

        $jobs = $this->transports_model->get_driver_job($driver_tag);
        if($jobs == 2){
            $message = array('status' => 2, 'msg' => 'Invalid driver..', 'data' => array());
            $this->response($message, 200);
        }else if ($jobs) {
            $message = array('status' => 1, 'msg' => 'Job Details..', 'data' => $jobs);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'No Jobs found', 'data' => array());
            $this->response($message, 200);
        }
    }
		   
		   
	   
	//Add driver notes for transport job
    public function add_driver_notes_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $job_number = trim($userid->job_number);
        $driver_nfc_id = trim($userid->driver_id);
        $notes = trim($userid->notes);

        $result = $this->transports_model->driver_note($job_number, $driver_nfc_id, $notes);

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Notes added!', 'data' => array());
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'Failure..', 'data' => array());
            $this->response($message, 200);
        }
    }
	
	
	   
	//Add driver documents for transport job
    public function add_driver_documents_post()
    {
        // echo "<pre>";
        // print_r($_FILES['docs']['name']);exit;
        $job_number = $this->input->post('job_number');
        $driver_id = $this->input->post('driver_id');
        $files = $_FILES['docs'];
        $path = $_SERVER['DOCUMENT_ROOT'] . '/glogApi/driver_documents/';
        $docs = $this->upload_files($path, $files);
        
        $result = $this->transports_model->driver_document($job_number, $driver_id, $docs);
        // echo $docs;exit;
        if(empty($docs)){
                $message = array('status' => 2, 'msg' => 'Invalid attachment', 'data' => array());
                $this->response($message, 200);
        }
        else{
            if ($result) {
                $message = array('status' => 1, 'msg' => 'Documents added!', 'data' => array());
                $this->response($message, 200);
            } else {
                $message = array('status' => 0, 'msg' => 'Failure..', 'data' => array());
                $this->response($message, 200);
            }
        }
    }
		   
		   
     //Change status of driver transport job
     // Types can be : 
     // start : to start the job
     // finish : to finish the job
    public function driver_job_status_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $job_number = trim($userid->job_number);
        $driver_nfc_id = trim($userid->driver_id);
        $type = trim($userid->type);
        $time = trim($userid->scan_time);
        $currentJType = trim($userid->currentJType);
        // -: latitude and longitude :-
        $lat = trim($userid->lat);
        $long = trim($userid->long);
        $result = $this->transports_model->job_status($job_number, $driver_nfc_id, $type, $time, $lat, $long, $currentJType);

        $driverData = $this->transports_model->get_driver_job($driver_nfc_id);

        $Data = $driverData ? $driverData : array();

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Finish current driver before starting new driver..', 'data' => $Data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Job is paused..', 'data' => $Data);
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Job started!', 'data' => $Data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Job is not started, it is allocated..', 'data' => $Data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Job finished..', 'data' => $Data);
            $this->response($message, 200);
        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Driver changed..', 'data' => $Data);
            $this->response($message, 200);
        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Driver is already changed..', 'data' => $Data);
            $this->response($message, 200);
        } elseif ($result == 8) {
            $message = array('status' => 8, 'msg' => 'Job is already finished..', 'data' => $Data);
            $this->response($message, 200);
        } elseif ($result == 9) {
            $message = array('status' => 9, 'msg' => 'Driver not assigned for this job..', 'data' => array());
            $this->response($message, 200);
        } elseif ($result == 10) {
            $message = array('status' => 10, 'msg' => 'Current job type not found..', 'data' => array());
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'Failure..', 'data' => array());
            $this->response($message, 200);
        }
    }
	  
	   
	   
		 //Pause driver transport job
    public function driver_job_pause_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $job_number = trim($userid->job_number);
        $driver_nfc_id = trim($userid->driver_id);
        $currentJType = trim($userid->currentJType);
        $type = trim($userid->type);
        $time = trim($userid->scan_time);

        $result = $this->transports_model->pause_job($job_number, $driver_nfc_id, $type, $time, $currentJType);

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Job paused..', 'data' => array());
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Job is already paused..', 'data' => array());
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Job resumed!', 'data' => array());
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Job is already started..', 'data' => array());
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Current job type not found..', 'data' => array());
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failure..', 'data' => array());
            $this->response($message, 200);
        }
    }

    private function upload_files($path, $files)
    {
        setlocale(LC_ALL,'en_US.UTF-8');
        $config_tickets = array('upload_path' => $path, 'allowed_types' => 'jpg|JPG|jpeg|JPEG|gif|GIF|png|PNG|pdf|PDF|docs|doc|docx', 'overwrite' => 1);
        $this->load->library('upload', $config_tickets);
        $tickets = array();
        foreach ($files['name'] as $key => $ticket) {
            $_FILES['tickets[]']['name'] = $files['name'][$key];
            $_FILES['tickets[]']['type'] = $files['type'][$key];
            $_FILES['tickets[]']['tmp_name'] = $files['tmp_name'][$key];
            $_FILES['tickets[]']['error'] = $files['error'][$key];
            $_FILES['tickets[]']['size'] = $files['size'][$key];
            // return $_FILES;
            $ext = pathinfo($ticket, PATHINFO_EXTENSION);
            $fileName = time() . '_' .$key.'.'.$ext;
            $tickets[] = str_replace(' ', '_', $fileName);
            
            $config_tickets['file_name'] = $fileName;
            $this->upload->initialize($config_tickets);
            if ($this->upload->do_upload('tickets[]')) {
                $this->upload->data();
            } else {
                return false;
            }
        }
        return $tickets;
    }

}
?>