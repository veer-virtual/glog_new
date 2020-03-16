<?php
error_reporting(0);
define('API_ACCESS_KEY', 'AIzaSyD_GrGFF1mOlmVSFVRYIu0pb0zFhoCE46Q');

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Transports_model extends CI_Model
{

    function __construct()
    {
        parent::__construct();
        //$this->load->library('database');
    }


	   //check NFC_Tag_ID
    function check_tag($nfc_tag)
    {
        $this->db->select('*');
        $this->db->from('driver');
        $this->db->where('nfc_id', $nfc_tag);
        $query = $this->db->get();
        if (count($query->result()) > 0) {

            return $query->result();
        } else {
            return false;
        }
    }	  



	//get jobs data for driver
    function get_driver_job_list($driver_tag)
    {


        $this->db->select(
            '
            transport_jobs.id,
            transport_jobs.job_number,
            transport_jobs.pick_up_date,
            transport_jobs.pick_up_time,
            transport_jobs.pick_up,
            transport_jobs.delivery_date,
            transport_jobs.delivery_time,
            transport_jobs.delivery,
            transport_jobs.pick_up_address_1,
            transport_jobs.pick_up_address_2,
            transport_jobs.pick_up_city,
            transport_jobs.job_status,
            '
        );
        $this->db->from('transport_jobs');
        $this->db->join('driver', 'driver.id = transport_jobs.driver');
        // $this->db->join('vehicle', 'vehicle.id = transport_jobs.vehicle');
        // $this->db->join('client', 'client.id = transport_jobs.client');
        // $this->db->join('supplier', 'supplier.id = transport_jobs.supplier');
        $this->db->where('driver.nfc_id', $driver_tag);
        $this->db->where('transport_jobs.job_status', 'allocated');
        $this->db->or_where('transport_jobs.job_status', 'started');
        $this->db->or_where('transport_jobs.job_status', 'unfinished');
        $query = $this->db->get();
        if (count($query->result()) > 0) {

            foreach ($query->result() as $val) {
                $this->db->select('*');
                $this->db->from('locations');
                $this->db->where_in('id', array($val->pick_up, $val->delivery));
                $loc = $this->db->get();

                if (count($loc->result()) > 0) {
                    foreach ($loc->result() as $location) {

                        if ($location->id == $val->pick_up) {

                            $val->pick_up = $location->site_name;

                        } elseif ($location->id == $val->delivery) {

                            $val->delivery = $location->site_name;
                        }
                    }
                } else {
                    $val->pick_up = '';
                    $val->delivery = '';
                }
                $val->pick_up_date = date('d-m-Y', strtotime($val->pick_up_date));
                $val->pick_up_time = date('H:i', strtotime($val->pick_up_time));

                $val->delivery_date = date('d-m-Y', strtotime($val->delivery_date));
                $val->delivery_time = date('H:i', strtotime($val->delivery_time));
            }
            return ($query->result());
        } else {
            return false;
        }
    }
	//get jobs data for driver
    function get_driver_job($driver_tag)
    {
    	
        $driver = $this->db->select('id,driver_name')->from('driver')->where('nfc_id', $driver_tag)->get()->row();

        if($driver){
            $driverId = $driver->id;
	        $sql = "SELECT *, pick_up_date AS startDate, pick_up_time AS startTime, id, pick_up as address, 'Pickup' AS jobType, pick_up_notes AS notes, pick_up_instructions AS instructions, pickup_status as jobStatus
	         FROM  `transport_jobs` 
	         WHERE FIND_IN_SET($driverId, driver) AND job_status!='deleted' AND job_status!='closed' AND job_status!='archived' AND job_status!='completed'
	         UNION ALL SELECT *, delivery_date AS startDate, delivery_time AS startTime, id, delivery as address, 'Delivery' AS jobType, delivery_notes AS notes, delivery_instructions AS instructions, delivery_status as jobStatus
	         FROM transport_jobs
	         WHERE FIND_IN_SET($driverId, driver) AND job_status!='deleted' AND job_status!='closed' AND job_status!='archived' AND job_status!='completed'
	         ORDER BY startDate, startTime ASC";
	        
	        $query = $this->db->query($sql);
	        // echo $this->db->last_query();exit;
	        $newArray = array();
	        $finalArray = array();
	        if (count($query->result()) > 0) {
	            foreach ($query->result() as $val) {
	            	$loc = $this->db->select('*')->from('locations')->where('id', $val->address)->get();
	            	if(!empty($loc->row())){
	            		$newArray['id'] = $val->id;
		                $newArray['job_number'] = $val->job_number;
		                $newArray['date'] = date('d-m-Y', strtotime($val->startDate));
		                $newArray['time'] = date('H:i', strtotime($val->startTime));
		                $newArray['job_type'] = $val->jobType;
		                $newArray['job_status'] = $val->jobStatus;
		                $newArray['driver_name'] = $driver->driver_name;

		                $newArray['type'] = $val->job_type;
		                $newArray['unit_type'] = $val->unit_type;
		                $newArray['unit_price'] = $val->pricing;
		                $newArray['no_units'] = $val->transport_units;
		                $newArray['total_trasport_price'] = $val->transport_total_price;
		                $newArray['item'] = $val->item;
		                $newArray['no_units_item'] = $val->no_units;
		                $newArray['time_unit'] = $val->time_unit;
		                $newArray['time_item'] = $val->time;
		                $newArray['price_per_unit'] = $val->price_per_unit;
		                $newArray['total_price'] = $val->total_price;
		                $newArray['notes'] = $val->notes;
		                $newArray['instructions'] = $val->instructions;

		                $newArray['address'] = $loc->row() ? array($loc->row()) : array();
		                array_push($finalArray,$newArray);
	            	}
	            }
	        }

	        return $finalArray;
	    }
	    else{
	    	return 2; // invalid driver
	    }
    }


      //Add Driver notes for transport job
    function driver_note($job_number, $driver_nfc_id, $notes)
    {
        $id = $this->driverId($driver_nfc_id);

        $prevNote = $this->db->select('id, comments')
                ->from('transport_jobs')
                ->where('job_number', $job_number)
                ->where("FIND_IN_SET('".$id."', driver)")
                ->get()
                ->row();

		$FinalNotes =  $prevNote->comments ? $prevNote->comments.', ' : '';
		$FinalNotes .= $notes;

        $this->db->set('comments', $FinalNotes);
        $this->db->where('id', $prevNote->id);
        if ($this->db->update('transport_jobs')) {
            return 1; //Notes added

        } else {
            return false;
        }
    }
				
		
	 //Add Driver documents for transport job
    function driver_document($job_number, $driver_nfc_id, $docs)
    {
        
        $id = $this->driverId($driver_nfc_id);
        $prevAttach = $this->db->select('id, attachments')
                ->from('transport_jobs')
                ->where('job_number', $job_number)
                ->where("FIND_IN_SET('".$id."', driver)")
                ->get()
                ->row();

        $attachments =  $prevAttach->attachments ? $prevAttach->attachments.',' : '';
        
        $attachments .= implode(',', $docs);

        
        $this->db->set('transport_jobs.attachments', $attachments);
        $this->db->where('transport_jobs.job_number', $job_number);
        $this->db->where("FIND_IN_SET('".$id."', transport_jobs.driver)");
        if ($this->db->update('transport_jobs')) {
            return 1; //Documents added

        } else {
            return false;
        }
    }
		
				
      //Change status of driver transport job
    function job_status($job_number, $driver_nfc_id, $type, $time, $lat = "", $long = "", $jobStatus)
    {
        // change on 12-12-2018 by v
        $sql = "SELECT t.*,group_concat(d.driver_name) as driverName, d.id as driver_id FROM transport_jobs t, driver d WHERE FIND_IN_SET(d.id, t.driver) and d.nfc_id='$driver_nfc_id' and t.job_number='$job_number'";
        $query = $this->db->query($sql);
        $data = $query->row();


        if($jobStatus == 'Pickup'){
        	$job_status = $data->pickup_status;
        	$job_status_key = 'pickup_status';
        }
        elseif($jobStatus == 'Delivery'){
        	$job_status = $data->delivery_status;
        	$job_status_key = 'delivery_status';
        }
        else{
        	return 10;
        }

 // print_r($data);exit();
        if (!empty($data->id)) {

            $job_id = $data->id;
            $driver_id = $data->driver_id;

            if ($type == 'start') {
                   //if job is started                   
                if ($job_status == 'started') {

                    return 1; //Job is already started

                } elseif ($job_status == 'paused') {

                    return 2; //Job is paused

                } elseif ($job_status == 'allocated' || $job_status == 'unfinished' || $job_status == 'changed') {
                    // insert into trasport job track
                    $trackData = array('transport_job_id' => $job_id,
                                        'driver_id' => $driver_id,
                                        'start_lat' => $lat,
                                        'start_long' => $long,
                                        'start_time' => $time
                                    );
                    $this->db->insert('transport_jobs_track', $trackData);
                    
                    if($job_status != 'changed'){
                        $this->db->set('start_time', $time);
                    }
                    $this->db->set($job_status_key, 'started');
                    $this->db->where('id', $job_id);
                    if ($this->db->update('transport_jobs')) {
                        return 3; //Job started                           
                    } else {
                        return false;
                    }
                }
            } elseif ($type == 'finish') {
                //if job is started
                if ($job_status == 'allocated') {

                    return 4; //Job is not started, it is allocated

                } elseif ($job_status == 'paused') {

                    return 2; //Job is paused

                } elseif ($job_status == 'finished') {

                    return 8; //Job is already finished

                } elseif ($job_status == 'started' || $job_status == 'changed') {

                    // upate transport track
                    $this->_update_transport_track($job_id, $driver_id, $lat, $long, $time);

                    $non_worked = $data->time_not_worked; //Non-worked time

                    $to_time = strtotime($time);
                    $from_time = strtotime($data->start_time);
                    $worked = round(abs($to_time - $from_time) / 3600, 2);  //Working Time in hours     
                    $worked = $query->row()->time_worked + $worked;
                    $time_worked = $worked - $non_worked;

                    
                    $this->db->set($job_status_key, 'finished');
                    $this->db->set('finish_time', $time);
                    $this->db->set('time_worked', $time_worked);
                    $this->db->where('id', $job_id);
                    if ($this->db->update('transport_jobs')) {
                        return 5; //Job finished
                    } else {
                        return false;
                    }
                }

            } elseif ($type == 'change') {
                if ($job_status == 'changed') {
                
                    return 7; //driver is already changed
                
                } elseif ($job_status == 'finished') {
                
                    return 5; //job is already finished
                
                } elseif ($job_status == 'allocated') {
                
                    return 4; //Job is not started, it is allocated
                
                } elseif ($job_status == 'paused') {
                
                    return 2; //Job is paused
                
                } else{
                    // upate transport track
                    $this->_update_transport_track($job_id, $driver_id, $lat, $long, $time);
                    
                    $this->db->set($job_status_key, 'changed');
                    $this->db->set('driver_change_date', $time);
                    $this->db->where('id', $job_id);
                    if ($this->db->update('transport_jobs')) {
                        return 6; //driver changed
                    } else {
                        return false;
                    }
                }
            }
        }
        else{
            return 9; //Driver not found for this job
        }
    }


    protected function _update_transport_track($job_id, $driver_id, $lat, $long, $time){
        
        // get job track id
        $this->db->select('id, start_lat, start_long');
        $this->db->from('transport_jobs_track');
        $this->db->where('transport_job_id', $job_id);
        $this->db->where('driver_id', $driver_id);
        $this->db->order_by('id', 'desc');
        $this->db->limit(1);
        $query = $this->db->get();
        $Row = $query->row();
        $track_id = $Row->id;
        $track_id = $Row->id;
        $start_lat = $Row->start_lat;
        $start_long = $Row->start_long;

        //get distance covered
       $distanceCovered = $this->distanceCovered($start_lat, $start_long, $lat, $long);

        // update trasport job track
        $trackDataUpdate = array('finish_lat' => $lat,
                                'finish_long' => $long,
                                'distance_covered' => $distanceCovered,
                                'finish_time' => $time,
                            );
        $this->db->where('id', $track_id);
        $this->db->update('transport_jobs_track', $trackDataUpdate);
        return;
    }


    /* ----------------------------------------------------------
    | Distance Covered Update in Transport job table
    |------------------------------------------------------------
    | Here Distance covered by a driver will be updated and the
    | distance will be count on the basis of start and finish
    | lat long. [ Distance store will be in meter ]
    */

    public function distanceCovered($start_lat, $start_long, $lat, $long)
    {
        $distance = latLongDistance($start_lat, $start_long, $lat, $long);
        return round($distance/1000, 2);
    }
	   

       
	//pause time transport job
    function pause_job($job_number, $driver_nfc_id, $type, $time, $jobStatus)
    {
    	// change on 12-12-2018 by v
        $sql = "SELECT t.*,group_concat(d.driver_name) as driverName, d.id as driver_id FROM transport_jobs t, driver d WHERE FIND_IN_SET(d.id, t.driver) and d.nfc_id='$driver_nfc_id' and t.job_number='$job_number'";
        $query = $this->db->query($sql);
        $data = $query->row();

        // $this->db->select('transport_jobs.*');
        // $this->db->from('transport_jobs');
        // $this->db->join('driver', 'driver.id = transport_jobs.driver');
        // $this->db->where('transport_jobs.job_number', $job_number);
        // $this->db->where('driver.nfc_id', $driver_nfc_id);
        // $query = $this->db->get();

        if($jobStatus == 'Pickup'){
        	$job_status = $data->pickup_status;
        	$job_status_key = 'pickup_status';
        }
        elseif($jobStatus == 'Delivery'){
        	$job_status = $data->delivery_status;
        	$job_status_key = 'delivery_status';
        }
        else{
        	return 5;
        }

        if (!empty($data)) {

            $job_id = $query->row()->id;

            if ($type == 'pause') {
				   //if job is started
                if ($job_status == 'started') {

                    $this->db->set($job_status_key, 'paused');
                    $this->db->set('pause_time', $time);
                    $this->db->where('id', $job_id);
                    if ($this->db->update('transport_jobs')) {
                        return 1; //Job paused
                    } else {
                        return false;
                    }

                } elseif ($job_status == 'paused') {

                    return 2; //Job is already paused
                }

            } elseif ($type == 'resume') {	
				   //if job is started
                if ($job_status == 'paused') {

                    $to_time = strtotime($time);
                    $from_time = strtotime($query->row()->pause_time);
                    $non_worked = round(abs($to_time - $from_time) / 3600, 2);//Non-working Time in hours		
                    $non_worked = $query->row()->time_not_worked + $non_worked;


                    $this->db->set($job_status_key, 'started');
                    $this->db->set('reset_time', $time);
                    $this->db->set('time_not_worked', $non_worked);
                    $this->db->where('id', $job_id);
                    if ($this->db->update('transport_jobs')) {
                        return 3; //Job resumed	
                    } else {
                        return false;
                    }

                } elseif ($job_status == 'started') {

                    return 4; //Job is already started

                }

            }
        }
    }
    // get driver id

    public function driverId($nfc_id)
    {
        return  $this->db->select('id')
                ->from('driver')
                ->where('nfc_id', $nfc_id)
                ->get()
                ->row()->id;
    }

}

?>