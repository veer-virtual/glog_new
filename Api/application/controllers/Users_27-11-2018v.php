<?php defined('BASEPATH') or exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH . '/libraries/REST_Controller.php';

class Users extends REST_Controller
{

    public function __construct()
    {

        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST');
        // Construct our parent class
        parent::__construct();
        $this->load->model('users_model');
        $this->load->library('image_lib');
        $this->load->helper('url');
        $this->load->library('email');
        $this->load->library('session');
        $this->load->library('encrypt');
        $this->methods['user_get']['limit'] = 500; //500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; //100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; //50 requests per hour per user/key
    }     
  

	//check NFC_TAG_ID
    public function check_nfc_tag_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $nfc_tag = trim($userid->nfc_tag_id);

        $result = $this->users_model->check_tag($nfc_tag);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Tag ID verified!', 'data' => $result[0]);
            $this->response($message, 200);


        } else {
            $message = array('status' => 0, 'msg' => 'Failure..', 'data' => array());
            $this->response($message, 300);
        }
    }
		   
		   
    //check collector type to Override
    public function check_collector_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $nfc_tag = trim($userid->nfc_tag_id);

        $result = $this->users_model->check_collector_1($nfc_tag);

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'C1 collector tag verified!');
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'C2 collector..');
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failure..', 'data' => array());
            $this->response($message, 300);
        }
    }

		   
		   
      //Get Collectors List
    public function collectors_list_get()
    {
        $collectors = $this->users_model->all_collector();

        if ($collectors) {
            $message = array('status' => 1, 'msg' => 'Collectors List', 'data' => $collectors);
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'No collector found', 'data' => array());
            $this->response($message, 300);
        }
    }
		   
		   
	  //Get Collectors List
    public function block_rows_get()
    {   
        // $block_rows = ["raja" => "aaja"];
        $block_rows = $this->users_model->all_block_row();
        header("Content-Type:Application/json");
        // $this->response($block_rows, 200); exit;
        if ($block_rows) {
            $message = array('status' => 1, 'msg' => 'Rows List', 'data' => $block_rows);
            http_response_code(200);
            echo json_encode($message);
            // $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'No Rows found', 'data' => array());
            http_response_code(300);
            echo json_encode($message); 
            // $this->response($message, 300);
        }
    }
  
	
	 //Get Job Data 
    public function job_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $collector_tag = trim($userid->collector_id);

        $jobs = $this->users_model->get_collector_job($collector_tag);
        if ($jobs) {
            // return json_encode($jobs);
            $message = array('status' => 1, 'msg' => 'Job Details..', 'data' => $jobs);
            // $this->response($message, 200);
            header('Content-Type: application/json');
            http_response_code(200);
            echo json_encode($message);

        } else {
            $message = array('status' => 0, 'msg' => 'No Jobs found', 'data' => array());
            // $this->response($message, 300);
            header('Content-Type: application/json');
            http_response_code(300);
            echo json_encode($message);
        }
    }
		   		   
		   
		   
	//Get Collector Data
    public function collector_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $nfc_tag_id = trim($userid->nfc_tag_id);
        $collector_details = $this->users_model->get_collector($nfc_tag_id);

        if ($collector_details) {
            $message = array('status' => 1, 'msg' => 'Collectors Data', 'data' => $collector_details[0]);
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'No collector found', 'data' => array());
            $this->response($message, 300);
        }
    }
		   
		   
	  //Get Collector Data
    public function worker_post()
    {
        $data = file_get_contents('php://input');
        $userid = json_decode($data);

        $nfc_tag_id = trim($userid->nfc_tag_id);
        $worker_details = $this->users_model->get_worker($nfc_tag_id);

        if ($worker_details) {
            $message = array('status' => 1, 'msg' => 'Worker Data', 'data' => $worker_details[0]);
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'No worker found', 'data' => array());
            $this->response($message, 300);
        }
    }
		   
		   
	 //Collector time for unit job
    public function collector_unit_time_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $collector_id = trim($details->collector_id);
        $device_id = trim($details->device_id);
        $type = trim($details->type);
        $time = trim($details->scan_time);

        $result = $this->users_model->collector_unit_time($job_number, $collector_id, $device_id, $type, $time);

        $this->db->select('*');
        $this->db->from('picking_details_collector');
        $this->db->where('job_number', $job_number);
        $job = $this->db->get();

        if (count($job->result()) > 0) {

            $started_jobs = 0;

            foreach ($job->result() as $val_job) {

                if ($val_job->status == 'started') {
                    $started_jobs = $started_jobs + 1;
                }
            }
            if ($started_jobs > 0) {

                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'active'));

            } elseif ($started_jobs <= 0) {

                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'finished'));

            }

        }


        $this->db->select('*');
        $this->db->from('picking_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('collector_id', $collector_id);
        $this->db->where('device_id', $device_id);
        $this->db->where('status', 'started');
        $query = $this->db->get();

        if (count($query->result()) > 0) {
            $collector_id = $query->row()->collector_id;
        } else {

            $this->db->select('*');
            $this->db->from('picking_details_collector');
            $this->db->where('job_number', $job_number);
            $this->db->where('device_id', $device_id);
            $this->db->order_by('start_time', 'desc');
            $this->db->limit(1);
            $res = $this->db->get();

            $collector_id = $res->row()->collector_id;
        }

        // $this->db->select('*');
        // $this->db->from('collector');
        // $this->db->where('nfc_tag_id', $collector_id);
        // $query = $this->db->get();
        // $data = $query->result()[0];

        $this->db->select('*');
        $this->db->from('collector as c');
        $this->db->join('picking_details_collector as p', 'c.nfc_tag_id = p.collector_id');
        $this->db->where('c.nfc_tag_id', $collector_id);
        $this->db->where('p.job_number', $job_number);
        $this->db->where('p.device_id', $device_id);
        $query = $this->db->get();
        $data = $query->result()[0];

        $this->db->select('*');
        $this->db->from('bucket_scan');
        $this->db->where('job_number', $job_number);
        $this->db->order_by('last_sync', 'desc');
        $this->db->limit(1);
        $buck = $this->db->get();

        $last_sync = $buck->row()->last_sync;

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Collector Job started!', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Job started by same collector..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Collector Job is already finished..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Collector Job finished!', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Collector Job is not started!', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Invalid Collector..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Job started by another collector..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 8) {
            $message = array('status' => 8, 'msg' => 'Collector needs to finish the first job..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 9) {
            $message = array('status' => 9, 'msg' => 'Job is paused, Collector needs to reset the job..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 10) {
            $message = array('status' => 10, 'msg' => 'Job is paused by another collector..', 'last_sync' => $last_sync, 'data' => $data);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    } 
		  

	   //Collector time for row job
    public function collector_row_time_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $job_number = trim($details->job_number);
        $collector_id = trim($details->collector_id);
        $device_id = trim($details->device_id);
        $type = trim($details->type);
        $time = trim($details->scan_time);

        $result = $this->users_model->collector_row_time($job_number, $collector_id, $device_id, $type, $time);

        $this->db->select('*');
        $this->db->from('row_details_collector');
        $this->db->where('job_number', $job_number);
        $job = $this->db->get();

        if (count($job->result()) > 0) {

            $started_jobs = 0;

            foreach ($job->result() as $val_job) {

                if ($val_job->status == 'started') {
                    $started_jobs = $started_jobs + 1;
                }
            }
            if ($started_jobs > 0) {

                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'active'));

            } elseif ($started_jobs <= 0) {

                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'finished'));

            }

        }


        $this->db->select('*');
        $this->db->from('row_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('collector_id', $collector_id);
        $this->db->where('device_id', $device_id);
        $this->db->where('status', 'started');
        $query = $this->db->get();

        if (count($query->result()) > 0) {
            $collector_id = $query->row()->collector_id;
        } else {

            $this->db->select('*');
            $this->db->from('row_details_collector');
            $this->db->where('job_number', $job_number);
            $this->db->where('device_id', $device_id);
            $this->db->order_by('start_time', 'desc');
            $this->db->limit(1);
            $res = $this->db->get();

            $collector_id = $res->row()->collector_id;
        }

        // $this->db->select('*');
        // $this->db->from('collector');
        // $this->db->where('nfc_tag_id', $collector_id);
        // // $this->db->where('status !=', 'finished');
        // $query = $this->db->get();
        // $data = $query->result()[0];

        $this->db->select('*');
        $this->db->from('collector as c');
        $this->db->join('row_details_collector as r', 'c.nfc_tag_id = r.collector_id');
        $this->db->where('c.nfc_tag_id', $collector_id);
        $this->db->where('r.job_number', $job_number);
        $this->db->where('r.device_id', $device_id);
        $query = $this->db->get();
        $data = $query->result()[0];

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Collector Job started!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Job started by same collector..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Collector Job is already finished..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Collector Job finished!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Collector Job is not started!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Invalid Collector..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Job started by another collector..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 8) {
            $message = array('status' => 8, 'msg' => 'Collector needs to finish the first job..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 9) {
            $message = array('status' => 9, 'msg' => 'Job is paused, Collector needs to reset the job..', 'data' => $data);
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }  
		   
	 
	 //Worker time for job_per_row
    public function worker_time_job_per_row_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $worker = trim($details->worker);
        $type = trim($details->type);
        $time = trim($details->scan_time);

        $result = $this->users_model->worker_time_row($job_number, $worker, $type, $time);

        $this->db->select('row_details_worker.* , worker.worker_name');
        $this->db->from('row_details_worker');
        $this->db->join('worker', 'worker.nfc_tag_id = row_details_worker.worker_id');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker);
        $res = $this->db->get();
        $data = $res->result()[0];

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Worker Job started!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Invalid Worker..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Job already finished..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Job already started.', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Worker Job finished!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Job is paused!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Worker job not started..', 'data' => $data);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }

    /* ----------------------------------------------------------
    | Getting Data on row scan Time
    |------------------------------------------------------------
    | 
     */
    public function row_scan_time_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $job_number = trim($details->job_number);
        $worker = trim($details->worker);
        $collector = trim($details->collector);
        $row = trim($details->row);
        $type = trim($details->type);
        $time = trim($details->scan_time);
        $deviceId = trim($details->device_id);

        // it is used for response purpose:
        $result = $this->users_model->row_time($job_number, $worker, $collector, $row, $type, $time, $deviceId);
        if ($result != 2) {
                 // This is the worker data show if $row is empty
            $this->db->select('row_details_worker.* , worker.worker_name');
            $this->db->from('row_details_worker');
            $this->db->join('worker', 'worker.nfc_tag_id = row_details_worker.worker_id');
            $this->db->where('job_number', $job_number);
            $this->db->where('worker_id', $worker);
            $mData = $this->db->get();
            $query1 = $mData->row();

            // $res = $this->users_model->get_row_buckets_num($job_number, $worker);
            // if ($res) {
            //     if ($res->extra_units > $query1->extra_units) {
            //         $query1->extra_units = $res->extra_units - ($res->nTime - 1) * $query1->extra_units;
            //     }
            //     if ($res->duplicates > $query1->duplicates) {
            //         $query1->duplicates = $res->duplicates - ($res->nTime - 1) * $query1->duplicates;
            //     }

            //     if ($res->overrides > $query1->overrides) {
            //         $query1->overrides = $res->overrides - ($res->nTime - 1) * $query1->overrides;
            //     }
            // }

            if ($row != '') {
                $this->db->select('row_scan.*, worker.nfc_tag_id, worker.worker_name');
                $this->db->from('row_scan');
                $this->db->join('worker', 'worker.nfc_tag_id = row_scan.worker_id');
                $this->db->where('row_scan.row_tag_id', $row);
                $this->db->order_by('row_scan.last_scan', 'desc');
                $this->db->limit(1);
                $query = $this->db->get();	//second latest worker scanned for row
                $query1->last_worker_row_tag_id = $query->row()->row_tag_id;
                $query1->last_worker_nfc_tag_id = $query->row()->nfc_tag_id;
                $query1->last_worker_worker_name = $query->row()->worker_name;
            } else {

                $query1->last_worker_row_tag_id = '';
                $query1->last_worker_nfc_tag_id = '';
                $query1->last_worker_worker_name = '';
            }
            $rowData = $query1;

            $rowData->last_worker_row_tag_id = $val->last_worker_row_tag_id;
            $rowData->last_worker_nfc_tag_id = $val->last_worker_nfc_tag_id;
            $rowData->last_worker_worker_name = $val->last_worker_worker_name;
            if ($row != "") {
                $rowData->row_tag_id = $query->row()->row_tag_id;
                $rowData->row_start_time = $query->row()->start_time;
                $rowData->row_time_worked = $query->row()->time_worked;
                $rowData->row_finish_time = $query->row()->finish_time;
                $rowData->row_last_scan = $query->row()->last_scan;
                $rowData->row_status = $query->row()->status;
                $rowData->last_sync = $query->row()->last_sync;
            } else {
                $rowData->row_tag_id = "";
                $rowData->row_start_time = "";
                $rowData->row_time_worked = "";
                $rowData->row_finish_time = "";
                $rowData->row_last_scan = "";
                $rowData->row_status = "";
                $rowData->last_sync = "";
            }
            $data = $rowData;
        }

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Worker scanned!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Invalid Worker..');
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Invalid Row..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Row already started by same worker..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Row started!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Row already started by another worker..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Row is not started..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 8) {
            $message = array('status' => 8, 'msg' => 'Row Finished!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 9) {
            $message = array('status' => 9, 'msg' => 'Row already finished, cannot be unallocated..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 10) {
            $message = array('status' => 10, 'msg' => 'Row Unallocated!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 11) {
            $message = array('status' => 11, 'msg' => 'Row Unfinished!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 12) {
            $message = array('status' => 12, 'msg' => 'Row is not finished!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 13) {
            $message = array('status' => 13, 'msg' => 'Same worker can only start an unfinished row!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 14) {
            $message = array('status' => 14, 'msg' => 'Row already finished!', 'data' => $data);
            $this->response($message, 200);

        }
        elseif ($result == 15) {
            $message = array('status' => 15, 'msg' => 'The job is paused!', 'data' => $data);
            $this->response($message, 200);

        }  else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }


    public function row_details_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $row = trim($details->row);

        $result = $this->users_model->row_data($job_number, $row);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Success', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    } 
		    
		   
		   
	//Worker time for job_per_bucket
    public function worker_time_per_bucket_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $worker = trim($details->worker);
        $type = trim($details->type);
        $time = trim($details->scan_time);

        $result = $this->users_model->worker_time_bucket($job_number, $worker, $type, $time);

        $this->db->select('picking_details_worker.* , worker.worker_name');
        $this->db->from('picking_details_worker');
        $this->db->join('worker', 'worker.nfc_tag_id = picking_details_worker.worker_id');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker);
        $res = $this->db->get();
        $data = $res->result()[0];

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Worker Job started!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Invalid Worker..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Job already finished..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Job already started..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Worker Job finished!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Job is paused..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Worker job not started..', 'data' => $data);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
    
    //Bucket scan time
    public function bucket_scan_time_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $job_number = trim($details->job_number);
        $worker = trim($details->worker);
        $collector = trim($details->collector);
        $bucket = trim($details->bucket);
        $time = trim($details->scan_time);
        $deviceId = trim($details->device_id);

        $result = $this->users_model->bucket_time($job_number, $worker, $collector, $bucket, $time, $deviceId);

        if ($result != 2) {
        //Worker tag is scanned instead of bucket tag  
            $this->db->select('*');
            $this->db->from('worker');
            $this->db->where('nfc_tag_id', $bucket);
            $work = $this->db->get();

            if (count($work->result()) > 0) {
                $this->db->select('*');
                $this->db->from('picking_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('worker_id', $bucket);
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    $worker = $bucket;
                }
            }

            $this->db->select('pdw.* , worker.worker_name');
            $this->db->from('picking_details_worker as pdw');
            $this->db->join('worker', 'worker.nfc_tag_id = pdw.worker_id', 'left');
            $this->db->where('pdw.job_number', $job_number);
            $this->db->where('pdw.worker_id', $worker);
            $query1 = $this->db->get()->row();

            if ($bucket != '') {

                $this->db->select('bucket_scan.bucket_tag_id, bucket_scan.last_scan , worker.nfc_tag_id, worker.worker_name');
                $this->db->from('bucket_scan');
                $this->db->join('worker', 'worker.nfc_tag_id = bucket_scan.worker_id');
                $this->db->where('bucket_scan.bucket_tag_id', $bucket);
                $this->db->order_by('bucket_scan.last_scan', 'desc');
                $this->db->limit(1);
                $query = $this->db->get();	//second latest worker scanned for bucket
                if($query->row()->last_scan){
                    $query1->last_scan = $query->row()->last_scan;
                }
                $query1->last_worker_bucket_tag_id = $query->row()->bucket_tag_id;
                $query1->last_worker_nfc_tag_id = $query->row()->nfc_tag_id;
                $query1->last_worker_worker_name = $query->row()->worker_name;

            } else {
                $query1->last_worker_bucket_tag_id = '';
                $query1->last_worker_nfc_tag_id = '';
                $query1->last_worker_worker_name = '';
            }
            $data = $query1;
        }

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Worker scanned!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Invalid Worker..');
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Duplicate bucket, please scan Collector..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'C1 Collector not scanned..', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 5) {
            $message = array('status' => 5, 'msg' => 'Bucket scanned!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 6) {
            $message = array('status' => 6, 'msg' => 'Collector scanned, duplicate bucket scanned!', 'data' => $data);
            $this->response($message, 200);

        } elseif ($result == 7) {
            $message = array('status' => 7, 'msg' => 'Job is paused..', 'data' => $data);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		   
		   
	   //Pause Row job
    public function pause_job_row_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $type = trim($details->type);
        $time = trim($details->scan_time);

        $result = $this->users_model->break_time_job_row($job_number, $type, $time);

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Job paused!', "lastHitTime" => $time);
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Job is already paused by another collector! Please wait a while to sync.', "lastHitTime" => $time);
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Job is already resumed by another collector! Please wait a while to sync.', "lastHitTime" => $time);
            $this->response($message, 200);

        } elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Job reset!', "lastHitTime" => $time);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..', "lastHitTime" => $time);
            $this->response($message, 300);
        }
    }
		   
		   
		   
		//Pause Bucket job
    public function pause_job_bucket_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $type = trim($details->type);
        $time = trim($details->scan_time);

        $result = $this->users_model->break_time_job_bucket($job_number, $type, $time);

        if ($result == 1) {
            $message = array('status' => 1, 'msg' => 'Job paused!');
            $this->response($message, 200);

        } elseif ($result == 2) {
            $message = array('status' => 2, 'msg' => 'Job is not started!');
            $this->response($message, 200);

        } elseif ($result == 3) {
            $message = array('status' => 3, 'msg' => 'Job is not paused!');
            $this->response($message, 200);

        }elseif ($result == 4) {
            $message = array('status' => 4, 'msg' => 'Job reset!', "lastHitTime" => $time);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..', "lastHitTime" => $time);
            $this->response($message, 300);
        }
    }
		   	


	 //Unallocate bucket scan
    public function unallocate_bucket_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $bucket = trim($details->bucket);

        $result = $this->users_model->unallocate_bucket_scan($job_number, $bucket);

        // if ($result == 1) {
        //     $message = array('status' => 1, 'msg' => 'Bucket Unallocated!');
        //     $this->response($message, 200);

        // } else {
        //     $message = array('status' => 0, 'msg' => 'Failed..');
        //     $this->response($message, 300);
        // }
        if (!$result) {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        } else {
            $message = array('status' => 1, 'msg' => 'Bucket Unallocated!', 'data' => $result);
            $this->response($message, 200);
        }
    }


    public function bucket_details_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $bucket = trim($details->bucket);

        $result = $this->users_model->bucket_data($job_number, $bucket);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Success', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		   
		   
	//Sync picking_details_collector
    public function sync_picking_details_collector_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $collector_id = trim($details->collector_id);

        $result = $this->users_model->sync_collector_picking($job_number, $collector_id);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Sync data!', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }

	 //Sync picking_details_worker
    public function sync_picking_details_worker_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);

        $result = $this->users_model->sync_worker_picking($job_number);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Sync data!', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
     
	 
	 //Sync bucket_scan
    public function sync_bucket_scan_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);

        $result = $this->users_model->sync_bucket_picking($job_number);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Sync data!', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		
		
    //Sync row_details_collector
    public function sync_row_details_collector_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);
        $collector_id = trim($details->collector_id);

        $result = $this->users_model->sync_collector_row($job_number, $collector_id);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Sync data!', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }


	//Sync row_details_worker
    public function sync_row_details_worker_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);

        $result = $this->users_model->sync_worker_row($job_number);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Sync data!', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		
	
	//Sync row_scan
    public function sync_row_scan_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $job_number = trim($details->job_number);

        $result = $this->users_model->sync_row_picking($job_number);

        if ($result) {
            $message = array('status' => 1, 'msg' => 'Sync data!', 'data' => $result);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
			
		
	//Sync offline data of picking_details_collector
    public function sync_offline_picking_details_collector_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $picking_collectors = $details->data;

        $response = $this->users_model->sync_offline_collector_picking($picking_collectors);

        if ($response) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'data' => $response);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		
	  //check net
    public function check_net_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $response = $details;

        if ($response) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'data' => $response);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		
		
     //Sync offline data of picking_details_worker
    public function sync_offline_picking_details_worker_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $picking_workers = $details->data;
        $response = $this->users_model->sync_offline_worker_picking($picking_workers);

        if ($response) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'data' => $response);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'There is no data to sync');
            $this->response($message, 300);
        }
    }
		
		
	//Sync offline data of bucket scan
    public function sync_offline_bucket_scan_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $bucket_scans = $details->data;

        $res = $this->users_model->sync_offline_bucket_scan($bucket_scans);

        if ($res) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'last_sync' => $res['last_sync'], 'data' => $res['result']);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		
	
	//Sync offline data of row_details_collector
    public function sync_offline_row_details_collector_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $row_collectors = $details->data;

        $response = $this->users_model->sync_offline_collector_row($row_collectors);

        if ($response) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'data' => $response);
            $this->response($message, 200);
        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }
		
		
	 //Sync offline data of row_details_worker
    public function sync_offline_row_details_worker_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);
        $row_workers = $details->data;
        $response = $this->users_model->sync_offline_worker_row($row_workers);

        if ($response) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'data' => $response);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }

    
	 //Sync offline data of row scan
    public function sync_offline_row_scan_post()
    {
        $data = file_get_contents('php://input');
        $details = json_decode($data);

        $row_scans = $details->data;

        $res = $this->users_model->sync_offline_row_scan($row_scans);

        if ($res) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'last_sync' => $res['last_sync'], 'data' => $res['result']);
            $this->response($message, 200);

        }

        if ($response) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', 'data' => $response);
            $this->response($message, 200);

        } else {
            $message = array('status' => 0, 'msg' => 'Failed..');
            $this->response($message, 300);
        }
    }

// ---------- Hit these api when Sync Offline is started [Start]---------------------//

    public function afterSyncPicking_get()
    {
        $res = $this->users_model->afterSyncPicking();
        if ($res == 1) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', );
            $this->response($message, 200);
        } else {
            $message = array('status' => 2, 'msg' => 'Data sync failed!', );
            $this->response($message, 300);
        }
    }

    public function afterSyncRow_get()
    {
        $res = $this->users_model->afterSyncRow();
        if ($res == 1) {
            $message = array('status' => 1, 'msg' => 'Data sync completed!', );
            $this->response($message, 200);
        } else {
            $message = array('status' => 2, 'msg' => 'Data sync failed!', );
            $this->response($message, 300);
        }
    }
// ---------- Hit these api when Sync Offline is started [End]---------------------//
}
?>