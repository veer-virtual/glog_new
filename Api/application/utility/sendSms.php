<?php
    # Define the username, password and XML API id
//define("USERNAME", "kumar110387");
//define("PASSWORD", "ZGPPfOADNPfWcK");
//define("API_ID", "3484626");
define("BASE_URL", "http://api.clickatell.com");
//define("USERNAME", "jgx5favo");
//define("PASSWORD", "DNLIeIdNcQKEAd");
//define("API_ID", "3484701");
//define("BASE_URL", "http://api.clickatell.com");
  
    class sms{
        
        
    public $username, $password, $apiId ;

    public function setUsername($username) {
        $this->username = $username;
    }
    public function setPassword($passowrd) {
        $this->password = $passowrd;
    }
    public function setApiId($apiId) {
        $this->apiId = $apiId;
    }
 

       // var $username =USERNAME ;
	//var $password =PASSWORD;
	//var $api_id = API_ID;
	var $baseurl = BASE_URL;



            function sendsms($phoneNumber , $txt){



                $text = urlencode($txt);
                $to = $phoneNumber;

                // auth call
               $url = $this->baseurl."/http/auth?user=$this->username&password=$this->password&api_id=".$this->apiId;

                // do auth call
                $ret = file($url);

                // explode our response. return string is on first line of the data returned
                $sess = explode(":",$ret[0]);

                if ($sess[0] == "OK") {

                    $sess_id = trim($sess[1]); // remove any whitespace
                    $url = $this->baseurl."/http/sendmsg?user=".$this->username."&password=".$this->password."&api_id=".$this->apiId."&mo=1&from=19529554183&to=$to&text=$text";
               
                    // do sendmsg call
                    $ret = file($url);
                    $send = explode(":",$ret[0]);

                    if ($send[0] == "ID") {
                      //  echo "success  message ID: ". $send[1];
                        return 1;
                    } else {
                        //echo "send message failed";
                        return 0;
                    }
                } else {
                    //echo "Authentication failure: ". $ret[0];
                    return 0;
                }




            }
    }// END OF CLASS

  //$s = new sms();
   //$s->sendsms('919717132393', 'hello John');
// JOhn 13109801481
?>
