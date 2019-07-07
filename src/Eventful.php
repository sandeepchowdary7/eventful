<?php

namespace Sandeepchowdary7\Laraeventful;

use Exception;
use Sandeepchowdary7\Laraeventful\Interfaces\EventfulInterface;

Class Eventful implements EventfulInterface
{
    private $app_key       = '';
    private $account_id      = '';
    private $error_code      = '';
    private $error_message   = '';
    private $user_agent      = 'Eventful API Laravel Wrapper (eventful.com)';
    private $api_end_point   = 'http://api.eventful.com/json/';
    private $recent_req_info = array();
    private $timeout         = 30;
    private $connect_timeout = 30;
    private $debug           = false;

    const GET    = 1;
    // const POST   = 2;
    // const DELETE = 3;
    // const PUT    = 4;

    /**
    * Accepts the token and saves it internally.
    *
    * @param string $app_key e.g. qsor48ughrjufyu2dadraasfa1212424
    * @throws Exception
    */
    public function __construct($app_key = null,$account_id = null) {
        if (!$app_key) {
            $app_key = config('eventful.app_key', 'app_key');
        }

        // if (!$account_id) {
        //     $account_id = config('eventful.account_id', 'account_id');
        // }

        $app_key = trim($app_key);
        // $account_id = trim($account_id);

        if (empty($app_key) || !preg_match('#^[\w-]+$#si', $app_key)) {
            throw new Exception("Missing or invalid Eventful API token.");
        }

        // if (empty($account_id)) {
        //     throw new Exception("Missing or invalid Eventful Account ID.");
        // }

        $this->app_key = $app_key;
        // $this->account_id = $account_id;
    }

    /**
    * Requests the campaigns for the given account.
    * @param array
    * @return array
    */
    public function getCityEvents($cityName) {

        if (!$cityName) 
            throw new Exception("Invalid input.");

        $url = $this->api_end_point . "events/search?location=$cityName&app_key=$this->app_key";
        $res = $this->makeRequest($url, $cityName);

        if (!empty($res['buffer'])) {
            $raw_json = json_decode($res['buffer'], true);
        }

        // here we distinguish errors from no city.
        // when there's no json that's an error
        $data = empty($raw_json)
        ? false
        : empty($raw_json['results']['0'])
        ? array()
        : $raw_json['results']['0'];

        return $data;
    }

    /**
    *
    * @param string $url
    * @param array $params
    * @param int $req_method
    * @return type
    * @throws Exception
    */
    public  function makeRequest($url, $params = array(), $req_method = self::GET) {
        if (!function_exists('curl_init')) {
            throw new Exception("Cannot find cURL php extension or it's not loaded.");
        }

        $ch = curl_init();

        if ($this->debug) {
            //curl_setopt($ch, CURLOPT_HEADER, true);
            // TRUE to output verbose information. Writes output to STDERR, or the file specified using CURLOPT_STDERR.
            curl_setopt($ch, CURLOPT_VERBOSE, true);
        }

        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connect_timeout);
        curl_setopt($ch, CURLOPT_USERPWD, $this->app_key . ":" . ''); // no pwd
        curl_setopt($ch, CURLOPT_USERAGENT, empty($params['user_agent']) ? $this->user_agent : $params['user_agent']);

        // if ($req_method == self::POST) { // We want post but no params to supply. Probably we have a nice link structure which includes all the info.
        //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        // } elseif ($req_method == self::DELETE) {
        //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        // } elseif ($req_method == self::PUT) {
        //     curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        // }

        // if (!empty($params)) {
        //     if ((isset($params['__req']) && strtolower($params['__req']) == 'get')
        //     || $req_method == self::GET) {
        //         unset($params['__req']);
        //         $url .= '?' . http_build_query($params);
        //     } elseif ($req_method == self::POST || $req_method == self::DELETE) {
        //         $params_str = is_array($params) ? json_encode($params) : $params;
        //         curl_setopt($ch, CURLOPT_POSTFIELDS, $params_str);
        //     }
        // }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Accept:application/json, text/javascript, */*; q=0.01',
            'Content-Type: application/vnd.api+json',
        ));

        $buffer = curl_exec($ch);
        $status = !empty($buffer);

        $data = array(
            'url'       => $url,
            'params'    => $params,
            'status'    => $status,
            'error'     => empty($buffer) ? curl_error($ch) : '',
            'error_no'  => empty($buffer) ? curl_errno($ch) : '',
            'http_code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
            'debug'     => $this->debug ? curl_getinfo($ch) : '',
        );

        curl_close($ch);

        // remove some weird headers HTTP/1.1 100 Continue or HTTP/1.1 200 OK
        $buffer = preg_replace('#HTTP/[\d.]+\s+\d+\s+\w+[\r\n]+#si', '', $buffer);
        $buffer = trim($buffer);
        $data['buffer'] = $buffer;

        $this->_parseError($data);
        $this->recent_req_info = $data;

        return $data;
    }

    /**
    * This returns the RAW data from the each request that has been sent (if any).
    * @return arraay of arrays
    */
    public  function getRequestInfo() {
        return $this->recent_req_info;
    }

    /**
    * Retruns whatever was accumultaed in error_message
    * @param string
    */
    public  function getErrorMessage() {
        return $this->error_message;
    }

    /**
    * Retruns whatever was accumultaed in error_code
    * @return string
    */
    public  function getErrorCode() {
        return $this->error_code;
    }

    /**
    * Some keys are removed from the params so they don't get send with the other data to Eventful.
    *
    * @param array $params
    * @param array
    */
    public  function _parseError($res) {
        if (empty($res['http_code']) || $res['http_code'] >= 200 && $res['http_code'] <= 299) {
            return true;
        }

        if (empty($res['buffer'])) {
            $this->error_message = "Response from the server.";
            $this->error_code = $res['http_code'];
        } elseif (!empty($res['buffer'])) {
            $json_arr = json_decode($res['buffer'], true);

            // The JSON error response looks like this.
            //{
            //     "errors": [{
            //     "code": "authorization_error",
            //     "message": "You are not authorized to access this resource"
            // }]
            // }

            if (!empty($json_arr['errors'])) { // JSON
                $messages = $error_codes = array();

                foreach ($json_arr['errors'] as $rec) {
                    $messages[] = $rec['message'];
                    $error_codes[] = $rec['code'];
                }

                $this->error_code = join(", ", $error_codes);
                $this->error_message = join("\n", $messages);
            } else { // There's no JSON in the reply so we'll extract the message from the HTML page by removing the HTML.
                $msg = $res['buffer'];

                $msg = preg_replace('#.*?<body[^>]*>#si', '', $msg);
                $msg = preg_replace('#</body[^>]*>.*#si', '', $msg);
                $msg = strip_tags($msg);
                $msg = preg_replace('#[\r\n]#si', '', $msg);
                $msg = preg_replace('#\s+#si', ' ', $msg);
                $msg = trim($msg);
                $msg = substr($msg, 0, 256);

                $this->error_code = $res['http_code'];
                $this->error_message = $msg;
            }
        } elseif ($res['http_code'] >= 400 || $res['http_code'] <= 499) {
            $this->error_message = "Not authorized.";
            $this->error_code = $res['http_code'];
        } elseif ($res['http_code'] >= 500 || $res['http_code'] <= 599) {
            $this->error_message = "Internal Server Error.";
            $this->error_code = $res['http_code'];
        }
    }

    // tmp
    public function __call($method, $args) {
        return array();
    }
}