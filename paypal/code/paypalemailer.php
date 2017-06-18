<?php

require('PaypalIPN.php');

$debug = false;
$logemail = "noda.yoshikazu@gmail.com";


//
function strposX($haystack, $needle, $number){
    if($number == '1'){
        return strpos($haystack, $needle);
    }
    elseif($number > '1'){
        return strpos($haystack, $needle, strposX($haystack, $needle, $number - 1) + strlen($needle));
    }
    else {
        return error_log('Error: Value for parameter $number is out of range');
    }
}

/* creates a compressed zip file */
function create_zip($files , $destination ) {
	//if the zip file already exists and overwrite is false, return false
	//if(file_exists($destination) && !$overwrite) { return false; }
	//vars
	$overwrite =true;
	$valid_files = array();
	//if files were passed in...
	if (is_array($files)) {
		//cycle through each file
		foreach($files as $file) {
			//make sure the file exists
			error_log('Looking for file ' . $file);
			if(file_exists($file)) {
				$valid_files[] = $file;
				error_log('File Exists' . $file);
			}
		}
	}
	//if we have good files...
	if (count($valid_files)) {
		//create the archive
		$zip = new ZipArchive();
		if($zip->open($destination, ZIPARCHIVE::CREATE) !== true) {
			error_log('Failed to create '. $destination);
			error_log('Zip Open false');
			return false;
		}
		//add the files
		for($i = 0; $i < sizeof($valid_files); $i++ ) {
			$path_parts = pathinfo( $valid_files[$i] );
			$zip->addFile( $valid_files[$i], $path_parts['basename'] );
			error_log('Adding file ' . $path_parts['basename'] );
			error_log('The zip archive contains '. $zip->numFiles. ' files with a status of ' . $zip->status);

		}
		//debug
		//echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;
		//close the zip -- done!
		$zip->close();
		error_log('Closing zip');
		//check to make sure the file exists
		return file_exists($destination);
	}
	else {
        error_log('There were no valid files');
        return false;
    }
}


// Set this to true to use the sandbox endpoint during testing:
$enable_sandbox = true;

// Use this to specify all of the email addresses that you have attached to paypal:
$my_email_addresses = array("cloudcoin@Protonmail.com", "sean.worthington@gmail.com", "sean@worthington.net");
if ($enable_sandbox)
    array_push($my_email_addresses, "sean-facilitator@worthington.net");
    

// Set this to true to send a confirmation email:
$send_confirmation_email = true;
$confirmation_email_address = $_POST["first_name"]." ". $_POST["last_name"] ."<".$_POST["payer_email"] .">";
$from_email_address = "Cloud Coin <CoinMaster@CloudCoinConsortium.com>";

$item_name  = strtolower($_POST["item_name"]);
$item_number = $_POST["item_number"];//250/100/25/5/1
$quantity  = $_POST["quantity"];

if ($debug)
    print_r($_POST);

// Set this to true to save a log file:
$save_log_file = true;
$log_file_dir = __DIR__ . "/logs";

// Here is some information on how to configure sendmail:
// http://php.net/manual/en/function.mail.php#118210

//use PaypalIPN;  not needed
$ipn = new PaypalIPN();
if ($enable_sandbox) {
    $ipn->useSandbox();
    echo "Using sandbox";
}

$verified = $ipn->verifyIPN();

$data_text = "";
foreach ($_POST as $key => $value) {
    $data_text .= $key . " = " . $value . "\r\n";
}
$test_text = "";
if ($_POST["test_ipn"] == 1) {
    $test_text = "Test ";
}

// Check the receiver email to see if it matches your list of paypal email addresses
$receiver_email_found = false;

foreach ($my_email_addresses as $a) {
    if (strtolower($_POST["receiver_email"]) == strtolower($a)) {
        $receiver_email_found = true;
        break;
    }
}

if ($debug && $receiver_email_found)
    echo "RECEIVER_EMAIL_FOUND!";

date_default_timezone_set("America/Los_Angeles");
list($year, $month, $day, $hour, $minute, $second, $timezone) = explode(":", date("Y:m:d:H:i:s:T"));
$date = $year . "-" . $month . "-" . $day;
$timestamp = $date . " " . $hour . ":" . $minute . ":" . $second . " " . $timezone;
$dated_log_file_dir = $log_file_dir . "/" . $year . "/" . $month;

$paypal_ipn_status = "VERIFICATION FAILED";
$Wanted1s = 0;
$Wanted5s = 0;
$Wanted25s = 0;
$Wanted100s = 0;
$Wanted250s = $_POST["quantity"];
$links = "";
$totalCoins = 0;

/* Sandbox _POST valus
{"mc_gross": "2.75","protection_eligibility":"Eligible","address_status":"confirmed","payer_id":"S99XW6D4TL3PC","address_street":"Nishi 4-chome, Kita 55-jo, Kita-ku","payment_date":"17:12:17 Jun 17, 2017 PDT","payment_status":"Completed","charset":"windows-1252","address_zip":"150-0002","first_name":"Sean","mc_fee":"0.41","address_country_code":"JP","address_name":"Worthington Sean","notify_version":"3.8","custom":"","payer_status":"unverified","business":"sean-facilitator@worthington.net","address_country":"Japan","address_city":"Shibuya-ku","quantity":"1","verify_sign":"AjNG4H.j9XP5JDmRSY54-UnKMFtBAqmXve0WrQpFDobbZlJJIOqQHNkr","payer_email":"noda.yoshikazu@gmail.com","txn_id":"6X3468117U763282L","payment_type":"instant","last_name":"Worthington","address_state":"Tokyo","receiver_email":"sean-facilitator@worthington.net","payment_fee":"0.41","receiver_id":"RTBHRUTXN6JNS","txn_type":"web_accept","item_name":"250 CloudCoin Notes in jpegs","mc_currency":"USD","item_number":"","residence_country":"JP","test_ipn":"1","transaction_subject":"","payment_gross":"2.75","ipn_track_id":"df94960cdff8c"}
*/

$where = "** 1";

if ($verified) {
    // Verified
    $where .= "** 2";
    $paypal_ipn_status = "RECEIVER EMAIL MISMATCH";

    if ($receiver_email_found) {
        $paypal_ipn_status = "Completed Successfully";
        $Wanted250s = $quantity;
		//error_log('PayPal: 1s ' . $Wanted1s." 5s ". $Wanted5s .", 25s ". $Wanted25s .", 100s ". $Wanted100s .", 250S ". $Wanted250s );
        //Get Folder name from payer_id and payment_date
        $folderName = $year.".".$month.".".$day.".".$hour.".".$minute.".".$second.".".$_POST["payer_id"];
        // Ok upto here

        if (!@mkdir("../orders/" . $folderName, 0777)) {
            $error = error_get_last();
            $where .= $error['message'];
            //exit(1);
        }
        if (strpos($item_name, 'jp') !== false) { 
            $format = "jpgs";
            error_log( "Format is jpge: " . $item_name);
        } 
        else {
            error_log( "Format is stack: " . $item_name);
            $format = "stacks";
        } //end if jpg or stack


        $Jpeglinks = array();//Fill this with the hyperlinks that will be sent to the user
        $urls = array();
        $fileContents = array();
        $where .= "** 3";
        $totalCoins = intval($Wanted1s) + intval($Wanted5s)*5 + intval($Wanted25s)*25 + intval($Wanted100s)*100 + intval($Wanted250s)*250 ;	

		//If they want 250
        // For Live only
        if ( intval( $Wanted250s  ) > 0) {
            $Names250s = scandir("../bank/$format/250s", 1);
            for ($i = 0; $i < intval( $Wanted250s ); $i++) {
                //move file to order folder
                rename("../bank/$format/250s/" . $Names250s[$i], "../orders/" . $folderName . "/" . $Names250s[$i] );
				if( $format == "jpgs"){
					//error_log("pushing ../orders/" . $folderName . "/" . $Names250s[$i]);
					//array_push($fileContents, chunk_split(base64_encode(file_get_contents("../orders/" . $folderName . "/" . $Names250s[$i]))) );
				}//end if they are jpbs
				array_push($Jpeglinks, "https://CloudCoinConsortium.com/paypal/orders/" . $folderName . "/" . $Names250s[$i] ."\n");
				
            } //end for each 250 wanted
        } //end if they want 250

        //Get all file names in the orders folder
        $allFiles  = scandir("../orders/" . $folderName, 1);
        $fileNames = array_diff($allFiles , array('.', '..'));
        if ($debug) echo("filenames" . json_encode($fileNames));

        $json = "";
        $linkStrings ="";
        $where .= "** 4";

        for ($j = 0; $j < count($fileNames); $j++) { //Minus 2 because this scandir includes .. and .  
            if ($format == "jpgs") {
                // $linkStrings .="<a download='". $Jpeglinks[$j]. "'><img src='". $Jpeglinks[$j]. "'
                //   alt='CloudCoin' style='display:block;margin-left:auto;margin-right:auto;box-shadow:10px 10px 5px #888888; width:400px;'></a><br>";	
                $fileNames[$j] = "../orders/" . $folderName . DIRECTORY_SEPARATOR . $fileNames[$j];
            }
            else {
                if($j==0){
                    $json .= "{
	                                     \"cloudcoin\": ["; }
                else {
                    $json .= ",";
                }
                //1.Open file and add string
                $file = file_get_contents( "../orders/" . $folderName . DIRECTORY_SEPARATOR . $fileNames[$j] , true);
                $pos1 = strposX($file, "{", 2);
                $pos2 = strpos( $file, "}") + 1;
                $oneCoin = substr( $file, $pos1, $pos2-$pos1);
                //Strip out any owner comments
                $pos1 = strposX($oneCoin, "[", 2);
                $pos2 = strposX($oneCoin, "]", 2);
                $begining = substr($oneCoin, 0,($pos1+1));
                $end = substr($oneCoin, ($pos2));
                $json .= $begining.$end;
		   
                if($j== (count($fileNames) - 1)) {
                    $json .= "]
			
}" ;}
            }//end if format jpg or stack
        } //end for each jpg to be linked to page
        
        $where .= "** 5";

        if ($format == "jpgs"){ 
            //  $links = $linkStrings;
            //  for($k=0;$k< sizeof($linkStrings); $k++)
            //{
            //	$links.="<a href='".$Jpeglinks[$k]."'>https://CloudCoinConsortium.com/paypal/orders/" . $Jpeglinks[$k]."</a>";
            //}
            $files_to_zip = array();
            $filename = $totalCoins.'cc.zip';   // NOTE: please make sure this is right! Yoshi.
            array_push($files_to_zip,  "../orders/" . $folderName . DIRECTORY_SEPARATOR . $filename);
            $result = create_zip($fileNames, "../orders/" . $folderName . DIRECTORY_SEPARATOR .  $totalCoins.'cc.zip');
            if ($debug && !$result)
                echo "JPEG CreateZip did not work " . "../orders/" . $folderName . DIRECTORY_SEPARATOR .  $totalCoins.'cc.zip';
            $zipLink = "https://CloudCoinConsortium.com/paypal/orders/" .  $folderName. DIRECTORY_SEPARATOR .  $filename;
            $links = "<a href='".$zipLink."' download>". $zipLink."</a>";
            //$message2 = "You need to download the CloudCoins above. You may need to right-click them and Save Image As.";
        }
        else {
            // Stack
            $rand = rand();
            $filename = "$totalCoins.cloudcoin.$rand.stack";
            $myfile = fopen( "../orders/" .  $folderName. DIRECTORY_SEPARATOR . $filename , "w") or error_log( "Unable to open file!");
            fwrite($myfile, $json);
            fclose($myfile);
            //error_log("pushing ..../orders/" . $folderName. DIRECTORY_SEPARATOR . $filename );
            //array_push($fileContents, chunk_split(base64_encode(file_get_contents("../orders/" . $folderName. DIRECTORY_SEPARATOR . $filename ))) );+
            //    $data_text .= $folderName. DIRECTORY_SEPARATOR . $filename;
            //$links ="<a download='https://CloudCoinConsortium.com/paypal/orders/" . $folderName. DIRECTORY_SEPARATOR . $filename . "' >
            // <img src='https://CloudCoinConsortium.com/img/stack.png'  alt='CloudCoin' style='display:block;margin-left:auto;margin-right:auto; width:400px;'></a><br>
	            //<a href='https://CloudCoinConsortium.com/paypal/orders/" . $folderName. DIRECTORY_SEPARATOR . $filename ."'>
            //https://CloudCoinConsortium.com/paypal/orders/" . $folderName. DIRECTORY_SEPARATOR . $filename ."</a>";
            $files_to_zip = array();
            array_push($files_to_zip,  "../orders/" . $folderName . DIRECTORY_SEPARATOR . $filename);
            $result = create_zip($files_to_zip, "../orders/" . $folderName . DIRECTORY_SEPARATOR .  $totalCoins.'cc.zip');
            if ($debug && !$result)
                echo "JPEG CreateZip did not work";
            $zipLink = "https://CloudCoinConsortium.com/paypal/orders/" .  $folderName. DIRECTORY_SEPARATOR .  $totalCoins.'cc.zip';
            $links = "<a href='".$zipLink."' download>". $zipLink."</a>";
            //$message2 = "You need to download the stack above above. You may need to right-click the link and Save As.";
        }//end if format jpg or stack
        if ($result && $debug)
            echo "zipLink=" . $zipLink . " links=" . $links;
        if ($result) {
            error_log("Zip worked");
        }else{
            error_log("Zip did not work");
        }//end if results is true
    }
    $where .= "** 6";
}


if ($verified) {
    //a random hash will be necessary to send mixed content
    $separator = md5(time());
    // carriage return type (RFC)
    $eol = "\r\n";
}

if ($enable_sandbox) {
    if ($_POST["test_ipn"] != 1) {
        $paypal_ipn_status = "RECEIVED FROM LIVE WHILE SANDBOXED";
    }
}
elseif ($_POST["test_ipn"] == 1) {
    $paypal_ipn_status = "RECEIVED FROM SANDBOX WHILE LIVE";
}

if ($save_log_file) {
    // Create log file directory
    if (!is_dir($dated_log_file_dir)) {
        if (!file_exists($dated_log_file_dir)) {
            mkdir($dated_log_file_dir, 0777, true);
            if (!is_dir($dated_log_file_dir)) {
                $save_log_file = false;
            }
        } else {
            $save_log_file = false;
        }
    }
    // Restrict web access to files in the log file directory
    $htaccess_body = "RewriteEngine On" . "\r\n" . "RewriteRule .* - [L,R=404]";
    if ($save_log_file && (!is_file($log_file_dir . "/.htaccess") || file_get_contents($log_file_dir . "/.htaccess") !== $htaccess_body)) {
        if (!is_dir($log_file_dir . "/.htaccess")) {
            file_put_contents($log_file_dir . "/.htaccess", $htaccess_body);
            if (!is_file($log_file_dir . "/.htaccess") || file_get_contents($log_file_dir . "/.htaccess") !== $htaccess_body) {
                $save_log_file = false;
            }
        } else {
            $save_log_file = false;
        }
    }
    if ($save_log_file) {
        // Save data to text file
        file_put_contents($dated_log_file_dir . "/" . $test_text . "paypal_ipn_" . $date . ".txt", "paypal_ipn_status = " . $paypal_ipn_status . "\r\n" . "paypal_ipn_date = " . $timestamp . "\r\n" . $data_text . "\r\n", FILE_APPEND);
    }
}

/*
 * If ok
 */
if ($verified && $send_confirmation_email) {

    $email_body = "
<!doctype html>
<html >
  <body style='background-color:#338FFF;font-family:Helvetica, Arial, sans-serif;'>
	<div style='width:50%; margin: auto; padding:10%; background-color: white'>
	 <img src='https://cloudcoinconsortium.com/img/cloudcointop.png' width='400' alt='CloudCoin logo'  style='margin-left:auto;margin-right:auto;'>
     <h2 style='text-align: center'>Your $totalCoins CloudCoins:</h2>
				
				" . $links . "
				
     <h3>Dear CloudCoin Owner,</h3>
	 <p>Thank you for your purchase. You will need to download the zip file above. Please unzip before you try to import." . /*$message2. */  " </p>
				
	 <p>In traditional monetary systems, the people who 'have' the money own it.</p>
     <p>With CloudCoins, the people that 'know' the money own it. CloudCoins use a new technology called RAIDA that does what Bitcoin's Blockchain does, only much better.</p>
     <p>You are one of the first people in history to use it!</p>
     <p>In order to own these coins you must pown (change all the internal authenticity numbers ) so only you have them.</p>

     <p>This requires software (soon we will have web pages) to contact the RAIDA. </p>
	For windows desktop use Foundation: <a href='https://cloudcoinconsortium.com/zip/CloudCoinFoundation.zip' download >CloudCoin Foundation</a><br>
	For Android use Pown Bank:  <a href='https://play.google.com/store/apps/details?id=co.cloudcoin.cc' download >CloudCoin Pown Bank</a><br>
        
     <p>Mac and iPhone software are coming soon.<br>n Let us know if you have any questions or concerns
         <a href='mailto:CloudCoin@Protonmail.com'>CloudCoin@Protonmail.com</a></p>
     <p>If you accidentally destroy or lose your Cloudcoins, they can be recovered in two years.
		Send your serial numbers and the month lost to <a href='mailto:CloudCoin@protonmail.com Subject: Lost Coin Report.'>CloudCoin@Protonmail.com</a></p>
     <p> Now you can become a member of the CloudCoin Consortium. <a href='http://CloudCoinConsortium.com/membership.html'>CloudCoin Consortium</a></p>

	Sincerely.<br>CoinMaster (No Reply)</p>
		 
	<p>PS. We recommend you that you get a free encrypted email account at Protonmail.com. Protonmail is the safest way to send and receive CloudCoins. <br></p>
    </div>
  </body>
</html>";

	$email_subject = "Your CloudCoins";
	// Send confirmation email
	$headers = "From: CoinMaster@CloudCoinConsortium.com\r\n";
    $headers .= "Reply-To: CloudCoin@Protonmail.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"" . $separator . "\"" . $eol;
    $headers .= "Content-Transfer-Encoding: 7bit" . $eol;
    $headers .= "This is a MIME encoded message." . $eol;

    // message
    $body = "--" . $separator . $eol;
    $body .= "Content-type:text/html;charset=UTF-8" . $eol;
    $body .= "Content-Transfer-Encoding: 8bit" . $eol;
    $body .= $email_body . $eol;

    // attachments
	/*
      for($i=0; $i< sizeof($fileContents);$i++){
		
      $body .= "--" . $separator . $eol;
      if($format == "jpgs"){
		
      $body .= "Content-Type: application/octet-stream; name=\"" . $fileNames[$i] . "\"" . $eol;
      $body .= "Content-Transfer-Encoding: base64" . $eol;
      $body .= "Content-Disposition: attachment" . $eol;
      $body .= $fileContents[$i] . $eol;
      error_log( "Adding to body " . $fileNames[$i] );
	
      }else{
		
      $body .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"" . $eol;
      $body .= "Content-Transfer-Encoding: base64" . $eol;
      $body .= "Content-Disposition: attachment" . $eol;
      $body .= $fileContents[$i] . $eol;
      error_log( "Adding to body " . $filename );
	
      }
      }//end for each file
    */
    if ($debug)
        echo $body;
	mail($confirmation_email_address, $email_subject, $body, $headers);

    // Reply with an empty 200 response to indicate to paypal the IPN was received correctly
}
//header("HTTP/1.1 200 OK");

?>
