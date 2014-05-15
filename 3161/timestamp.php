<?php
/**
 * timestamp.php - Creates Timestamp Requestfiles, processes the request at a Timestamp Authority (TSA) after RFC 3161
 *
 * bases on OpenSSL and RFC 3161: http://www.ietf.org/rfc/rfc3161.txt
 *
 * Dependencies: 
 *  needs openssl ts, which is availible in OpenSSL versions >= 0.99
 *
 * @version 0.1
 * @author Antonio Sejas
 * @package publicacioncertificada
*/

class Timestamp
{
    /**
     * Creates a Timestamp Requestfile from a hash
     *
     * @param string $hash: The hashed data (sha1)
     * @return string: path of the created timestamp-requestfile
     */
    public static function createRequestfile ($hash)
    {
        if (strlen($hash) !== 40)
            throw new Exception("Invalid Hash.");
            
        $outfilepath = self::createTempFile();
        $cmd = "openssl ts -query -digest ".escapeshellarg($hash)." -cert -out ".escapeshellarg($outfilepath);

        $retarray = array();
        exec($cmd." 2>&1", $retarray, $retcode);
        
        if ($retcode !== 0)
            throw new Exception("OpenSSL does not seem to be installed: ".implode(", ", $retarray));
        
        if (stripos($retarray[0], "openssl:Error") !== false)
            throw new Exception("There was an error with OpenSSL. Is version >= 0.99 installed?: ".implode(", ", $retarray));

        return $outfilepath;
    }

    /**
     * Signs a timestamp requestfile at a TSA using CURL
     *
     * @param string $requestfile_path: The path to the Timestamp Requestfile as created by createRequestfile
     * @param string $tsa_url: URL of a TSA such as http://zeitstempel.dfn.de
     * @return array of response_string with the unix-timetamp of the timestamp response and the base64-encoded response_string
     */
    public static function signRequestfile ($requestfile_path, $tsa_url)
    {
        if (!file_exists($requestfile_path))
            throw new Exception("The Requestfile was not found");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $tsa_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents($requestfile_path));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/timestamp-query'));
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)"); 
        $binary_response_string = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($status != 200 || !strlen($binary_response_string))
            throw new Exception("The request failed");
        
        $base64_response_string = base64_encode($binary_response_string);
        
        $response_time = self::getTimestampFromAnswer ($base64_response_string);
        
        return array("response_string" => $base64_response_string,
                     "response_time" => $response_time);
    }

    /**
     * Extracts the unix timestamp from the base64-encoded response string as returned by signRequestfile
     *
     * @param string $base64_response_string: Response string as returned by signRequestfile
     * @return int: unix timestamp
     */
    public static function getTimestampFromAnswer ($base64_response_string)
    {
        $binary_response_string = base64_decode($base64_response_string);

        $responsefile = self::createTempFile($binary_response_string);

        $cmd = "openssl ts -reply -in ".escapeshellarg($responsefile)." -text";
        
        $retarray = array();
        exec($cmd." 2>&1", $retarray, $retcode);
        
        if ($retcode !== 0)
            throw new Exception("The reply failed: ".implode(", ", $retarray));
        
        $matches = array();
        $response_time = 0;

        /*
         * Format of answer:
         * 
         * Foobar: some stuff
         * Time stamp: 21.08.2010 blabla GMT
         * Somestuff: Yayayayaya
         */
        foreach ($retarray as $retline)
        {
            if (preg_match("~^Time\sstamp\:\s(.*)~", $retline, $matches))
            {
                $response_time = strtotime($matches[1]);
                break;      
            }
        }

        if (!$response_time)
            throw new Exception("The Timestamp was not found"); 
            
        return $response_time;
    }

    /**
     *
     * @param string $hash: sha1 hash of the data which should be checked
     * @param string $base64_response_string: The response string as returned by signRequestfile
     * @param int $response_time: The response time, which should be checked
     * @param string $tsa_cert_file: The path to the TSAs certificate chain (e.g. https://pki.pca.dfn.de/global-services-ca/pub/cacert/chain.txt)
     * @return <type>
     */
    public static function validate ($hash, $base64_response_string, $response_time, $tsa_cert_file)
    {
        if (strlen($hash) !== 40)
            throw new Exception("Invalid Hash");
        
        $binary_response_string = base64_decode($base64_response_string);
        
        if (!strlen($binary_response_string))
            throw new Exception("There was no response-string");    
            
        if (!intval($response_time))
            throw new Exception("There is no valid response-time given");
        
        if (!file_exists($tsa_cert_file))
            throw new Exception("The TSA-Certificate could not be found");
        
        $responsefile = self::createTempFile($binary_response_string);

        $cmd = "openssl ts -verify -digest ".escapeshellarg($hash)." -in ".escapeshellarg($responsefile)." -CAfile ".escapeshellarg($tsa_cert_file);
        
        $retarray = array();
        exec($cmd." 2>&1", $retarray, $retcode);
        
        /*
         * just 2 "normal" cases: 
         *  1) Everything okay -> retcode 0 + retarray[0] == "Verification: OK"
         *  2) Hash is wrong -> retcode 1 + strpos(retarray[somewhere], "message imprint mismatch") !== false
         * 
         * every other case (Certificate not found / invalid / openssl is not installed / ts command not known)
         * are being handled the same way -> retcode 1 + any retarray NOT containing "message imprint mismatch"
         */
        
        if ($retcode === 0 && strtolower(trim($retarray[0])) == "verification: ok")
        {
            if (self::getTimestampFromAnswer ($base64_response_string) != $response_time)
                throw new Exception("The responsetime of the request was changed");
            
            return true;
        }

        foreach ($retarray as $retline)
        {
            if (stripos($retline, "message imprint mismatch") !== false)
                return false;
        }

        throw new Exception("Systemcommand failed: ".implode(", ", $retarray));
    }

    /**
     * Create a tempfile in the systems temp path
     *
     * @param string $str: Content which should be written to the newly created tempfile
     * @return string: filepath of the created tempfile
     */
    public static function createTempFile ($str = "")
    {
        $tempfilename = tempnam(sys_get_temp_dir(), rand());

        if (!file_exists($tempfilename))
            throw new Exception("Tempfile could not be created");
            
        if (!empty($str) && !file_put_contents($tempfilename, $str))
            throw new Exception("Could not write to tempfile");

        return $tempfilename;
    }

    /**
     * Test the correct version of OpenSSL
     *
     * @return true if everything is ok. Else otc.
     * @return json object if debug is a get variable.
     */
    public static function test()
    {
        $my_hash = sha1("Publicacion certificada by Antonio Sejas");
        $requestfile_path = self::createRequestfile($my_hash);
        $response = self::signRequestfile($requestfile_path, "http://zeitstempel.dfn.de");
        if (isset($_GET['debug'])) {
            return json_encode($response);
        }

        /*
        Array
        (
            [response_string] => Shitload of text (base64-encoded Timestamp-Response of the TSA)
            [response_time] => 1299098823
        )
        */

        //response_time == self::getTimestampFromAnswer($response['response_string']); //1299098823

        $tsa_cert_chain_file = dirname(__FILE__)."/chain.txt"; //from https://pki.pca.dfn.de/global-services-ca/pub/cacert/chain.txt
        $validate = self::validate($my_hash, $response['response_string'], $response['response_time'], $tsa_cert_chain_file); 

        if($validate){
            return true;
        }else{
            return false;
        }
    }

    public static function is_sha1($str) {
        return (bool) preg_match('/^[0-9a-f]{40}$/i', $str);
    }

    public static function getTimestamp($tsa,$my_hash,$pub_cer = null)
    {
        $resultado = "";
        if (!filter_var($tsa, FILTER_VALIDATE_URL)) {
            $resultado = json_encode(array('error'=>true, 'message'=>'tsa url is not valid'));
        } elseif (!self::is_sha1($my_hash)) {
            $resultado = json_encode(array('error'=>true, 'message'=>'hash is not valid, must be sha1'));
        } else {
            //Parámetros ok
            $requestfile_path = self::createRequestfile($my_hash);
            $response = self::signRequestfile($requestfile_path, $tsa);
            //Por defecto no se valida el timestamp, sólo se valida si nos pasan la url del certificado púlico.
            $validate = true;
            if(isset($pub_cer)){
             $tsa_cert_chain_file = $pub_cer;//"../chain.txt"; //from https://pki.pca.dfn.de/global-services-ca/pub/cacert/chain.txt
             $validate = self::validate($my_hash, $response['response_string'], $response['response_time'], $tsa_cert_chain_file); 
            }
            if($validate){
             $response['hash']=$my_hash;
             $response['error']=false;
             $resultado = json_encode($response);
            }else{
             $resultado = json_encode(array('error'=>true, 'message'=>'timestamp is not valid'));
            }
        }
        return $resultado;
        
    }
}
