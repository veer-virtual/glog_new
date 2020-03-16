<?php
error_reporting(0);
define('API_ACCESS_KEY', 'AIzaSyD_GrGFF1mOlmVSFVRYIu0pb0zFhoCE46Q');

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Users_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        //$this->load->library('database');
    }

    /* ------------------------------------------------------------
    | All Only collector Related Functionality will come this side.
    |--------------------------------------------------------------
    | 
     */

    //
    //------------ All Methods come here --------------------------------
    //

    //
    //------------ get jobs data for collector --------------------------------
    function getCollectorJobQuery($collector_tag){
    	$this->db->select('jobs.id, jobs.job_number, jobs.j_type, jobs.job_type, jobs.worker_type, client.company_name AS client_name, vineyard.vineyard_name,block.block_name, jobs.variety, jobs.rows, jobs.name_collector AS collector, jobs.worker_ids_allocated AS worker,supplier.company_name AS supplier_name, jobs.price_per_unit, jobs.lock_out_time, jobs.job_status, jobs.multiple_scan, jobs.worker_lock_out_time, jobs.block');
        $this->db->from('jobs');
        $this->db->join('client', 'jobs.client_name = client.id');
        $this->db->join('block', 'jobs.block = block.id', 'left');
        $this->db->join('supplier', 'jobs.supplier_name = supplier.id');
        $this->db->join('vineyard', 'vineyard.id = jobs.vineyard_name');
        $this->db->where("FIND_IN_SET('$collector_tag',jobs.name_collector)!=", 0);
        $this->db->where("jobs.job_status !=", 'created');
        $this->db->where("jobs.job_status !=", 'closed');
        $this->db->where("jobs.job_status !=", 'completed');
        $this->db->where("jobs.job_status !=", 'archived');
        $this->db->where("jobs.job_status !=", 'deleted');

        $query = $this->db->get();
        // $result = $query->result();

        // echo $this->db->last_query();

        return $query->result();
    }
    function get_collector_job($collector_tag)
    {
        
        $result2 = $this->getCollectorJobQuery($collector_tag);

        if (count($result2) > 0) {

            // change status create on 19-02-2019
            foreach ($result2 as $value) {
            	if($value->job_status != 'paused'){
            		$this->_changeJobStatus($value);
            	}
            }  

            $result = $this->getCollectorJobQuery($collector_tag);
            foreach ($result as $val) {
                  //Collectors name	

                $this->db->select('nfc_tag_id,collector_name');
                $this->db->from('collector');
                $this->db->where("FIND_IN_SET(nfc_tag_id,'$val->collector')!=", 0);
                $collectors = $this->db->get();
                $collectorNames = array();
                $collectorIds = array();
                foreach ($collectors->result() as $coll) {
                    array_push($collectorNames, $coll->collector_name);
                    array_push($collectorIds, $coll->nfc_tag_id);
                }
                $val->collector_allocated = count($collectorNames);
                $val->collector = implode(',', $collectorNames); //Collector names
                $val->collector_ids = implode(',', $collectorIds); //Collector Ids
				 			  
				  //Workers name
                $this->db->select('nfc_tag_id,worker_name');
                $this->db->from('worker');
                $this->db->where("FIND_IN_SET(nfc_tag_id,'$val->worker')!=", 0);
                $workers = $this->db->get();
                $workerNames = array();
                $workerIds = array();
                foreach ($workers->result() as $work) {
                    array_push($workerNames, $work->worker_name);
                    array_push($workerIds, $work->nfc_tag_id);
                }
                $val->worker_allocated = count($workerNames);
                $val->worker = implode(',', $workerNames); //worker names				  
                $val->worker_ids = implode(',', $workerIds); //worker ids
				  			  
				  //Row IDs
                $this->db->select('row_no');
                $this->db->from('block_row_data');
                $this->db->where("FIND_IN_SET(id,'$val->rows')!=", 0);
                $rows = $this->db->get();
                $rowList = array();
                foreach ($rows->result() as $row) {
                    array_push($rowList, $row->row_no);
                }
                $val->rows = implode(',', $rowList);

                //blocks
                $this->db->select('block_name');
                $this->db->from('block');
                $this->db->where("FIND_IN_SET(id,'$val->block')!=", 0);
                $blocks = $this->db->get();
                $blockList = array();
                foreach ($blocks->result() as $block) {
                    array_push($blockList, $block->block_name);
                }
                $val->block_name = implode(',', $blockList);

            }
            return ($result);
        } else {
            return false;
        }
    }

	//
    //------------ Get the collector data --------------------------------
    //
    function get_collector($collector_tag)
    {
        $this->db->select('*');
        $this->db->from('collector');
        $this->db->where('nfc_tag_id', $collector_tag);
        $query = $this->db->get();

        if ($query->result()) {

            return $query->result();
        } else {
            return false;
        }
    }

    //
    //------------ All Collectors --------------------------------
    //
    function all_collector()
    {
        $this->db->select('*');
        $this->db->from('collector');
        $query = $this->db->get();

        if (count($query->result()) > 0) {

            return $query->result();
        } else {
            return false;
        }
    }

    //
    //------------ Check Collector --------------------------------
    //
    function check_collector_1($nfc_tag)
    {
        $this->db->select('*');
        $this->db->from('collector');
        $this->db->where('nfc_tag_id', $nfc_tag);
        $query = $this->db->get();

        if (count($query->result()) > 0) {

            $c_type = $query->row()->c_type;

            if ($c_type == 1) {
                return 1;

            } elseif ($c_type == 2) {

                return 2;
            }
        } else {
            return false;
        }
    }
    
      /* ------------------------------------------------------------|
     |||||||||||||||][ Collector DATA END ][|||||||||||||||||||||||||
    |-------------------------------------------------------------*/


    /* ------------------------------------------------------------
    | All Only Worker Related Functionality will come this side.
    |--------------------------------------------------------------
    | 
     */

    //
    //------------ All Methods come here --------------------------------
    //

    //
    //------------ Get the Worker Data --------------------------------
    //
    function get_worker($worker_tag)
    {
        $this->db->select('*');
        $this->db->from('worker');
        $this->db->where('nfc_tag_id', $worker_tag);
        $query = $this->db->get();

        if ($query->result()) {

            return $query->result();
        } else {
            return false;
        }
    }


      /* ------------------------------------------------------------|
     ||||||||||||||||][ Worker DATA END ][|||||||||||||||||||||||||||
    |-------------------------------------------------------------*/
    
    /* ----------------------------------------------------------
    | All Row Related Functionality will come this side.
    |------------------------------------------------------------
    | 
     */

    //
    //------------ All methods --------------------------------
    //

    //
    //------------ break time Row job --------------------------------
    //
    function break_time_job_row($job_number, $type, $time)
    {
        if ($type == 'pause') {
            $pause = $time;
			//Pause Collectors in Job
            $this->db->select('*');
            $this->db->from('row_details_collector');
            $this->db->where('job_number', $job_number);
            $this->db->where('status', 'started');
            $res = $this->db->get();

            if (count($res->result()) > 0) {
                $this->db->where('job_number', $job_number);
                $this->db->where('status', 'started');
                $this->db->update('row_details_collector', array('pause_time' => $pause, 'status' => 'paused'));
						
						//Pause workers in Job
                $this->db->select('*');
                $this->db->from('row_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('status', 'started');
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    $this->db->where('job_number', $job_number);
                    $this->db->where('status', 'started');
                    $this->db->update('row_details_worker', array('pause_time' => $pause, 'status' => 'paused'));
                }


                // change job status on 15-01-2019
                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'paused'));

                return 1; //Job paused

            } else {
                return 2; //Job is not started
            }

        } elseif ($type == 'reset') {

            $reset = $time;

				//Reset Collectors in Job
            $this->db->select('*');
            $this->db->from('row_details_collector');
            $this->db->where('job_number', $job_number);
            $this->db->where('status', 'paused');
            $res = $this->db->get();

            if (count($res->result()) > 0) {
                foreach ($res->result() as $val) {
                    $to_time = strtotime($reset);
                    $from_time = strtotime($val->pause_time);									
						  //echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                    $non_worked = round(abs($to_time - $from_time) / 3600, 2);	//Non-working Time in hours		
                    $non_worked = $val->time_not_worked + $non_worked;

                    $this->db->where('id', $val->id);
                    $this->db->update('row_details_collector', array('reset_time' => $reset, 'status' => 'started', 'time_not_worked' => $non_worked));
                }
						
						//Reset workers in Job
                $this->db->select('*');
                $this->db->from('row_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('status', 'paused');
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    foreach ($query->result() as $val_1) {
                        $to_time = strtotime($reset);
                        $from_time = strtotime($val_1->pause_time);									
								  //echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                        $non_worked = round(abs($to_time - $from_time) / 3600, 2);	//Non-working Time in hours
                        $non_worked = $val_1->time_not_worked + $non_worked;

                        $this->db->where('id', $val_1->id);
                        $this->db->update('row_details_worker', array('reset_time' => $reset, 'status' => 'started', 'time_not_worked' => $non_worked));
                    }
                }

                // change job status on 15-01-2019
                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'active'));
                
                return 4; //Job reset

            } else {
                return 3; //Job is not paused
            }

        } else {
            return false;
        }
    }

    //
    //------------ worker time for row job --------------------------------
    //
    function worker_time_row($job_number, $worker, $type, $time)
    {

        $this->db->select('*');
        $this->db->from('row_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('status', 'paused');
        $collect = $this->db->get();
        if (count($collect->result()) > 0) {
            return 6; //Job is paused
        }
        $this->db->select('worker_ids_allocated');
        $this->db->from('jobs');
        $this->db->where('job_number', $job_number);
        $query = $this->db->get();

        foreach ($query->result() as $val) {
            $workers = explode(',', $val->worker_ids_allocated);
        }

        if (in_array($worker, $workers)) {
            //if worker is valid		
            if ($type == 'start') {

                $start = $time;

                $this->db->select('*');
                $this->db->from('row_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('worker_id', $worker);
                $this->db->where("( status = 'started' OR status = 'paused')");
                $res = $this->db->get();

                if (count($res->result()) <= 0) {
                    //check if job is finished	
                    $this->db->select('*');
                    $this->db->from('row_details_worker');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $worker);
                    $this->db->where('status', 'finished');
                    $result = $this->db->get();


                    if (count($result->result()) > 0) {
                        // CHANGE ON 26-11-2018
                        /*$this->db->where('job_number', $job_number);
                        $this->db->where('worker_id', $worker);
                        if ($this->db->update(
                            'row_details_worker',
                            array(
                                'start_time' => $start,
                                'status' => 'started'
                            )
                        )) {
                            return 1; //Worker Job started
                        }*/
                        $data = array(
                            'job_number' => $job_number,
                            'worker_id' => $worker,
                            'start_time' => $start,
                            'w_type' => (int)$this->isjobHourly($job_number),
                            'hourly_fee' => (float)$this->isWorkerHourly($worker),
                            'status' => 'started'
                        );

                        if ($this->db->insert('row_details_worker', $data)) {
                            return 1; //Worker Job started
                        } else {
                            return false;
                        }

                    } else {

                        $data = array(
                            'job_number' => $job_number,
                            'worker_id' => $worker,
                            'start_time' => $start,
                            'w_type' => (int)$this->isjobHourly($job_number),
                            'hourly_fee' => (float)$this->isWorkerHourly($worker),
                            'status' => 'started'
                        );

                        if ($this->db->insert('row_details_worker', $data)) {
                            return 1; //Worker Job started
                        } else {
                            return false;
                        }
                    }

                } else {
                    return 4; //Job already started
                }

            } elseif ($type == 'finish') {

                $finish = $time;

                $this->db->select('*');
                $this->db->from('row_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('worker_id', $worker);
                $this->db->order_by('id', 'DESC');
                // $this->db->where('status', 'finished');
                $res = $this->db->get();
                // echo count($res->result());
                // echo $res->row()->status;
                // print_r($res->result());
                if(count($res->result()) > 0){
                    if ($res->row()->status == "started") {
                        $this->db->select('*');
                        $this->db->from('row_details_worker');
                        $this->db->where('job_number', $job_number);
                        $this->db->where('worker_id', $worker);
                        $this->db->where('status', 'started');
                        $data = $this->db->get();
    
                        $id = $data->row()->id;
                        $non_worked = $data->row()->time_not_worked; //Non-worked time
    
                        $to_time = strtotime($finish);
                        $from_time = strtotime($data->row()->start_time);									
                            //echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                        $worked = round(abs($to_time - $from_time) / 3600, 2);	//Working Time in hours		
                        $worked = $data->row()->time_worked + $worked;
                        $time_worked = $worked - $non_worked;
    
                        $this->db->where('id', $id);
                        if ($this->db->update('row_details_worker', array('time_worked' => $time_worked, 'finish_time' => $finish, 'status' => 'finished'))) {
                            return 5; //Worker Job finished
                        }
                    } elseif($res->row()->status == "finished") {
                        return 3; //Job already finished 
                    }elseif($res->row()->status == "paused") {
                        return 6; //Job already finished 
                    }else{
                        return 7;
                    }
                }else{
                    return 7;
                }
               
            } else {
                return false;
            }

        } else {
            return 2; //Invalid Worker
        }
    }
    //
    //------------ Row Scan Time [Start] --------------------------------
    //
    function row_time($job_number, $worker, $collector, $row, $type, $time, $deviceId = "")
    {
        //lockTime
        // echo $lock_out_time = $this->lockTime($job_number);

        $job = $this->db->select('rows')
            ->from('jobs')
            ->where('job_number', $job_number)
            ->get();
        //All rows of job
        $r = $job->row()->rows;
        $rows = explode(',', $r);
        $job_rows = array();  //Rows of job

        $res = $this->db->select('*')
            ->from('block_row_data')
            ->where_in('id', $rows)
            ->get();

        if (count($res->result()) > 0) {
            foreach ($res->result() as $val) {
                array_push($job_rows, $val->nfc_tag_id);
            }
        }

        $picks = $this->db->select('*')
            ->from('row_details_worker')
            ->where('job_number', $job_number)
            ->where('worker_id', $worker)
            ->order_by('id', 'DESC')
            // ->where('status', 'started')
            ->get();
            
        if ($picks->row()->status = "started" || $picks->row()->status = "finished") {
            return $this->row_time_scan($job_number, $worker, $collector, $row, $type, $time, $deviceId, true);
        } elseif ($picks->row()->status = "paused" ) {
            return 15;
        }else{
            return 2;
        }
    }

    function row_time_scan($job_number, $worker, $collector, $row, $type, $time, $deviceId = "")
    {
        //lockTime
        $lock_out_time = $this->lockTime($job_number);

        $job = $this->db->select('rows')
            ->from('jobs')
            ->where('job_number', $job_number)
            ->get();
            //All rows of job
        $r = $job->row()->rows;
        $rows = explode(',', $r);
        $job_rows = array();  //Rows of job

        $res = $this->db->select('*')
            ->from('block_row_data')
            ->where_in('id', $rows)
            ->get();

        if (count($res->result()) > 0) {
            foreach ($res->result() as $val) {
                array_push($job_rows, $val->nfc_tag_id);
            }
        }

        $picks = $this->db->select('*')
            ->from('row_details_worker')
            ->where('job_number', $job_number)
            ->where('worker_id', $worker)
            ->order_by('id', 'DESC')
            // ->where('status', 'started')
            ->get();

        if ($picks->row()->status == "started" || $picks->row()->status == "finished") {
            $now_scan = $time;
            $units_picked = $picks->row()->units_picked; //Total rows picked by Worker for this Job
            $dup = $picks->row()->duplicates; //Total Duplicates by Worker for this Job
            $overrides = $picks->row()->overrides; //Total Overrides by Worker for this Job

            if ($job_number != '' && $worker != '' && $collector == '' && $row == '' && $type == '') {
                // if worker already finished
                if($picks->row()->status == "finished"){
                    return 2;
                }

                $this->db->select('*');
                $this->db->from('row_scan');
                $this->db->where('job_number', $job_number);
                $this->db->where('worker_id', $worker);
                // $this->db->where('last_scan', '0000-00-00 00:00:00');
                $res = $this->db->get();
                // return $res->row();
                if (count($res->result()) <= 0) {
                    $data = array(
                        'job_number' => $job_number,
                        'worker_id' => $worker
                    );

                    if ($this->db->insert('row_scan', $data)) {
                        // $this->db->where('job_number', $job_number);
                        // $this->db->where('worker_id', $worker);
                        $this->db->where('id', $picks->row()->id);
                        if ($this->db->update('row_details_worker', array('last_scan' => $now_scan))) {
                            return 1; //Worker Scanned
                        } else {
                            return false;
                        }
                    } else {
                        return false;
                    }
                } else {								 
							  //Already scanned worker with no row started
                    // $this->db->where('job_number', $job_number);
                    // $this->db->where('worker_id', $worker);
                    $this->db->where('id', $picks->row()->id);
                    if ($this->db->update('row_details_worker', array('last_scan' => $now_scan))) {
                        return 1; //Worker Scanned
                    } else {
                        return false;
                    }
                }

            } else if ($job_number != '' && $worker != '' && $collector == '' && $row != '' && $type != '') {

                if (in_array($row, $job_rows)) {
					//If row is scanned to start
                    if ($type == 'start') {
						//Check if row is scanned again												
                        $this->db->select('*');
                        $this->db->from('row_scan');
                        $this->db->where('job_number', $job_number);
                        $this->db->where('row_tag_id', $row);
                        $this->db->where('worker_id', $worker);// change on 09-05-2019 by v
                        $this->db->order_by('last_scan', 'desc');
                        $this->db->limit(1);
                        $buck = $this->db->get();
                        
										 //Duplicate row scanned by worker
                        if (count($buck->result()) > 0) {
							//check status of row
                            $row_status = $buck->row()->status;

                            if ($row_status == 'started') {			
								//check if row is started by same worker
                                $this->db->select('*');
                                $this->db->from('row_scan');
                                $this->db->where('job_number', $job_number);
                                $this->db->where('worker_id', $worker);
                                $this->db->where('row_tag_id', $row);
                                $this->db->where('status', 'started');
                                $this->db->order_by('last_scan', 'desc');
                                $this->db->limit(1);
                                $res = $this->db->get();

                                if (count($res->result()) <= 0) {
									//Row scanned by another worker to start												
                                    // $this->db->where('job_number', $job_number);
                                    // $this->db->where('worker_id', $worker);
                                    $this->db->where('id', $picks->row()->id);
                                    if ($this->db->update('row_details_worker', array('duplicates' => $dup, 'last_scan' => $now_scan))) {

                                        $update = $this->db->update(
                                            'sync_row_details_worker',
                                            [
                                                'duplicates' => $dup,
                                                'prev_duplicate' => $dup
                                            ],
                                            [
                                                'job_number' => $job_number,
                                                'worker_id' => $worker,
                                                'device_id' => $deviceId
                                            ]
                                        );
                                        return 6; //Row already started by another worker
                                    } else {
                                        return false;
                                    }
                                } else {
                                    return 4; //Row already started by same worker
                                }

                            } elseif ($row_status == 'finished') {
							     // Finished Row is scanned, Override by collector	
                                $dup = $dup + 1;

                                // $this->db->where('job_number', $job_number);
                                // $this->db->where('worker_id', $worker);
                                $this->db->where('id', $picks->row()->id);
                                if ($this->db->update('row_details_worker', array('duplicates' => $dup, 'last_scan' => $now_scan))) {
                                    $update = $this->db->update(
                                        'sync_row_details_worker',
                                        [
                                            'duplicates' => $dup,
                                            'prev_duplicate' => $dup
                                        ],
                                        [
                                            'job_number' => $job_number,
                                            'worker_id' => $worker,
                                            'device_id' => $deviceId
                                        ]
                                    );
                                    return 14; //Row already finished 
                                } else {
                                    return false;
                                }
                            } elseif ($row_status == 'unfinished') {												 
								// Unfinished Row is scanned, same worker will re-start the work on same row
                                $this->db->select('*');
                                $this->db->from('row_scan');
                                $this->db->where('job_number', $job_number);
                                $this->db->where('worker_id', $worker);
                                $this->db->where('row_tag_id', $row);
                                $this->db->where('status', 'unfinished');
                                $this->db->order_by('last_scan', 'desc');
                                $this->db->limit(1);
                                $res = $this->db->get();

                                if (count($res->result()) > 0) {
                                    $row_id = $res->row()->id;

                                    $this->db->where('id', $row_id);
                                    if ($this->db->update('row_scan', array('start_time' => $now_scan, 'last_scan' => $now_scan, 'status' => 'started'))) {
                                        return 5; //Row Started
                                    } else {
                                        return false;
                                    }
                                } else {
                                    return 13; //Same worker can only start an unfinished row
                                }

                            } elseif ($row_status == 'unallocated') {												 
										       // Unallocated Row is scanned, any woker can start work on it like a new row
                                //modify on 09-05-2019
                                // $this->db->where('job_number', $job_number);
                                // $this->db->where('worker_id', $worker);
                                // $this->db->where('last_scan', '0000-00-00 00:00:00');
                                $this->db->where('id', $buck->row()->id);
                                if ($this->db->update('row_scan', array('row_tag_id' => $row, 'start_time' => $now_scan, 'last_scan' => $now_scan, 'status' => 'started'))) {
                                    return 5; //Row Started
                                } else {
                                    return false;
                                }
                            }

                        } elseif (count($buck->result()) <= 0) { //New row scanned 			
                            // chage on 10-05-2019 by v
                            /*$this->db->where('job_number', $job_number);
                            $this->db->where('worker_id', $worker);
                            $this->db->where('last_scan', '0000-00-00 00:00:00');
                            if ($this->db->update('row_scan', array('row_tag_id' => $row, 'start_time' => $now_scan, 'last_scan' => $now_scan, 'status' => 'started'))) {
                                return 5; //Row Started
                            } else {
                                return false;
                            }*/
                            $data = array(
                                'job_number' => $job_number,
                                'worker_id' => $worker,
                                'row_tag_id' => $row, 
                                'start_time' => $now_scan, 
                                'last_scan' => $now_scan, 
                                'status' => 'started'
                            );
                            if($this->db->insert('row_scan', $data)){
                                return 5; //Row Started
                            } else {
                                return false;
                            }
                        }
                    }//If row is scanned to finish
                    elseif ($type == 'finish') {				
						//check if job for row is started	
                        $this->db->select('*');
                        $this->db->from('row_scan');
                        $this->db->where('job_number', $job_number);
                        $this->db->where('worker_id', $worker);
                        $this->db->where('row_tag_id', $row);
                        $this->db->where('status', 'started');
                        $this->db->order_by('last_scan', 'desc');
                        $this->db->limit(1);
                        $res = $this->db->get();

                        if (count($res->result()) > 0) {
                            //Finish the job on started Row	

                            $row_id = $res->row()->id;

                            $to_time = strtotime($now_scan);
                            $from_time = strtotime($res->row()->start_time);
                            $job_time = round(abs($to_time - $from_time) / 3600, 2); //Job Time in hours
                            $time_worked = $res->row()->time_worked + $job_time;

                            $this->db->where('id', $row_id);
                            if ($this->db->update('row_scan', array('finish_time' => $now_scan, 'time_worked' => $time_worked, 'last_scan' => $now_scan, 'status' => 'finished'))) {//update units picked by worker

                                $this->db->select('*');
                                $this->db->from('block_row_data');
                                $this->db->where('nfc_tag_id', $row);
                                $row_res = $this->db->get();

                                $vines = $row_res->row()->no_vines; //total vines in row finished
                                $units_picked = $units_picked + $vines; //add vines picked from finished row
                                if ($units_picked < 0) {
                                    $units_picked = 0;
                                }
                                // $this->db->where('job_number', $job_number);
                                // $this->db->where('worker_id', $worker);
                                $this->db->where('id', $picks->row()->id);
                                if ($this->db->update('row_details_worker', array('units_picked' => $units_picked))) {
                                    $update = $this->db->update(
                                        'sync_row_details_worker',
                                        [
                                            'units_picked' => $units_picked,
                                            'prev_units' => $units_picked
                                        ],
                                        [
                                            'job_number' => $job_number,
                                            'worker_id' => $worker,
                                            'device_id' => $deviceId
                                        ]
                                    );
                                    return 8; //Row Finished
                                } else {
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        } else {
                            return 7; //Row is not started
                        }

                    }//If row is scanned to unallocate
                    elseif ($type == 'unallocate') {

						//check if job for row is finished	
                        $this->db->select('*');
                        $this->db->from('row_scan');
                        $this->db->where('job_number', $job_number);
                        $this->db->where('worker_id', $worker);
                        $this->db->where('row_tag_id', $row);
                        $this->db->where('status', 'finished');
                        $this->db->order_by('last_scan', 'desc');
                        $this->db->limit(1);
                        $res = $this->db->get();

                        if (count($res->result()) <= 0) { //check if job for row is started	
                            $this->db->select('*');
                            $this->db->from('row_scan');
                            $this->db->where('job_number', $job_number);
                            $this->db->where('worker_id', $worker);
                            $this->db->where('row_tag_id', $row);
                            $this->db->where('status', 'started');
                            $this->db->order_by('last_scan', 'desc');
                            $this->db->limit(1);
                            $result = $this->db->get();

                            if (count($result->result()) > 0) {//Unallocate started row for Worker								
                                $row_id = $result->row()->id;

                                $this->db->where('id', $row_id);
                                if ($this->db->update('row_scan', array('last_scan' => $now_scan, 'status' => 'unallocated'))) {
                                    return 10; //Row Unallocated
                                } else {
                                    return false;
                                }
                            } else {
                                return 7; //Row is not started
                            }
                        } else {
                            return 9; //Row already finished, cannot be unallocated
                        }
                    }

                } else {
                    return 3; //Invalid Row
                }

            } elseif ($job_number != '' && $worker != '' && $collector != '' && $row != '' && $type != '') {
             
                //If row is scanned to unallocate
                if ($type == 'unallocate') {
                    $this->db->select('*');
                    $this->db->from('row_scan');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('row_tag_id', $row);
                    $this->db->order_by('last_scan', 'desc');
                    $this->db->limit(1);
                    $result = $this->db->get();

                    if (count($result->result()) > 0) {
                        //Unallocate Row	
                        $row_id = $result->row()->id;
                        $row_status = $result->row()->status;

                        if ($row_status == 'started') {
                            $this->db->where('id', $row_id);
                            if ($this->db->update('row_scan', array('last_scan' => $now_scan, 'status' => 'unallocated'))) {
															 //Override worker
                                $overrides = $overrides + 1;

                                
                                //update units picked by worker whose row is unfinished
                                $this->db->select('no_vines');
                                $this->db->from('block_row_data');
                                $this->db->where('nfc_tag_id', $row);
                                $row_res = $this->db->get();
                                $vines = $row_res->row()->no_vines; //total vines in row finished
                                $units_picked = $units_picked - $vines; //remove vines picked from finished row

                                // $this->db->where('job_number', $job_number);
                                // $this->db->where('worker_id', $worker);
                                $this->db->where('id', $picks->row()->id);
                                if ($this->db->update('row_details_worker', array('overrides' => $overrides))) {
                                    $update = $this->db->update(
                                        'sync_row_details_worker',
                                        [
                                            'overrides' => $overrides,
                                            'prev_override' => $overrides,
                                            'units_picked' => $units_picked,
                                            'prev_units' => $units_picked
                                        ],
                                        [
                                            'job_number' => $job_number,
                                            'worker_id' => $worker,
                                            'device_id' => $deviceId

                                        ]
                                    );
                                    return 10; //Row Unallocated
                                } else {
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        } elseif ($row_status == 'finished') {

                            $this->db->where('id', $row_id);
                            if ($this->db->update('row_scan', array('last_scan' => $now_scan, 'status' => 'unallocated'))) {
								//Override worker
                                $overrides = $overrides + 1;
								//update units picked by worker whose row is unallocated
                                $this->db->select('no_vines');
                                $this->db->from('block_row_data');
                                $this->db->where('nfc_tag_id', $row);
                                $row_res = $this->db->get();

                                $vines = $row_res->row()->no_vines; //total vines in row finished
                                $units_picked = $units_picked - $vines; //remove vines picked from finished row

                                // $this->db->where('job_number', $job_number);
                                // $this->db->where('worker_id', $worker);
                                $this->db->where('id', $picks->row()->id);
                                if ($this->db->update('row_details_worker', array('overrides' => $overrides, 'units_picked' => $units_picked))) {
                                    $update = $this->db->update(
                                        'sync_row_details_worker',
                                        [
                                            'overrides' => $overrides,
                                            'units_picked' => $units_picked,
                                            'prev_override' => $overrides,
                                            'prev_units' => $units_picked
                                        ],
                                        [
                                            'job_number' => $job_number,
                                            'worker_id' => $worker,
                                            'device_id' => $deviceId
                                        ]
                                    );
                                    return 10; //Row Unallocated											   
                                } else {
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        }
                    } else {
                        return 7; //Row is not started
                    }
                }//If row is scanned to unfinish
                elseif ($type == 'unfinish') {
                   
        			//check if job for row is started	
                    $this->db->select('*');
                    $this->db->from('row_scan');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('row_tag_id', $row);
                    $this->db->where('status', 'finished');
                    $this->db->order_by('last_scan', 'desc');
                    $this->db->limit(1);
                    $res = $this->db->get();

                    if (count($res->result()) > 0) {//Unfinish the job on started Row
                        $row_id = $res->row()->id;
                        $worker_id = $res->row()->worker_id; //id of worker who finished row
                        $this->db->where('id', $row_id);
                        if ($this->db->update('row_scan', array('last_scan' => $now_scan, 'status' => 'unfinished'))) {//Override worker
                            $overrides = $overrides + 1;

                            // $this->db->where('job_number', $job_number);
                            // $this->db->where('worker_id', $worker);
                            $this->db->where('id', $picks->row()->id);
                            if ($this->db->update('row_details_worker', array('overrides' => $overrides))) {
                                $update = $this->db->update(
                                    'sync_row_details_worker',
                                    [
                                        'overrides' => $overrides,
                                        'prev_override' => $overrides
                                    ],
                                    [
                                        'job_number' => $job_number,
                                        'worker_id' => $worker,
                                        'device_id' => $deviceId
                                    ]
                                );												  
								//update units picked by worker whose row is unfinished
                                $this->db->select('no_vines');
                                $this->db->from('block_row_data');
                                $this->db->where('nfc_tag_id', $row);
                                $row_res = $this->db->get();
                                $vines = $row_res->row()->no_vines; //total vines in row finished
                                $units_picked = $units_picked - $vines; //remove vines picked from finished row

                                // $this->db->where('job_number', $job_number);
                                // $this->db->where('worker_id', $worker_id);
                                $this->db->where('id', $picks->row()->id);
                                if ($this->db->update('row_details_worker', array('units_picked' => $units_picked))) {
                                    $update = $this->db->update(
                                        'sync_row_details_worker',
                                        [
                                            'units_picked' => $units_picked,
                                            'prev_units' => $units_picked
                                        ],
                                        [
                                            'job_number' => $job_number,
                                            'worker_id' => $worker,
                                            'device_id' => $deviceId
                                        ]
                                    );
                                    return 11; //Row Unfinished
                                } else {
                                    return false;
                                }
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    } else {
                        return 12; //Row is not finished
                    }

                } else {
                    return false;
                }
            }
        } else if ($picks->row()->status == "paused") { 
           return 15;
        }else{
            return 2;
        }
    }
    //
    //------------ Row Scan Time [END] --------------------------------
    //

    //
    //------------ Get row data for Unfinished or Un-allocated Row scanned -------
    //
    function row_data($job_number, $row)
    {
        $this->db->select('block_row_data.row_no, block_row_data.no_vines');
        $this->db->from('block_row_data');
        $this->db->where('nfc_tag_id', $row);
        $row_data = $this->db->get();

        if (count($row_data->result()) > 0) {

            foreach ($row_data->result() as $val) {

                $this->db->select('row_scan.status AS row_status, row_scan.worker_id, worker.worker_name AS worker');
                $this->db->from('row_scan');
                $this->db->join('worker', 'worker.nfc_tag_id = row_scan.worker_id');
                $this->db->where('job_number', $job_number);
                $this->db->where('row_tag_id', $row);
                $this->db->order_by('last_scan', 'desc');
                $this->db->limit(1);
                $data = $this->db->get();

                if (count($data->result()) > 0) {

                    $val->row_status = $data->row()->row_status;
                    $val->worker_id = $data->row()->worker_id;
                    $val->worker = $data->row()->worker;

                } else {

                    $val->row_status = '';
                    $val->worker_id = '';
                    $val->worker = '';
                }
            }

            return $row_data->result();

        } else {
            return false;
        }
    }

    //
    //------------ get row collector synced --------------------------------
    //
    function row_collector_synced($job_number, $collector_id, $device_id)
    {
        $this->db->select('*');
        $this->db->from('row_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('collector_id', $collector_id);
        $this->db->where('device_id', $device_id);
        $res = $this->db->get();
        return $res;
    }

    //
    //------------ Sync offline data of row_details_collector-------------
    //
    function sync_offline_collector_row($row_collectors)
    {
        $response = array();

        foreach ($row_collectors as $val) {
            $res = $this->row_collector_synced($val->job_number, $val->collector_id, $val->device_id);
            if (count($res->result()) > 0) {//update record for existing collector on job	
                $id = $res->row()->id;
                $data = array(
                    'start_time' => $val->start_time,
                    'time_worked' => $val->time_worked,
                    'finish_time' => $val->finish_time,
                    'pause_time' => $val->pause_time,
                    'reset_time' => $val->reset_time,
                    'time_not_worked' => $val->time_not_worked,
                    'status' => $val->status,
                    'device_id' => $val->device_id
                );
                $this->db->where('id', $id);
                $sync_collector = $this->db->update('row_details_collector', $data);

                $synced_data = $this->row_collector_synced($val->job_number, $val->collector_id, $val->device_id);

                if ($sync_collector == 1) {
                    array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                } else {
                    array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                }

            } else {//insert new record for new collector on job					
                $data = array(
                    'job_number' => $val->job_number,
                    'collector_id' => $val->collector_id,
                    'start_time' => $val->start_time,
                    'time_worked' => $val->time_worked,
                    'finish_time' => $val->finish_time,
                    'pause_time' => $val->pause_time,
                    'reset_time' => $val->reset_time,
                    'time_not_worked' => $val->time_not_worked,
                    'status' => $val->status,
                    'device_id' => $val->device_id
                );
                $this->db->insert('row_details_collector', $data);
                $insert_id = $this->db->insert_id();

                $synced_data = $this->row_collector_synced($val->job_number, $val->collector_id, $val->device_id);

                if ($insert_id != 0) {
                    array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                } else {
                    array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                }
            }
        }
        return $response;
    }


    //
    //------------ get row worker synced-------------------------
    //
    function row_worker_synced($job_number, $worker_id)
    {
        $this->db->select('*');
        $this->db->from('row_details_worker');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker_id);
        $query = $this->db->get()->row();
        return $query;
    }
    
    //
    //------------ Sync offline data of row_details_worker ------------
    //
    function sync_offline_worker_row($row_workers)
    {
        $response = array();
        $x = 0;
        $allData = [];
        foreach ($row_workers as $val) {
            $key = $this->db->select('key')->from('row_details_worker')->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id])->get()->row();
            if ($key->key != $val->key || empty($key)) {
                $val->units_picked = $this->getNoOfUnits($val->job_number, $val->worker_id);
                $val->extra_units = 0;
                $allData[$x] = array(
                    'job_number' => $val->job_number,
                    'worker_id' => $val->worker_id,
                    'units_picked' => $val->units_picked,
                    'duplicates' => $val->duplicates,
                    'overrides' => $val->overrides,
                    'extra_units' => $val->extra_units,
                    'extra_duplicates' => $val->extra_duplicates,
                    'extra_overrides' => $val->extra_overrides,
                    'prev_units' => $val->units_picked - $val->extra_units,
                    'prev_duplicate' => $val->duplicates - $val->extra_duplicates,
                    'prev_override' => $val->overrides - $val->extra_overrides,
                    'device_id' => $val->device_id,
                    'is_synced' => $val->key
                );
                $x++;

                $res = $this->row_worker_synced($val->job_number, $val->worker_id);
                if (count($res) > 0) {
                    //update record for existing worker on job	
                    $id = $res->id;

                    $val->duplicates = $res->duplicates + $val->extra_duplicates;//duplicates added in offline
                    $val->overrides = $res->overrides + $val->extra_overrides;//overrides added in offline
                    $data = array(
                        'units_picked' => $val->units_picked,
                        'duplicates' => $val->duplicates,
                        'overrides' => $val->overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'work_time_per_unit' => $val->work_time_per_unit,
                        'avg_units_per_hour' => $val->avg_units_per_hour,
                        'pay_per_unit' => $val->pay_per_unit,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
                    $this->db->where('id', $id);
                    $sync_worker = $this->db->update('row_details_worker', $data);

                    $synced_data = $this->row_worker_synced($val->job_number, $val->worker_id);

                    if ($sync_worker == 1) {
                        array_push($response, array('status' => 1, 'data' => $synced_data));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $synced_data));
                    }

                } else {//insert new record for new worker on job					
                    $data = array(
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id,
                        'units_picked' => $val->units_picked,
                        'duplicates' => $val->duplicates,
                        'overrides' => $val->overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'work_time_per_unit' => $val->work_time_per_unit,
                        'avg_units_per_hour' => $val->avg_units_per_hour,
                        'pay_per_unit' => $val->pay_per_unit,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
                    $this->db->insert('row_details_worker', $data);
                    $insert_id = $this->db->insert_id();

                    $synced_data = $this->row_worker_synced($val->job_number, $val->worker_id);

                    if ($insert_id != 0) {
                        array_push($response, array('status' => 1, 'data' => $synced_data));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $synced_data));
                    }
                }
            }
        }

        if (!empty($allData)) {
            $this->sync_offline_worker_row_store($allData);
        }

        return $response;
    }

    //
    //------------ sync offline worker row data [Mobile App to Server ] -------------
    //
    public function sync_offline_worker_row_store($row_workers)
    {
        # code...
        $response = array();
        $this->db->insert_batch('sync_row_details_worker', $row_workers);
        if ($this->db->insert_id()) {
            $sql = 'DELETE t1 FROM  `sync_row_details_worker` t1, `sync_row_details_worker` t2 WHERE t1.id < t2.id AND t1.job_number = t2.job_number AND t1.worker_id = t2.worker_id AND t1.device_id = t2.device_id';
            $query = $this->db->query($sql);
            $data = $this->row_worker_synced_off($row_workers);
            array_push($response, array('status' => 1, 'data' => $data));
        } else {
            $data = $this->row_worker_synced_off($row_workers);
            array_push($response, array('status' => 0, 'data' => $data));
        }
        return $response;
    }
    
    //
    //------------ Get row Worker Synced Offline-------------
    //
    function row_worker_synced_off($row_workers)
    {
        $x = 0;
        foreach ($row_workers as $key => $value) {
            $this->db->select('*');
            $this->db->from('sync_row_details_worker');
            $this->db->where(
                [
                    'job_number' => $value['job_number'],
                    'worker_id' => $value['worker_id'],
                    'device_id' => $value['device_id']
                ]
            );
            $this->db->order_by('id', 'desc');
            $this->db->limit(1); // getting the largest value;
            $res[$x] = $this->db->get()->row();
            $x++;
            // $this->updateSynRowData($value['job_number'], $value['worker_id'], $value['device_id']);
        }
    }

    //
    //------------ get picking row synced -------------
    //
    function row_synced($job_number, $worker_id, $row_tag_id)
    {
        $this->db->select('*');
        $this->db->from('row_scan');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker_id);
        $this->db->where('row_tag_id', $row_tag_id);
        $this->db->order_by('last_scan', 'desc');
        $this->db->limit(1);
        $res = $this->db->get();
        return $res;
    }

    //
    //------------ Sync offline data of row scans-------------
    //
    function sync_offline_row_scan($row_scans)
    {
        $response = array();
        $job = "";
        foreach ($row_scans as $val) {
            $job = $val->job_number;
            $res = $this->row_synced($val->job_number, $val->worker_id, $val->row_tag_id);
            if (count($res->result()) > 0) {
                //print_r($res->result()); die;
                if ($res->row()->status == 'unallocated') {
					//insert new record for unallocated row
                    $data = array(
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id,
                        'row_tag_id' => $val->row_tag_id,
                        'start_time' => $val->start_time,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'last_scan' => $val->last_scan,
                        'last_sync' => $val->last_sync,
                        'status' => $val->status
                    );
                    $this->db->update('row_scan', $data, ['job_number' => $val->job_number, 'worker_id' => $val->worker_id, 'row_tag_id' => $val->row_tag_id, ]);
                    $isAffected = $this->db->affected_rows();

                    $synced_data = $this->row_synced($val->job_number, $val->worker_id, $val->row_tag_id);

                    if ($isAffected) {
                        array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                    }

                } else {
						
					//update record for existing row of picker	
                    $id = $res->row()->id;

                    $data = array(
                        'start_time' => $val->start_time,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'last_scan' => $val->last_scan,
                        'last_sync' => $val->last_sync,
                        'status' => $val->status
                    );
                    $this->db->where('id', $id);
                    $sync_row = $this->db->update('row_scan', $data);

                    $synced_data = $this->row_synced($val->job_number, $val->worker_id, $val->row_tag_id);

                    if ($sync_row == 1) {
                        array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                    }
                }

            } else {//insert new record for new row of picker					
                $data = array(
                    'job_number' => $val->job_number,
                    'worker_id' => $val->worker_id,
                    'row_tag_id' => $val->row_tag_id,
                    'start_time' => $val->start_time,
                    'time_worked' => $val->time_worked,
                    'finish_time' => $val->finish_time,
                    'last_scan' => $val->last_scan,
                    'last_sync' => $val->last_sync,
                    'status' => $val->status
                );
                $this->db->insert('row_scan', $data);
                $insert_id = $this->db->insert_id();

                $synced_data = $this->row_synced($val->job_number, $val->worker_id, $val->row_tag_id);

                if ($insert_id != 0) {
                    array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                } else {
                    array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                }
            }
            //2222

            $this->updateRowData($val->job_number, $val->worker_id);
        }

        $this->db->select('*');
        $this->db->from('row_scan');
        $this->db->where('job_number', $job);
        $this->db->order_by('last_sync', 'desc');
        $this->db->limit(1);
        $buck = $this->db->get();
        $last_sync = $buck->row()->last_sync;

        $data['last_sync'] = $last_sync;
        $data['result'] = $response;


        return $data;
    }

    //
    //------------ Update Row Data for Sync and live -------------
    //
    public function updateRowData($jobNumber, $workerId)
    {
        # code...
        $totalUnits = $this->getNoOfUnits($jobNumber, $workerId);
        $this->db->update('sync_row_details_worker', ['units_picked' => $totalUnits, 'extra_units' => 0, 'prev_units' => $totalUnits], ['job_number' => $jobNumber, 'worker_id' => $workerId]);

        // comment on 28-08-2019 v
        // $this->db->update('row_details_worker', ['units_picked' => $totalUnits], ['job_number' => $jobNumber, 'worker_id' => $workerId]);
    }

    //
    //------------ After Syncing the row data -------------
    //
    public function afterSyncRow()
    {
        $data = $this->db->select('*')
            ->from('sync_row_details_worker')
            ->where('units_picked', 0)
            ->where('device_id !=', '')
            ->group_by('job_number, worker_id')
            ->get()->result();
        if (count($data) > 0) {
            foreach ($data as $key => $val) {
                $this->db->update(
                    'sync_row_details_worker',
                    [
                        'units_picked' => $val->units_picked,
                        'prev_units' => $val->prev_units,
                        'extra_units' => $val->extra_units
                    ],
                    [
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id
                    ]
                );
                $this->db->update(
                    'row_details_worker',
                    [
                        'units_picked' => $val->units_picked,
                    ],
                    [
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id
                    ]
                );
            }
            return 1;
        } else {
            return 2; // failed here
        }
    }

    //
    //------------ Sync row_details_worker -------------
    //
    function sync_worker_row($job_number)
    {
        $this->db->select('*');
        $this->db->from('row_details_worker');
        $this->db->where('job_number', $job_number);
        $query = $this->db->get()->result();
        
        // change on 14-06-2019
        return $query;
        
        if (count($query) > 0) {
			// This changes has been made by shailesh
            foreach ($query as $key => $val) {
                $res = $this->db->select('spdw.job_number, spdw.worker_id,  spdw.units_picked as units_picked,spdw.duplicates, spdw.overrides as overrides, spdw.id')
                    ->from('sync_row_details_worker as spdw')
                    ->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id])
                    ->order_by('id', 'DESC')
                    ->limit(1)
                    ->get()->row();
                
                $query[$key]->units_picked = $res->units_picked;
                $query[$key]->duplicates = $res->duplicates;
                $query[$key]->overrides = $res->overrides;
            }

            return $query;
        } else {
            return false;
        }
    }

    //
    //------------ Sync row_scan -------------
    //
    function sync_row_picking($job_number)
    {
        $this->db->select('*');
        $this->db->from('row_scan');
        $this->db->where('job_number', $job_number);
        $query = $this->db->get();

        if (count($query->result()) > 0) {

            return $query->result();
        } else {
            return false;
        }
    }

    //
    //------------ collector time for row job -------------
    //
    function collector_row_time($job_number, $collector_id, $device_id, $type, $time)
    {
        $this->db->select('*');
        $this->db->from('jobs');
        $this->db->where("FIND_IN_SET('$collector_id',name_collector)!=", 0);
        $this->db->where('job_number', $job_number);
        $data = $this->db->get();

        if (count($data->result()) > 0) {
            if ($type == 'start') {
                // check if started job not finish it return with 8
                $check = $this->check_job_finish($collector_id, $device_id, $job_number);

                if($check){
                    return 8;
                }
                else{
                    $start = $time;
                    $this->db->select('*');
                    $this->db->from('picking_details_collector');
                    $this->db->where('collector_id', $collector_id);
                    $this->db->where('device_id', $device_id);
                    $this->db->where('status', 'started');
                    $coll_unit_check = $this->db->get();

                    $this->db->select('*');
                    $this->db->from('row_details_collector');
                    $this->db->where('collector_id', $collector_id);
                    $this->db->where('device_id', $device_id);
                    $this->db->where('status', 'started');
                    $coll_row_check = $this->db->get(); 
                    
                    
                    //If No job started on same device by same collector
                    if (count($coll_unit_check->result()) <= 0 && count($coll_row_check->result()) <= 0) {

                        $this->db->select('*');
                        $this->db->from('row_details_collector');
                        $this->db->where('job_number', $job_number);
                        $this->db->where('device_id', $device_id);
                        $this->db->where('status', 'started');
                        $query = $this->db->get();

                        if (count($query->result()) <= 0) {

                            $this->db->select('*');
                            $this->db->from('row_details_collector');
                            $this->db->where('job_number', $job_number);
                            $this->db->where('collector_id', $collector_id);
                            $res = $this->db->get();

                            if (count($res->result()) <= 0) {
                                //New job for collector		
                                $data = array(
                                    'job_number' => $job_number,
                                    'collector_id' => $collector_id,
                                    'start_time' => $start,
                                    'w_type' => 1,
                                    'hourly_fee' => (float)$this->isCollectorHourly($collector_id),
                                    'status' => 'started',
                                    'device_id' => $device_id
                                );

                                if ($this->db->insert('row_details_collector', $data)) {
                                    // $this->db
                                    //     ->where(['job_number' => $job_number])
                                    //     ->update('row_details_collector', ['status' => 'started']);
                                    return 1; //Collector Job started
                                } else {
                                    return false;
                                }

                            } else {
                                    //if job status for collector is finished
                                if ($res->row()->status == 'finished') {
                                    $id = $res->row()->id;
                                    $this->db->where('id', $id);
                                    if ($this->db->update('row_details_collector', array('start_time' => $start, 'status' => 'started', 'device_id' => $device_id))) {
                                        return 1; //Collector Job started
                                    } else {
                                        return false;
                                    }

                                } elseif ($res->row()->status == 'paused') {

                                    return 9; //Job is paused, Collector needs to reset the job				  
                                }
                            }

                        } else {
                        	//Job is started by same collector
                            if ($query->row()->collector_id == $collector_id) {
                                return 2; //Job started by same collector
                            } else {
                                return 7; //Job started by another collector
                            }
                        }
                    } else {
                        if ($coll_unit_check->row()->job_number == $job_number || $coll_row_check->row()->job_number == $job_number) {
                            return 2; //Job started by same collector 
                        } else {
                            return 8; //Collector needs to finish the first job 
                        }
                    }
                }

            } elseif ($type == 'finish') {

                $finish = $time;

                $this->db->select('*');
                $this->db->from('row_details_collector');
                $this->db->where('job_number', $job_number);
                $this->db->where('collector_id', $collector_id);
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    if ($query->row()->status == 'started') {
                        $id = $query->row()->id;
                        $non_worked = $query->row()->time_not_worked; //Non-worked time

                        $to_time = strtotime($finish);
                        $from_time = strtotime($query->row()->start_time);									
							//echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                        $worked = round(abs($to_time - $from_time) / 3600, 2);	//Working Time in hours		
                        $worked = $query->row()->time_worked + $worked;
                        $time_worked = $worked - $non_worked;

                        $this->db->where('job_number', $job_number);
                        $this->db->where('collector_id', $collector_id);
                        if ($this->db->update('row_details_collector', array('time_worked' => $time_worked, 'finish_time' => $finish, 'status' => 'finished', 'device_id' => $device_id))) {
                            return 4; //Collector Job finished

                        } else {
                            return false;
                        }

                    } elseif ($query->row()->status == 'finished') {

                        return 3; //Collector Job is already finished

                    } elseif ($query->row()->status == 'paused') {

                        return 9; //Job is paused, Collector needs to reset the job						   
                    }

                } else {
                    return 5; //Collector Job is not started
                }
            } else {
                return false;
            }
        } else {
            return 6; //Invalid Collector
        }
    }

    //
    //------------ Sync row_details_collector-------------
    //
    function sync_collector_row($job_number, $collector_id)
    {
        $this->db->select('*');
        $this->db->from('row_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('collector_id', $collector_id);
        // $this->db->where('status != ', 'finished');
        $query = $this->db->get();
        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    } 

    //
    //------------ getting numbers of bucket -------------
    //
    public function get_row_buckets_num($job_number, $worker_id)
    {
        return $this->db->select('sum(units_picked) as extra_units, sum(duplicates) as duplicates, sum(overrides) as overrides, count(id) as nTime')
            ->from('sync_row_details_worker')
            ->where(['job_number' => $query->job_number, 'worker_id' => $query->worker_id])
            ->group_by('worker_id')
            ->get()->row();
    }





      /* ------------------------------------------------------------|
     ||||||||||||||||||][ ROW DATA END ][||||||||||||||||||||||||||||
    |-------------------------------------------------------------*/

    /* ----------------------------------------------------------------------
    | All Picking (Bucket) Related Functionality will come this side.
    |-----------------------------------------------------------------------
     */

    //
    //------------ All Methods -------------
    //
    
    //
    //------------ break time Bucket job -------------
    //
    function break_time_job_bucket($job_number, $type, $time)
    {
        if ($type == 'pause') {

            $pause = $time;

				//Pause Collectors in Job
            $this->db->select('*');
            $this->db->from('picking_details_collector');
            $this->db->where('job_number', $job_number);
            $this->db->where('status', 'started');
            $res = $this->db->get();

            if (count($res->result()) > 0) {
                $this->db->where('job_number', $job_number);
                $this->db->where('status', 'started');
                $this->db->update('picking_details_collector', array('pause_time' => $pause, 'status' => 'paused'));
						
				//Pause workers in Job
                $this->db->select('*');
                $this->db->from('picking_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('status', 'started');
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    $this->db->where('job_number', $job_number);
                    $this->db->where('status', 'started');
                    $this->db->update('picking_details_worker', array('pause_time' => $pause, 'status' => 'paused'));
                }

                // change job status on 15-01-2019
                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'paused'));

                return 1; //Job paused

            } else {
                return 2; //Job is not started
            }

        } elseif ($type == 'reset') {

            $reset = $time;

				//Reset Collectors in Job
            $this->db->select('*');
            $this->db->from('picking_details_collector');
            $this->db->where('job_number', $job_number);
            $this->db->where('status', 'paused');
            $res = $this->db->get();

            if (count($res->result()) > 0) {
                foreach ($res->result() as $val) {
                    $to_time = strtotime($reset);
                    $from_time = strtotime($val->pause_time);									
						  //echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                    $non_worked = round(abs($to_time - $from_time) / 3600, 2);	//Non-working Time in hours		
                    $non_worked = $val->time_not_worked + $non_worked;

                    $this->db->where('id', $val->id);
                    $this->db->update('picking_details_collector', array('reset_time' => $reset, 'status' => 'started', 'time_not_worked' => $non_worked));
                }
						
						//Reset workers in Job
                $this->db->select('*');
                $this->db->from('picking_details_worker');
                $this->db->where('job_number', $job_number);
                $this->db->where('status', 'paused');
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    foreach ($query->result() as $val_1) {
                        $to_time = strtotime($reset);
                        $from_time = strtotime($val_1->pause_time);									
								  //echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                        $non_worked = round(abs($to_time - $from_time) / 3600, 2);	//Non-working Time in hours
                        $non_worked = $val_1->time_not_worked + $non_worked;

                        $this->db->where('id', $val_1->id);
                        $this->db->update('picking_details_worker', array('reset_time' => $reset, 'status' => 'started', 'time_not_worked' => $non_worked));
                    }
                }


                // change job status on 15-01-2019
                $this->db->where('job_number', $job_number);
                $this->db->update('jobs', array('job_status' => 'active'));

                return 4; //Job reset

            } else {
                return 3; //Job is not paused
            }

        } else {
            return false;
        }
    }

    //
    //------------ get Unallocated Bucket Scan data -------------
    //
    function unallocate_bucket_scan($job_number, $bucket, $worker)
    {
        if ($job_number != '' && $bucket != '' && $worker!='') {

            $this->db->select('b.*,b.id as bucket_id, p.id as picking_id,
								  p.buckets_picked, p.*');
            $this->db->from('bucket_scan as b');
            $this->db->join('picking_details_worker as p', 'p.job_number = b.job_number');
            $this->db->join('jobs as j', 'j.job_number = b.job_number');
            $this->db->where('b.bucket_tag_id', $bucket);
            $this->db->where('b.job_number', $job_number);
            $this->db->where('p.worker_id', $worker);
            $this->db->where('b.worker_id', $worker);
            //$this->db->where('p.status', 'started');
            // $this->db->order_by('b.last_scan', 'desc');
            $this->db->order_by('p.id', 'desc');
            $this->db->limit(1);
            $query = $this->db->get(); // last worker scanned for bucket 			   

        }
        
       // echo $this->db->last_query();exit;
// echo "<pre>";
// print_r($query->row());exit;
        if (count($query->result()) > 0) {

            $id = $query->row()->bucket_id;
            $picking_id = $query->row()->picking_id;
            $buckets_count = $query->row()->buckets_picked - 1;

            $this->db->where('id', $id);
            if ($this->db->delete('bucket_scan')) {

                $this->db->where('id', $picking_id);
                if ($this->db->update('picking_details_worker', array('buckets_picked' => $buckets_count))) {
                    //return 1; //Worker Job started
                    $query->result()[0]->buckets_picked = $buckets_count;
                    return $query->result()[0];

                } else {
                    return false;
                }

            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //
    //------------ Get bucket data -------------
    //
    function bucket_data($job_number, $bucket)
    {
        if ($job_number != '' && $bucket != '') {
            $this->db->select('bucket_scan.*, worker.worker_name AS worker');
            $this->db->from('bucket_scan');
            $this->db->join('worker', 'worker.nfc_tag_id = bucket_scan.worker_id');
            $this->db->where('bucket_scan.bucket_tag_id', $bucket);
            $this->db->where('bucket_scan.job_number', $job_number);
            $this->db->order_by('bucket_scan.last_scan', 'desc');
            $this->db->limit(1);
            $query = $this->db->get(); 
            // echo $this->db->last_query();exit;
            // last worker scanned for bucket
            if (count($query->result()) > 0) {
                return $query->result();
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //
    //------------ worker time for unit job -------------
    //
    function worker_time_bucket($job_number, $worker, $type, $time)
    {
        $this->db->select('*');
        $this->db->from('picking_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('status', 'paused');
        $collect = $this->db->get();

        if (count($collect->result()) > 0) {

            return 6; //Job is paused

        } else {

            $this->db->select('worker_ids_allocated');
            $this->db->from('jobs');
            $this->db->where('job_number', $job_number);
            $query = $this->db->get();

            foreach ($query->result() as $val) {
                $workers = explode(',', $val->worker_ids_allocated);
            }

            if (in_array($worker, $workers)) {

                //if worker is valid		
                if ($type == 'start') {

                    $start = $time;
                    $this->db->select('*');
                    $this->db->from('picking_details_worker');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $worker);
                    $this->db->where("( status = 'started' OR status = 'paused')");
                    $res = $this->db->get();

                    if (count($res->result()) <= 0) {
                        $data = array(
                            'job_number' => $job_number,
                            'worker_id' => $worker,
                            'start_time' => $start,
                            'w_type' => (int)$this->isjobHourly($job_number),
                            'hourly_fee' => (float)$this->isWorkerHourly($worker),
                            'status' => 'started'
                        );
                        if ($this->db->insert('picking_details_worker', $data)) {
                            return 1; //Worker Job started
                        } else {
                            return false;
                        }
                    } 
                    else {
                        return 4; //Job already started
                    }

                } elseif ($type == 'finish') {

                    $finish = $time;

                    $this->db->select('*');
                    $this->db->from('picking_details_worker');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $worker);
                    $this->db->where('status', 'started');
                    $this->db->order_by('id', 'DESC');
                    $data = $this->db->get();

                    if (count($data->result()) > 0) {

                        if ($data->row()->status == 'started') {

                            $id = $data->row()->id;
                            $non_worked = $data->row()->time_not_worked; //Non-worked time

                            $to_time = strtotime($finish);
                            $from_time = strtotime($data->row()->start_time);									
									//echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                            $worked = round(abs($to_time - $from_time) / 3600, 2);	//Working Time in hours		
                            $worked = $data->row()->time_worked + $worked;
                            $time_worked = $worked - $non_worked;

                            $this->db->where('id', $id);
                            if ($this->db->update('picking_details_worker', array('time_worked' => $time_worked, 'finish_time' => $finish, 'status' => 'finished'))) {
                                return 5; //Worker Job finished
                            }

                        } elseif ($data->row()->status == 'finished') {

                            return 3; //Job already finished 
                        }

                    } else {
                        return 7; //Worker job not started
                    }
                } else {
                    return false;
                }

            } else {
                return 2; //Invalid Worker
            }
        }

    }

    //
    //------------ Bucket Scan [START] --------------------------------
    //
    public function bucket_time($job_number, $worker, $collector, $bucket, $time, $deviceId = '', $flag)
    {
        //lock Time
        $lock_out_time = $this->lockTime($job_number);
        // See if Jobs is started ...
        $picks = $this->db->select('*')
            ->from('picking_details_worker')
            ->where('job_number', $job_number)
            ->where('worker_id', $worker)
            ->order_by('id', 'DESC')
            ->get();

        $jobStatus = $this->db->select('status')
        ->from('picking_details_collector')
        ->where('job_number', $job_number)
        ->where('status', 'paused')
        ->get();

        if(count($jobStatus->result()) > 0){
            return 7;
        }

        if (count($picks->result()) > 0) {
            // update with current status with sync details :
            return $this->bucket_time_scan($job_number, $worker, $collector, $bucket, $time, $deviceId, true, $flag);
        } else {
            return 2; //Invalid Worker
        }
    }

	//bucket scan
    function bucket_time_scan($job_number, $worker, $collector, $bucket, $time, $deviceId, $status,$flag)
    {
        $picks = $this->db->select('w.*, j.multiple_scan, j.worker_lock_out_time')
            ->from('picking_details_worker as w')
            ->join('jobs as j', 'w.job_number = j.job_number')
            ->where('w.job_number', $job_number)
            ->where('w.worker_id', $worker)
            ->where("(w.status='started' or w.status='paused')")
            ->order_by('w.id', 'ASC')
            ->get()->row();

        if ($picks->status == 'started') {
            $now_scan = $time;
                   //Total buckets picked by Worker for this Job
            $buckets_picked = $picks->buckets_picked;
            $dup = $picks->duplicates; //Total Duplicates by Worker for this Job
            $overrides = $picks->overrides; //Total Overrides by Worker for this Job

            if ($job_number != '' && $worker != '' && $collector == '' && $bucket == '') {
                
                $workerLockOutTime = $this->_workerLockOutTime($job_number, $worker, $picks->worker_lock_out_time, $now_scan);
                // check worker lock out time
                if($workerLockOutTime == 1){
                    return 8; // worker lock out time not over
                }
                else{
                    /*--Scan Bucket when there is no collector and Bucket ------*/
                    // return $this->scanBucketOnNoCollectorAndBucket($job_number, $worker);
                    $this->db->select('*');
                    $this->db->from('bucket_scan');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $worker);
                    $this->db->where('last_scan', '0000-00-00 00:00:00');
                    $res = $this->db->get();

                    if (count($res->result()) <= 0) {
                        $data = array(
                            'job_number' => $job_number,
                            'worker_id' => $worker
                        );

                        if ($this->db->insert('bucket_scan', $data)) {
                            // $this->db->where('job_number', $job_number);
                            // $this->db->where('worker_id', $worker);
                            $this->db->where('id', $picks->id);
                            if ($this->db->update('picking_details_worker', array('last_scan' => $now_scan))) {
                                return 1; //Worker Scanned
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    } else {                                 
                                             //Already scanned worker 
                        // $this->db->where('job_number', $job_number);
                        // $this->db->where('worker_id', $worker);
                        $this->db->where('id', $picks->id);
                        if ($this->db->update('picking_details_worker', array('last_scan' => $now_scan))) {
                            return 1; //Worker Scanned
                        } else {
                            return false;
                        }
                    }
                }

            } elseif ($job_number != '' && $worker != '' && $collector == '' && $bucket != '') {

                $this->db->select('*');
                $this->db->from('worker');
                $this->db->where('nfc_tag_id', $bucket);
                $work = $this->db->get();

                if (count($work->result()) > 0) {//Worker tag is scanned instead of bucket tag
                    $this->db->select('*');
                    $this->db->from('picking_details_worker');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $bucket);
                    $query = $this->db->get();

                    if (count($query->result()) > 0) {
                        return 1; //Worker Scanned									  
                    } else {
                        return 2; //Invalid Worker
                    }
                } else {
                    //Bucket tag 								
                    $this->db->select('*');
                    $this->db->from('bucket_scan');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('bucket_tag_id', $bucket);
                    $this->db->order_by('last_scan', 'desc');
                    $this->db->limit(1);
                    $buck = $this->db->get();

                    //Duplicate bucket filled by worker
                    if (count($buck->result()) > 0) {
                                       //return 3 ;
                        $last_scan = $buck->row()->last_scan;                        
                        $to_time = strtotime($now_scan);
                        $from_time = strtotime($last_scan);									
                                       
                        $diff = round(abs($to_time - $from_time) / 60, 1);	//check lock out time on bucket for rescan
                        
                        $lock_out_time = $this->lockTime($job_number);
                        
                        //Lock-out time on duplicate bucket is passed	
                        if ($diff > $lock_out_time) {
                            if ($buck->row()->worker_id == $worker) {
                                   //If same worker had filled the duplicate Bucket
                                $id = $buck->row()->id;
                                $count = $buck->row()->total_scan+1;
                                $this->db->where('id', $id);
                                if ($this->db->update('bucket_scan', array('last_scan' => $now_scan, 'total_scan' => $count))) {
                                    
                                    $buckets_picked = $buckets_picked + 1;
                                    $data = array('buckets_picked' => $buckets_picked);
                                    $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

                                    if ($success) {
                                        return 5;
                                    } else {
                                        return false;
                                    }
                                } else {
                                    return false;
                                }
                            } else {
                            	if($picks->multiple_scan == 1){
		                    		$Res = $this->_multiScanBucket($job_number, $worker, $bucket, $now_scan);
		                    		if($Res){
		                    			$buckets_picked = $buckets_picked + 1;
			                            $data = array('buckets_picked' => $buckets_picked);
			                            $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);
			                            if($success)
			                            	return 5; //Bucket scanned
			                            else
			                            	return false;
		                    		}
		                    	}
		                    	else{
	                                //If new Worker is scanning duplicate bucket
	                                $this->db->where('job_number', $job_number);
	                                $this->db->where('worker_id', $worker);
	                                $this->db->where('bucket_tag_id', '');
	                                if ($this->db->update('bucket_scan', array('bucket_tag_id' => $bucket, 'last_scan' => $now_scan, 'total_scan' => 1))) {

	                                    $buckets_picked = $buckets_picked + 1;
	                                    $data = array('buckets_picked' => $buckets_picked);
	                                    $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

	                                    if ($success) {
	                                        return 5; //Bucket scanned
	                                    } else {
	                                        return false;
	                                    }
	                                } else {
	                                    return false;
	                                }
	                            }
                            }

                        } else { 
                            if($lock_out_time == 0){
                            	if($picks->multiple_scan == 1){
		                    		$Res = $this->_multiScanBucket($job_number, $worker, $bucket, $now_scan);
		                    		if($Res){
		                    			$buckets_picked = $buckets_picked + 1;
			                            $data = array('buckets_picked' => $buckets_picked);
			                            $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);
			                            if($success)
			                            	return 5; //Bucket scanned
			                            else
			                            	return false;
		                    		}
		                    	}
		                    	else{
	                                //If new Worker is scanning duplicate bucket
	                                $this->db->where('job_number', $job_number);
	                                $this->db->where('worker_id', $worker);
	                                $this->db->where('bucket_tag_id', '');
	                                if ($this->db->update('bucket_scan', array('bucket_tag_id' => $bucket, 'last_scan' => $now_scan, 'total_scan' => 1))) {

	                                    $buckets_picked = $buckets_picked + 1;
	                                    $data = array('buckets_picked' => $buckets_picked);
	                                    $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

	                                    if ($success) {
	                                        return 5; //Bucket scanned
	                                    } else {
	                                        return false;
	                                    }
	                                } else {
	                                    return false;
	                                }
	                            }
                            }
                            else{
                                //Duplicate bucket scanned in Lock-out time	
                                $dup = $dup + 1;
                                $data = array('duplicates' => $dup);
                                $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

                                if ($success) {
                            		return 3; //Duplicate bucket, please scan Collector
                                } else {
                                    return false;
                                }
                            }
                        }

                    } elseif (count($buck->result()) <= 0) {
                    	if($picks->multiple_scan == 1){
                    		$Res = $this->_multiScanBucket($job_number, $worker, $bucket, $now_scan);
                    		if($Res){
                    			$buckets_picked = $buckets_picked + 1;
	                            $data = array('buckets_picked' => $buckets_picked);
	                            $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);
	                            if($success)
	                            	return 5; //Bucket scanned
	                            else
	                            	return false;
                    		}
                    	}
                    	else{
                    		$this->db->where('job_number', $job_number);
	                        $this->db->where('worker_id', $worker);
	                        $this->db->where('bucket_tag_id', '');
	                        $success = $this->db->update('bucket_scan', array('bucket_tag_id' => $bucket, 'last_scan' => $now_scan));
	                        if ($success) {

	                            $buckets_picked = $buckets_picked + 1;
	                            $data = array('buckets_picked' => $buckets_picked);
	                            $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

	                            if ($success) {

	                                return 5; //Bucket scanned
	                            } else {
	                                return false;
	                            }

	                        } else {
	                            return false;
	                        }
                    	}
                    }

                }
            } elseif ($job_number != '' && $worker != '' && $collector != '' && $bucket != '') {

                $this->db->select('*');
                $this->db->from('collector');
                $this->db->where('nfc_tag_id', $collector);
                $this->db->where('c_type', '1');
                $collect = $this->db->get();

                if (count($collect->result()) > 0) {
                    $this->db->select('*');
                    $this->db->from('bucket_scan');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $worker);
                    $this->db->where('bucket_tag_id', $bucket);
                    $buck = $this->db->get();

                    if (count($buck->result()) > 0) {

                        $id = $buck->row()->id;
                        $count = $buck->row()->total_scan+1;
                        $this->db->where('id', $id);
                        if ($this->db->update('bucket_scan', array('last_scan' => $now_scan, 'total_scan' => $count))) {
                            
                            $buckets_picked = $buckets_picked + 1;
                            $overrides = $overrides + 1;
                            $data = array('buckets_picked' => $buckets_picked, 'overrides' => $overrides);
                            $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

                            if ($success) {
                                $update = $this->db->update('sync_picking_details_worker', ['buckets_picked' => $buckets_picked, 'prev_bucket' => $buckets_picked, 'overrides' => $overrides, 'prev_override' => $overrides], ['job_number' => $job_number, 'worker_id' => $worker, 'device_id' => $deviceId]);
                                return 6; //Collector scanned, duplicate bucket scanned!
                            } else {
                                return false;
                            }

                        } else {
                            return false;
                        }
                    } else {

                        $this->db->where('job_number', $job_number);
                        $this->db->where('worker_id', $worker);
                        $this->db->where('bucket_tag_id', '');
                        if ($this->db->update('bucket_scan', array('bucket_tag_id' => $bucket, 'last_scan' => $now_scan, 'total_scan' => 1))) {
                            
                            $buckets_picked = $buckets_picked + 1;
                            $overrides = $overrides + 1;
                            $data = array('buckets_picked' => $buckets_picked, 'overrides' => $overrides);
                            $success = $this->_updatePickingDetailsWorker($job_number, $worker, $data, $picks->id);

                            if ($success) {

                                $update = $this->db->update('sync_picking_details_worker', ['buckets_picked' => $buckets_picked, 'prev_bucket' => $buckets_picked, 'overrides' => $overrides, 'prev_override' => $overrides], ['job_number' => $job_number, 'worker_id' => $worker, 'device_id' => $deviceId]);
                                return 6; //Collector scanned, duplicate bucket scanned!
                            } else {
                                return false;
                            }

                        } else {
                            return false;
                        }
                    }

                } else {
                    return 4; //Collector not scanned
                }
            } elseif ($job_number != '' && $worker != '' && $collector != '' && $flag != '') {
                $this->db->select('*');
                $this->db->from('collector');
                $this->db->where('nfc_tag_id', $collector);
                $this->db->where('c_type', '1');
                $collect = $this->db->get();
               
                if(count($collect->result()) > 0){
                    $this->db->select('*');
                    $this->db->from('bucket_scan');
                    $this->db->where('job_number', $job_number);
                    $this->db->where('worker_id', $worker);
                    $this->db->where('last_scan', '0000-00-00 00:00:00');
                    $res = $this->db->get();

                    if (count($res->result()) <= 0) {
                        $data = array(
                            'job_number' => $job_number,
                            'worker_id' => $worker
                        );

                        if ($this->db->insert('bucket_scan', $data)) {
                            $this->db->where('id', $picks->id);
                            if ($this->db->update('picking_details_worker', array('last_scan' => $now_scan))) {
                                return 1; //Worker Scanned
                            } else {
                                return false;
                            }
                        } else {
                            return false;
                        }
                    } else {                                 
                        $this->db->where('id', $picks->id);
                        if ($this->db->update('picking_details_worker', array('last_scan' => $now_scan))) {
                            return 1; //Worker Scanned
                        } else {
                            return false;
                        }
                    }
                }
                else{
                    return 4; //Collector not scanned
                }
            }

        } elseif($picks->status == 'paused') {
            return 7;  //Job is paused
        }else{
            return 2;
        }
    }


    // get multiscan by veerender on 31-12-2018
    protected function _multiScanBucket($job_number, $worker, $bucket_tag_id, $now_scan){
    	$data = array(
                    'job_number' => $job_number,
                    'worker_id' => $worker,
                    'bucket_tag_id' => $bucket_tag_id,
                    'last_scan' => $now_scan,

                );
    	return $this->db->insert('bucket_scan', $data);
    }




    // get last bucket scan by veerender on 01-11-2018
    protected function _getLastBucketScan($job_number, $worker){
        $this->db->select('id');
        $this->db->from('bucket_scan');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $query = $this->db->get();
        $result = $query->row();
        
        return $result->id;
    }



    // update picking details worker by veerender on 01-11-2018
    protected function _updatePickingDetailsWorker($job_number, $worker, $data, $id){
        // $this->db->where('job_number', $job_number);
        // $this->db->where('worker_id', $worker);
        $this->db->where('id', $id);
        $success = $this->db->update('picking_details_worker', $data);
        return $success;
    }



    /*-------- Scan Bucket when there is no collector and Bucket -------------------*/
    protected function scanBucketOnNoCollectorAndBucket($job_number, $worker)
    {
        $this->db->select('*');
        $this->db->from('bucket_scan');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker);
        $this->db->where('last_scan', '0000-00-00 00:00:00');
        $res = $this->db->get();

        if (count($res->result()) <= 0) {
            $data = array(
                'job_number' => $job_number,
                'worker_id' => $worker
            );

            if ($this->db->insert('bucket_scan', $data)) {
                // $this->db->where('job_number', $job_number);
                // $this->db->where('worker_id', $worker);
                $this->db->where('worker_id', $worker);
                if ($this->db->update('picking_details_worker', array('last_scan' => $now_scan))) {
                    return 1; //Worker Scanned
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {								 
                                 //Already scanned worker 
            $this->db->where('job_number', $job_number);
            $this->db->where('worker_id', $worker);
            if ($this->db->update('picking_details_worker', array('last_scan' => $now_scan))) {
                return 1; //Worker Scanned
            } else {
                return false;
            }
        }
    }


    //
    //------------ Bucket Scan [END]--------------------------------
    //
    
    //
    //------------ Sync picking_details_collector -------------
    //
    function sync_collector_picking($job_number, $collector_id)
    {
        $this->db->select('*');
        $this->db->from('picking_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('collector_id', $collector_id);
        $query = $this->db->get();

        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    }

    //
    //------------ Sync picking_details_worker -------------
    //
    function sync_worker_picking($job_number)
    {
        $this->db->select('*');
        $this->db->from('picking_details_worker');
        $this->db->where('job_number', $job_number);
        $query = $this->db->get()->result();
        if (count($query) > 0) {
            return $query;
        } else {
            return false;
        }
    }
    //
    //------------ Sync bucket_scan -------------
    //
    function sync_bucket_picking($job_number)
    {
        $this->db->select('*');
        $this->db->from('bucket_scan');
        $this->db->where('job_number', $job_number);
        $query = $this->db->get();

        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    }

    //
    //------------ get picking collector synced -------------
    //
    function picking_collector_synced($job_number, $collector_id, $device_id)
    {
        $this->db->select('*');
        $this->db->from('picking_details_collector');
        $this->db->where('job_number', $job_number);
        $this->db->where('collector_id', $collector_id);
        $this->db->where('device_id', $device_id);
        $this->db->order_by('id', 'desc');
        $res = $this->db->get();
        return $res;
    }	

    //
    //------------ Sync offline data of picking_details_collector -------------
    //
    function sync_offline_collector_picking($picking_collectors)
    {
        $response = array();

        foreach ($picking_collectors as $val) {

            $res = $this->picking_collector_synced($val->job_number, $val->collector_id, $val->device_id);

            if (count($res->result()) > 0) {//update record for existing collector on job	
                $id = $res->row()->id;
                $status = $res->row()->status;
                $time_not_worked = $res->row()->time_not_worked;

                $data = array(
                    'start_time' => $val->start_time,
                    'time_worked' => $val->time_worked,
                    'finish_time' => $val->finish_time,
                    'pause_time' => $val->pause_time,
                    'reset_time' => $val->reset_time,
                    'time_not_worked' => $val->time_not_worked,
                    'status' => $val->status,
                    'device_id' => $val->device_id
                );
                

                // $this->db->where('id', $id);
                /*if($val->status == 'finished'){
                	$this->db->where('status', 'started');
    	            $this->db->where('job_number', $val->job_number);
    	            $this->db->where('collector_id', $val->collector_id);
                }
                elseif($val->status == 'paused'){
                	$this->db->where('status', 'started');
    	            $this->db->where('job_number', $val->job_number);
                }
                elseif($val->status == 'started' && $status == 'finished'){
                    $this->db->where('status', 'finished');
                    $this->db->where('id', $id);
                }
                elseif($val->status == 'started' && $time_not_worked != $val->time_not_worked && $status == 'started'){
                    $this->db->where('id', $id);
                }
                else{
                	$this->db->where('status', 'paused');
    	            $this->db->where('job_number', $val->job_number);
	            }*/
	            $this->db->where('id', $id);
                $sync_collector = $this->db->update('picking_details_collector', $data);

                $synced_data = $this->picking_collector_synced($val->job_number, $val->collector_id, $val->device_id);

                if ($sync_collector == 1) {
                    array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                } else {
                    array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                }

            } else {//insert new record for new collector on job					
                $data = array(
                    'job_number' => $val->job_number,
                    'collector_id' => $val->collector_id,
                    'start_time' => $val->start_time,
                    'time_worked' => $val->time_worked,
                    'finish_time' => $val->finish_time,
                    'pause_time' => $val->pause_time,
                    'reset_time' => $val->reset_time,
                    'time_not_worked' => $val->time_not_worked,
                    'status' => $val->status,
                    'device_id' => $val->device_id
                );
                $this->db->insert('picking_details_collector', $data);
                $insert_id = $this->db->insert_id();

                $synced_data = $this->picking_collector_synced($val->job_number, $val->collector_id, $val->device_id);

                if ($insert_id != 0) {
                    array_push($response, array('status' => 1, 'data' => $synced_data->row()));
                } else {
                    array_push($response, array('status' => 0, 'data' => $synced_data->row()));
                }
            }


            // change job status on 19-01-2019
            $this->db->where('job_number', $val->job_number);
            $this->db->update('jobs', array('job_status' => $val->status));
        }

        return $response;
    }

    //
    //------------ Sync offline data of picking_details_workers -------------
    //
    function sync_offline_worker_picking($picking_workers)
    {
        $response = array();
        $x = 0;
        $allData = [];
        if (empty($picking_workers) || $picking_workers == [] || $picking_workers == null) {
            return 0;
        }

        foreach ($picking_workers as $val) {
            $key = $this->db->select('key')->from('picking_details_worker')->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id])->order_by('id','desc')->get()->row();

            if ($key->key != $val->key || empty($key)) {

                $allData[$x] = array(
                    'job_number' => $val->job_number,
                    'worker_id' => $val->worker_id,
                    'buckets_picked' => $val->buckets_picked,
                    'duplicates' => $val->duplicates,
                    'overrides' => $val->overrides,
                    'extra_buckets' => $val->extra_buckets,
                    'extra_duplicates' => $val->extra_duplicates,
                    'extra_overrides' => $val->extra_overrides,
                    'prev_bucket' => $val->buckets_picked - $val->extra_buckets,
                    'prev_duplicate' => $val->duplicates - $val->extra_duplicates,
                    'prev_override' => $val->overrides - $val->extra_overrides,
                    'device_id' => $val->device_id,
                    'is_synced' => $val->key
                );
                $x++;


                $res = $this->picking_worker_synced($val->job_number, $val->worker_id);

                if (count($res) > 0) {//update record for existing worker on job	
                    $id = $res->id;
                    $val->buckets_picked = $res->buckets_picked + $val->extra_buckets;//buckets_picked added in offline
                    $val->duplicates = $res->duplicates + $val->extra_duplicates;//duplicates added in offline
                    $val->overrides = $res->overrides + $val->extra_overrides;//overrides added in offline
                    $data = array(
						// 'buckets_picked' => $val->buckets_picked,
						// 'duplicates' => $val->duplicates,
						// 'overrides' => $val->overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'pick_time_per_bucket' => $val->pick_time_per_bucket,
                        'avg_buckets_per_hour' => $val->avg_buckets_per_hour,
                        'pay_per_bucket' => $val->pay_per_bucket,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
     
                    // $this->db->where('id', $id);
                    // change on 15-02-2019 by v
                    /*if($val->status == 'finished'){
					    $this->db->where('status', 'started');
					    $this->db->where('job_number', $val->job_number);
					    $this->db->where('worker_id', $val->worker_id);
					}
                    elseif($val->status == 'paused'){
					    $this->db->where('status', 'started');
					    $this->db->where('job_number', $val->job_number);
					}
                    elseif($val->status == 'started' && $val->time_not_worked != $res->time_not_worked){
                        $this->db->where('id', $res->id);
                    }
                    elseif($val->status == 'started' && $res->status == ''){
                        $this->db->where('id', $res->id);
                    }
					else{
					    $this->db->where('status', 'paused');
					    $this->db->where('job_number', $val->job_number);
					}*/
					$this->db->where('id', $res->id);
                    $sync_worker = $this->db->update('picking_details_worker', $data);


                    // update last sync time
                    // change on 15-02-2019 by v
     //                $this->db->where('status', 'started');
     //                $this->db->where('worker_id', $val->worker_id);
					// $this->db->where('job_number', $val->job_number);
     //                $sync_worker = $this->db->update('picking_details_worker', array('last_scan' => $val->last_scan));

                    $synced_data = $this->picking_worker_synced($val->job_number, $val->worker_id);

                    if ($sync_worker == 1) {
                        array_push($response, array('status' => 1, 'data' => $synced_data));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $synced_data));
                    }

                } else {//insert new record for new collector on job					
                    $data = array(
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id,
						// 'buckets_picked' => $val->buckets_picked,
						// 'duplicates' => $val->duplicates,
						// 'overrides' => $val->overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'pick_time_per_bucket' => $val->pick_time_per_bucket,
                        'avg_buckets_per_hour' => $val->avg_buckets_per_hour,
                        'pay_per_bucket' => $val->pay_per_bucket,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
                    $this->db->insert('picking_details_worker', $data);
                    $insert_id = $this->db->insert_id();

                    $synced_data = $this->picking_worker_synced($val->job_number, $val->worker_id);

                    if ($insert_id != 0) {
                        array_push($response, array('status' => 1, 'data' => $synced_data));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $synced_data));
                    }
                }
            }
        }

        
        if (!empty($allData)) {
            $backData = $this->sync_offline_worker_picking_store($allData);
        }
        else{
            $backData = array();
            foreach ($picking_workers as $key => $value) {
                $dat = array('status' => 1, 'data' => $value);
                
                array_push($backData, $dat);
            }
        }

        return $backData;
    }

    //
    //------------ get picking worker synced -------------
    //
    function picking_worker_synced($job_number, $worker_id)
    {
        $this->db->select('*');
        $this->db->from('picking_details_worker');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker_id);
        $this->db->where('status !=', 'finished');
        $this->db->order_by('id', 'desc');
        $query = $this->db->get()->row();
        if (count($query) > 0) {
            $res = $this->db->select('sum(spdw.buckets_picked) as buckets_picked, sum(spdw.duplicates) as duplicates, sum(spdw.overrides) as overrides, count(spdw.id) as nTime')
                ->from('sync_picking_details_worker as spdw')
                ->where(['spdw.job_number' => $query->job_number, 'spdw.worker_id' => $query->worker_id])
                ->group_by('spdw.worker_id')
                ->get()->row();
            if ($res) {
                if ($res->buckets_picked > $query->buckets_picked) {
                    $query->buckets_picked = $res->buckets_picked - ($res->nTime - 1) * $query->buckets_picked;
                }
                if ($res->duplicates > $query->duplicates) {
                    $query->duplicates = $res->duplicates - ($res->nTime - 1) * $query->duplicates;
                }

                if ($res->overrides > $query->overrides) {
                    $query->overrides = $res->overrides - ($res->nTime - 1) * $query->overrides;
                }
            }
        }

        return $query;
    }

    //
    //---- sync offline worker picking data [Mobile App to Server ] -------
    //

    public function sync_offline_worker_picking_store($picking_workers)
    {
        # code...
        $response = array();
        $this->db->insert_batch('sync_picking_details_worker', $picking_workers);
        if ($this->db->insert_id()) {
            $sql = 'DELETE t1 FROM `sync_picking_details_worker` t1, `sync_picking_details_worker` t2 WHERE t1.id < t2.id AND t1.job_number = t2.job_number AND t1.worker_id = t2.worker_id AND t1.device_id = t2.device_id';
            $query = $this->db->query($sql);
            $response = $this->picking_worker_synced_off($picking_workers);
        } else {
            $response = $this->picking_worker_synced_off($picking_workers);
        }
        return $response;
    }

    //
    //---- Sync offline data to live and return current data -------
    //
    function picking_worker_synced_off($picking_workers)
    {
        $x = 0;
        $res = [];
        foreach ($picking_workers as $key => $value) {
            $getData = $this->feedPickingDataToLive($value['job_number'], $value['worker_id']);
            if (!empty($getData)) {
                $doLivePickeUpdate = $this->updatePickingDataToLive($value['job_number'], $value['worker_id'], $getData);
            }

            $this->db->select('*');
            $this->db->from('picking_details_worker');
            $this->db->where(['job_number' => $value['job_number'], 'worker_id' => $value['worker_id']]);
            $this->db->order_by('id', 'desc');
            $res[$x]['status'] = 1;
            $res[$x]['data'] = $this->db->get()->row();
            $x++;
        }
        return $res;
    }

    //
    //---- Fetching required data from Synced Picking Details -------
    //
    public function feedPickingDataToLive($job_number, $worker_id)
    {
        return $this->db->select("SUM(extra_buckets) as extra_buckets, 
            SUM(extra_duplicates) as extra_duplicates,
            SUM(extra_overrides) as extra_overrides
            ")
            ->from('sync_picking_details_worker')
            ->where(
                [
                    'job_number' => $job_number,
                    'worker_id' => $worker_id
                ]
            )
            ->group_by('job_number, worker_id')
            ->get()
            ->row();

    }

    //
    //---- Updating Fetched Data to the live [synced to live] -------
    //
    public function updatePickingDataToLive($job_number, $worker_id, $data)
    {
        $ndata = $this->db
            ->select('buckets_picked, duplicates, overrides')
            ->from('picking_details_worker')
            ->where(
                [
                    'job_number' => $job_number,
                    'worker_id' => $worker_id,
                    'status' => 'started'
                ]
            )
            ->get()->row();
        $this->db->where(
            [
                'job_number' => $job_number,
                'worker_id' => $worker_id,
                'status' => 'started'
            ]
        )->update(
            'picking_details_worker',
            [
                // "buckets_picked" => $data->extra_buckets + $ndata->buckets_picked,
                "duplicates" => $data->extra_duplicates + $ndata->duplicates,
                "overrides" => $data->extra_overrides + $ndata->overrides
            ]
        );
        if ($this->db->affected_rows()) {
            $data = $this->db
                ->where([
                    'job_number' => $job_number,
                    'worker_id' => $worker_id
                ])
                ->update(
                    'sync_picking_details_worker',
                    [
                        'extra_buckets' => 0,
                        'extra_duplicates' => 0,
                        'extra_overrides' => 0,
                    ]
                );
            if ($this->db->affected_rows()) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    //
    //-------------------- collector time for unit job --------------------------
    //
    function collector_unit_time($job_number, $collector_id, $device_id, $type, $time)
    {
        $this->db->select('*');
        $this->db->from('jobs');
        $this->db->where("FIND_IN_SET('$collector_id',name_collector)!=", 0);
        $this->db->where('job_number', $job_number);
        $data = $this->db->get();

        if (count($data->result()) > 0) {
            if ($type == 'start') {
                // check if started job not finish it return with 8
                $check = $this->check_job_finish($collector_id, $device_id, $job_number);

                if($check){
                    return 8;
                }
                else{
                    $start = $time;

                    $this->db->select('*');
                    $this->db->from('picking_details_collector');
                    $this->db->where('collector_id', $collector_id);
                    $this->db->where('device_id', $device_id);
                    $this->db->where('status', 'started');
                    $coll_unit_check = $this->db->get();

                    $this->db->select('*');
                    $this->db->from('row_details_collector');
                    $this->db->where('collector_id', $collector_id);
                    $this->db->where('device_id', $device_id);
                    $this->db->where('status', 'started');
                    $coll_row_check = $this->db->get(); 
                        
                        //If No job started on same device by same collector
                    if (count($coll_unit_check->result()) <= 0 && count($coll_row_check->result()) <= 0) {

                        $this->db->select('*');
                        $this->db->from('picking_details_collector');
                        $this->db->where('job_number', $job_number);
                        $this->db->where('device_id', $device_id);
                        $query = $this->db->get();

                        if ($query->row()->status == 'started') {
                                    //Job is started by same collector
                            if ($query->row()->collector_id == $collector_id) {
                                return 2; //Job started by same collector
                            } else {
                                return 7; //Job started by another collector
                            }

                        } elseif ($query->row()->status == 'paused') {
                            //Job is paused by same collector
                            if ($query->row()->collector_id == $collector_id) {
                                return 9; //Job is paused, Collector needs to reset the job	
                            } else {
                                return 10; //Job is paused by another collector
                            }
                        } else {
                            // echo "in box"; exit;
                            $this->db->select('*');
                            $this->db->from('picking_details_collector');
                            $this->db->where('job_number', $job_number);
                            $this->db->where('collector_id', $collector_id);
                            $res = $this->db->get();

                            if (count($res->result()) <= 0) {
                                //New job for collector
                                // echo "not available"; exit;

                                $data = array(
                                    'job_number' => $job_number,
                                    'collector_id' => $collector_id,
                                    'start_time' => $start,
                                    'w_type' => 1,
                                    'hourly_fee' => (float)$this->isCollectorHourly($collector_id),
                                    'status' => 'started',
                                    'device_id' => $device_id
                                );

                                if ($this->db->insert('picking_details_collector', $data)) {
                                    return 1; //Collector Job started
                                } else {
                                    return false;
                                }

                            } else {
                            	// echo "<pre>";
                            	// print_r($res->row());exit;
                                // echo "available"; exit;
                                //if job status for collector is finished
                                if ($res->row()->status == 'finished') {
                                    $id = $res->row()->id;
                                    $this->db->where('id', $id);

                                    if ($this->db->update('picking_details_collector', array('start_time' => $start, 'status' => 'started', 'device_id' => $device_id))) {
                                        return 1; //Collector Job started
                                    } else {
                                        return false;
                                    }

                                } elseif ($res->row()->status == 'paused') {
                                    return 9; //Job is paused, Collector needs to reset the job				  
                                }
                            }
                        }

                    } else {
                        if ($coll_unit_check->row()->job_number == $job_number || $coll_row_check->row()->job_number == $job_number) {

                            return 2; //Job started by same collector 
                        } else {
                            return 8; //Collector needs to finish the first job 
                        }
                    }
                }

            } elseif ($type == 'finish') {
                $finish = $time;

                $this->db->select('*');
                $this->db->from('picking_details_collector');
                $this->db->where('job_number', $job_number);
                $this->db->where('collector_id', $collector_id);
                $query = $this->db->get();

                if (count($query->result()) > 0) {
                    if ($query->row()->status == 'started') {
                        $id = $query->row()->id;
                        $non_worked = $query->row()->time_not_worked; //Non-worked time

                        $to_time = strtotime($finish);
                        $from_time = strtotime($query->row()->start_time);									
							//echo round(abs($to_time - $from_time) / 60,1). " minute"; die;
                        $worked = round(abs($to_time - $from_time) / 3600, 2);	//Working Time in hours		
                        $worked = $query->row()->time_worked + $worked;
                        $time_worked = $worked - $non_worked;

                        $this->db->where('job_number', $job_number);
                        $this->db->where('collector_id', $collector_id);
                        if ($this->db->update('picking_details_collector', array('time_worked' => $time_worked, 'finish_time' => $finish, 'status' => 'finished', 'device_id' => $device_id))) {
                            return 4; //Collector Job finished

                        } else {
                            return false;
                        }

                    } elseif ($query->row()->status == 'finished') {

                        return 3; //Collector Job is already finished

                    } elseif ($query->row()->status == 'paused') {

                        return 9; //Job is paused, Collector needs to reset the job						   
                    }

                } else {
                    return 5; //Collector Job is not started
                }
            } else {
                return false;
            }
        } else {
            return 6; //Invalid Collector
        }
    }

    //
    //-------------------- check job finish or not --------------------------
    //
    function check_job_finish($collector_id, $device_id, $job_number)
    {
        $this->db->select('job_number, collector_id, device_id');
        $this->db->from('picking_details_collector');
        $this->db->where("(device_id = '$device_id' OR collector_id = '$collector_id')");
        $this->db->where('status', 'started');
        $query = $this->db->get();
        $res = $query->row_array();
        
        $this->db->select('job_number, collector_id, device_id');
        $this->db->from('row_details_collector');
        $this->db->where("(device_id = '$device_id' OR collector_id = '$collector_id')");
        $this->db->where('status', 'started');
        $query2 = $this->db->get();
        $res2 = $query2->row_array();

        // echo "<pre>";
        // print_r($res);
        // print_r($res2);
        // exit;
        if(!empty($res) || !empty($res2)){
            if(!empty($res) && $res['job_number'] == $job_number && $res['collector_id'] == $collector_id && $res['device_id'] == $device_id){             
                return false;    
            }
            else if(!empty($res2) && $res2['job_number'] == $job_number && $res2['collector_id'] == $collector_id && $res2['device_id'] == $device_id){
                return false;    
            }
            else if(!empty($res) && $res['job_number'] != $job_number && $res['collector_id'] != $collector_id && $res['device_id'] == $device_id){             
                return false;    
            }
            else if(!empty($res2) && $res2['job_number'] != $job_number && $res2['collector_id'] != $collector_id && $res2['device_id'] == $device_id){
                return false;    
            }
            else if(!empty($res) && $res['job_number'] != $job_number && $res['collector_id'] != $collector_id && $res['device_id'] != $device_id){ 
                return false;    
            }
            else if(!empty($res2) && $res2['job_number'] != $job_number && $res2['collector_id'] != $collector_id && $res2['device_id'] != $device_id){
                return false;    
            }
            else{
                return true;
            }
        }
        else{
            return false;
        }
    }

    //
    //-------------------- get picking bucket synced --------------------------
    //
    function bucket_worker_synced($job_number, $worker_id, $bucket_tag_id)
    {
        $this->db->select('*');
        $this->db->from('bucket_scan');
        $this->db->where('job_number', $job_number);
        $this->db->where('worker_id', $worker_id);
        $this->db->where('bucket_tag_id', $bucket_tag_id);
        $this->db->order_by('id', 'DESC');
        $res = $this->db->get();
        return $res;
    }

    //
    //-------------------- Sync offline data of bucket scans --------------------------
    //
    function sync_offline_bucket_scan($bucket_scans)
    {
        $response = array();
        
        foreach ($bucket_scans as $val) {

            $job = $val->job_number;
            $syncedFinalData = '';
            $res = $this->bucket_worker_synced($val->job_number, $val->worker_id, $val->bucket_tag_id);
            if(($val->bucket_unique_key != '') && ($val->bucket_unique_key != $res->row()->bucket_unique_key)){
            	// check offline unallocatd
            	if($val->bucket_lastflag == 'unallocated'){
            		
                   if($val->scanstatus != 'yes'){

                		$scanendBucket = $this->db->select('*')->from('bucket_scan')->where('job_number', $val->job_number)->where('worker_id', $val->worker_id)->where('bucket_tag_id', $val->bucket_tag_id)->order_by('last_scan', 'desc')->get()->row();
                		
                		if(!empty($scanendBucket) && $scanendBucket->total_scan > 1){
                			$data['total_scan'] = $scanendBucket->total_scan - 1;
                			$data['last_sync'] = date('Y-m-d H:i:s');
                			$this->db->where('id', $scanendBucket->id);
    			        	$this->db->update('bucket_scan', $data);

    			        	// update total picked
    	                	$this->_updatePickedBucked($val->job_number, $val->worker_id, 1, 'unallocate');
                		}
                		else if(!empty($scanendBucket) && $scanendBucket->total_scan == 1){
                			$this->db->where('id', $scanendBucket->id);
    			        	$this->db->delete('bucket_scan');

    			        	// update total picked
    	                	$this->_updatePickedBucked($val->job_number, $val->worker_id, 1, 'unallocate');
                		}
                    }

            		$syncedFinalData = $val;
            	}
            	else{

	                //insert new record for new bucket of picker					
	                $data = array(
	                    'job_number' => $val->job_number,
	                    'worker_id' => $val->worker_id,
	                    'bucket_tag_id' => $val->bucket_tag_id,
	                    'last_scan' => $val->last_scan,
	                    'last_sync' => $val->last_sync,
	                    'bucket_unique_key' => $val->bucket_unique_key
	                );
	                $this->db->insert('bucket_scan', $data);
	                $insert_id = $this->db->insert_id();    

	                if($insert_id != 0){
	                	// update total picked
	                	$this->_updatePickedBucked($val->job_number, $val->worker_id, 1, 'allocate');
	                }
	                

                    $synced_data = $this->bucket_worker_synced($val->job_number, $val->worker_id, $val->bucket_tag_id);
                    $syncedFinalData = $synced_data->row();
	                
	            }

	            
            
                if ($insert_id != 0) {
                	
                	array_push($response, array('status' => 1, 'data' => $syncedFinalData));
                } else {
                    array_push($response, array('status' => 0, 'data' => $syncedFinalData));
                }
            
            }
        }

        $this->db->select('*');
        $this->db->from('bucket_scan');
        $this->db->where('job_number', $job);
        $this->db->order_by('last_sync', 'desc');
        $this->db->limit(1);
        $buck = $this->db->get();

        $last_sync = $buck->row()->last_sync ? date('d-m-Y H:i', strtotime($buck->row()->last_sync)) : date('d-m-Y H:i');

        $data['last_sync'] = $last_sync;
        $data['result'] = $response;

        return $data;
    }
    
    //
    //-------------------- getting numbers of bucket --------------------------
    //
    public function get_picker_buckets_num($job_number, $worker_id)
    {
        return $this->db->select('sum(spdw.buckets_picked) as buckets_picked, sum(spdw.duplicates) as duplicates, sum(spdw.overrides) as overrides, count(spdw.id) as nTime')
            ->from('sync_picking_details_worker as spdw')
            ->where(['spdw.job_number' => $job_number, 'spdw.worker_id' => $worker_id])
            ->group_by('spdw.worker_id')
            ->get()->row();
    }

      /* ------------------------------------------------------------|
     ||||||||||||||||||][ Picking DATA END ][|||||||||||||||||||||||
    |-------------------------------------------------------------*/

    /* ----------------------------------------------------------
    | Here comes Other Basic Functionality
    |------------------------------------------------------------
     */

    //
    //----------- Getting all the sum of finished No of units ----------------
    //
    public function getNoOfUnits($jobNumber, $workerId)
    {
        # code...
        $getRowScan = $this->db
            ->select('rs.*, SUM(brd.no_vines) as vines')
            ->from('row_scan as rs')
            ->join('block_row_data as brd', 'rs.row_tag_id = brd.nfc_tag_id', 'left')
            ->where([
                'rs.job_number' => $jobNumber,
                'rs.worker_id' => $workerId,
                'rs.status' => "finished"
            ])
            ->group_by('status')
            ->get()
            ->row();
        return (int)$getRowScan->vines;
    }

    //
    //----------- Getting Working Hourly Fees and if JOb is hourly or not ----------------
    //

    // FOr Job Work Type
    public function isjobHourly($jobId)
    {
        $w_type = $this->db->select('w_type')
            ->from('jobs')
            ->where('job_number', $jobId)
            ->get()->row()->w_type;
        return $w_type;
    }

    // For Worker
    public function isWorkerHourly($workerId)
    {
        $hourly_fee = $this->db->select('hourly_fee')
            ->from('worker')
            ->where('nfc_tag_id', $workerId)
            ->get()->row()->hourly_fee;
        return $hourly_fee;
    }

    // fore Collector
    public function isCollectorHourly($collectorId)
    {
        $hourly_fee = $this->db->select('hourly_fee')
            ->from('collector')
            ->where('nfc_tag_id', $collectorId)
            ->get()->row()->hourly_fee;
        return $hourly_fee;
    }

    //
    //----------- Get the Lock time of the Job ----------------
    //
    public function lockTime($job_number)
    {
        # code...
        $job = $this->db->select('lock_out_time')
            ->from('jobs')
            ->where('job_number', $job_number)
            ->get();

        $lock = $job->row()->lock_out_time; //Lock out time for all buckets of job
        $lock_time = explode(':', $lock);
        return ($lock_time[0] * 60.0 + $lock_time[1] * 1.0);
    }

    //
    //------------ check NFC_Tag_ID --------------------------------
    //
    function check_tag($nfc_tag)
    {
        $this->db->select('*');
        $this->db->from('collector');
        $this->db->where('nfc_tag_id', $nfc_tag);
        $query = $this->db->get();

        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    }	
     
    //
    //------------ All Block Rows --------------------------------
    //
    /*function all_block_row()
    {
        $this->db->select('*');
        $this->db->from('block_row_data');
        $query = $this->db->get();

        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    }*/
    function all_block_row($collector_id)
    {
        $sql = "SELECT d.id, d.block_id, d.no_vines, d.no_metres, d.nfc_tag_id, d.row_no, d.created_at, d.updated_at FROM `block_row_data` as d join block as b on d.block_id=b.id join vineyard as v on v.id=b.vineyard_id join jobs as j on j.vineyard_name=v.id WHERE find_in_set('".$collector_id."', j.name_collector) and j.job_status !='closed' and j.job_status !='completed' and j.job_status !='created' and j.job_status !='archived' and j.job_status !='deleted' and d.nfc_tag_id!='' group by d.nfc_tag_id";

        $query = $this->db->query($sql);
        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    }




    // update total bucket picked
    protected function _updatePickedBucked($job_number, $worker_id, $pickedBucked, $flag){

        $worker = $this->db->select('id, buckets_picked')->from('picking_details_worker')->where('job_number', $job_number)->where('worker_id', $worker_id)->order_by('id', 'desc')->get()->row();

        if(empty($worker)){
        	$data['job_number'] = $job_number;
        	$data['worker_id'] = $worker_id;
        	$data['buckets_picked'] = $pickedBucked;
        	
        	$this->db->insert('picking_details_worker', $data);

        }
        else{
        	if($flag == 'unallocate'){
        		$data['buckets_picked'] = $worker->buckets_picked != 0 ? ($worker->buckets_picked - $pickedBucked) : $worker->buckets_picked;
                $this->db->where('id', $worker->id);
                $this->db->update('picking_details_worker', $data);
        	}
    		/*
            change on 27-08-2019 by v
            else{
    			$data['buckets_picked'] = ((int)$worker->buckets_picked + $pickedBucked);
    		}
        	
        	// $this->db->where('job_number', $job_number);
        	// $this->db->where('worker_id', $worker_id);
	        // $this->db->where('status', 'started');
            $this->db->where('id', $worker->id);
	        $this->db->update('picking_details_worker', $data);*/
        }

        return true;
    }




    //Get worker of perticular job by v on 15-01-2019
    function get_job_worker($job_number)
    {
        $sql = "SELECT W.nfc_tag_id, W.worker_name, (SELECT status from picking_details_worker WHERE job_number='$job_number' AND worker_id = W.nfc_tag_id ORDER BY id DESC LIMIT 1) AS status FROM jobs AS J
                    JOIN worker AS W ON FIND_IN_SET(W.nfc_tag_id, J.worker_ids_allocated) > 0
                    WHERE job_number = '$job_number'";
        
        $query = $this->db->query($sql);
        if (count($query->result()) > 0) {
            return $query->result();
        } else {
            return false;
        }
    }



    // check worker lock out time by veerender on 24-01-2019
    protected function _workerLockOutTime($job_number, $worker, $lockOutTime, $now_scan){

        $lockOutTimeMinuts = 0;
        
        if(!empty($lockOutTime)){
            $lock_time = explode(':', $lockOutTime);
            $lockOutTimeMinuts = ($lock_time[0] * 60.0 + $lock_time[1] * 1.0);
        }
        

        $workerDetails = $this->db->select('last_scan')
                                    ->from('picking_details_worker')
                                    ->where('job_number', $job_number)
                                    ->where('worker_id', $worker)
                                    ->where('status', 'started')
                                    ->order_by('id', 'DESC')
                                    ->get()->row();

        $last_scan = $workerDetails->last_scan;

        $to_time = strtotime($now_scan);
        $from_time = strtotime($last_scan);
                               
        $diff = round(abs($to_time - $from_time) / 60, 1);  //check lock out time on bucket for rescan

        if($diff < $lockOutTimeMinuts){
            return 1;
        }
        else{
            return 0;
        }
    }





    // change job status on 19-02-2019
    protected function _changeJobStatus($val){
        
        $status = $this->getStatus($val->job_number, $val->j_type);
        
        $activeIds = array();
        $finishIds = array();
        $inProgressIds = array();

        if ($status == 'active' && $val->job_status != 'active') {
            array_push($activeIds, $val->id);
        }
        elseif ($status == 'finish' && $val->job_status != 'finished') {
            array_push($finishIds, $val->id);
        }
        elseif ($status == 'inprogress' && $val->job_status != 'In Progress') {
            array_push($inProgressIds, $val->id);
        }

        if (!empty($activeIds)) {
            $this->db->where_in('id', $activeIds);
            $active_job = $this->db->update('jobs', array('job_status' => 'active'));
        }

        if (!empty($finishIds)) {
            $this->db->where_in('id', $finishIds);
            $finish_job = $this->db->update('jobs', array('job_status' => 'finished'));
        }

        if (!empty($inProgressIds)) {
            $this->db->where_in('id', $inProgressIds);
            $in_progress_job = $this->db->update('jobs', array('job_status' => 'In Progress'));
        }
    }


    public function getStatus($jobNumber, $jobType) {
        if($jobType == '2'){
            $collectorTbl = 'picking_details_collector';
            $workerTbl = 'picking_details_worker';
        }
        else{
            $collectorTbl = 'row_details_collector';
            $workerTbl = 'row_details_worker';
        }

        
        $jobExitInCollector = $this->db->where('job_number', $jobNumber)->count_all_results($collectorTbl);
        $jobExitInWorker = $this->db->where('job_number', $jobNumber)->count_all_results($workerTbl);

        if($jobExitInCollector > 0 || $jobExitInWorker > 0){
            
            $collectorStatus = $this->db->where('job_number', $jobNumber)->where('status', 'started')->count_all_results($collectorTbl);
            $workerStatus = $this->db->where('job_number', $jobNumber)->where('status', 'started')->count_all_results($workerTbl);
            
            if($collectorStatus > 0 && $workerStatus > 0){
                return 'active';
            }
            else if($collectorStatus > 0 || $workerStatus > 0){
                return 'inprogress';
            }
            else{
                return 'finish';
            }
        }
        else{
            return 'not started';
        }
        
    }




    function sync_offline_worker_picking2($picking_workers)
    {
        $response = array();
        if (empty($picking_workers) || $picking_workers == [] || $picking_workers == null) {
            return 0;
        }

        foreach ($picking_workers as $val) {

            $key = $this->db->select('key')->from('picking_details_worker')->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id, 'key' => $val->key])->order_by('id','desc')->get()->row();

            if ($key->key != $val->key || empty($key)) {

                $res = $this->db->select('*')->from('picking_details_worker')->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id])->where('status !=', 'finished')->order_by('id','desc')->get()->row();

                if (count($res) > 0) {//update record for existing worker on job    
                    $id = $res->id;
                    $buckets_picked = $res->buckets_picked + $val->extra_buckets;//buckets_picked added in offline
                    $duplicates = $res->duplicates + $val->duplicates;//duplicates added in offline
                    $overrides = $res->overrides + $val->overrides;//overrides added in offline
                    
                    $data = array(
                        'buckets_picked' => $buckets_picked,
                        'duplicates' => $duplicates,
                        'overrides' => $overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'pick_time_per_bucket' => $val->pick_time_per_bucket,
                        'avg_buckets_per_hour' => $val->avg_buckets_per_hour,
                        'pay_per_bucket' => $val->pay_per_bucket,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
     
                    
                    $this->db->where('id', $id);
                    $sync_worker = $this->db->update('picking_details_worker', $data);

                    if ($sync_worker == 1) {
                        array_push($response, array('status' => 1, 'data' => $val));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $val));
                    }

                } else {//insert new record for new collector on job                    
                    $data = array(
                        'buckets_picked' => $val->buckets_picked,
                        'duplicates' => $val->duplicates,
                        'overrides' => $val->overrides,
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'pick_time_per_bucket' => $val->pick_time_per_bucket,
                        'avg_buckets_per_hour' => $val->avg_buckets_per_hour,
                        'pay_per_bucket' => $val->pay_per_bucket,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
                    $this->db->insert('picking_details_worker', $data);
                    $insert_id = $this->db->insert_id();

                    if ($insert_id != 0) {
                        array_push($response, array('status' => 1, 'data' => $val));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $val));
                    }
                }
            }
        }

        
        if (!empty($response)) {
            return $response;
        }
        else{
            $backData = array();
            foreach ($picking_workers as $key => $value) {
                $dat = array('status' => 0, 'data' => $value);
                array_push($backData, $dat);
            }
        }

        return $backData;
    }




    //
    //------------ Sync offline data of row_details_worker ------------
    //
    function sync_offline_worker_row2($row_workers)
    {
        // echo "<pre>";
        // print_r($row_workers);
        // exit;
        $allData = [];
        $response = array();
        foreach ($row_workers as $val) {
            $key = $this->db->select('key')->from('row_details_worker')->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id, 'key' => $val->key])->order_by('id','desc')->get()->row();
            if ($key->key != $val->key || empty($key)){

                $res = $this->db->select('*')->from('row_details_worker')->where(['job_number' => $val->job_number, 'worker_id' => $val->worker_id])->where('status !=', 'finished')->order_by('id','desc')->get()->row();

                if (count($res) > 0) {//update record for existing worker on job  
                    $id = $res->id;

                    $units_picked = $res->units_picked + $val->extra_units;//buckets_picked added in offline
                    $duplicates = $res->duplicates + $val->duplicates;//duplicates added in offline
                    $overrides = $res->overrides + $val->overrides;//overrides added in offline

                    $data = array(
                        'units_picked' => $units_picked,
                        'duplicates' => $duplicates,
                        'overrides' => $overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'work_time_per_unit' => $val->work_time_per_unit,
                        'avg_units_per_hour' => $val->avg_units_per_hour,
                        'pay_per_unit' => $val->pay_per_unit,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
                    $this->db->where('id', $id);
                    $sync_worker = $this->db->update('row_details_worker', $data);

                    if ($sync_worker == 1) {
                        array_push($response, array('status' => 1, 'data' => $val));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $val));
                    }

                } else {//insert new record for new worker on job                   
                    $data = array(
                        'job_number' => $val->job_number,
                        'worker_id' => $val->worker_id,
                        'units_picked' => $val->units_picked,
                        'duplicates' => $val->duplicates,
                        'overrides' => $val->overrides,
                        'start_time' => $val->start_time,
                        'last_scan' => $val->last_scan,
                        'time_worked' => $val->time_worked,
                        'finish_time' => $val->finish_time,
                        'pause_time' => $val->pause_time,
                        'reset_time' => $val->reset_time,
                        'work_time_per_unit' => $val->work_time_per_unit,
                        'avg_units_per_hour' => $val->avg_units_per_hour,
                        'pay_per_unit' => $val->pay_per_unit,
                        'rate_hour' => $val->rate_hour,
                        'time_not_worked' => $val->time_not_worked,
                        'status' => $val->status,
                        'key' => $val->key
                    );
                    $this->db->insert('row_details_worker', $data);
                    $insert_id = $this->db->insert_id();

                    if ($insert_id != 0) {
                        array_push($response, array('status' => 1, 'data' => $val));
                    } else {
                        array_push($response, array('status' => 0, 'data' => $val));
                    }
                }
            }
        }


        if (!empty($response)) {
            return $response;
        }
        else{
            $backData = array();
            foreach ($row_workers as $key => $value) {
                $dat = array('status' => 0, 'data' => $value);
                array_push($backData, $dat);
            }
        }

        return $backData;
    }
}

