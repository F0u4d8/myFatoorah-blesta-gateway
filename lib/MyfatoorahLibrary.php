<?php

/**
 * MyFatoorah is responsible for handling calling MyFatoorah API endpoints.
 * Also, It has necessary library functions that help in providing the correct parameters used endpoints.
 *
 * MyFatoorah offers a seamless business experience by offering a technology put together by our tech team. It enables smooth business operations involving sales activity, product invoicing, shipping, and payment processing. MyFatoorah invoicing and payment gateway solution trigger your business to greater success at all levels in the new age world of commerce. Leverage your sales and payments at all e-commerce platforms (ERPs, CRMs, CMSs) with transparent and slick applications that are well-integrated into social media and telecom services. For every closing sale click, you make a business function gets done for you, along with generating factual reports and statistics to fine-tune your business plan with no-barrier low-cost.
 * Our technology experts have designed the best GCC E-commerce solutions for the native financial instruments (Debit Cards, Credit Cards, etc.) supporting online sales and payments, for events, shopping, mall, and associated services.
 *
 * Created by MyFatoorah http://www.myfatoorah.com/
 * Developed By tech@myfatoorah.com
 * Date: 05/12/2023
 * Time: 12:00
 *
 * API Documentation on https://myfatoorah.readme.io/docs
 * Library Documentation and Download link on https://myfatoorah.readme.io/docs/php-library
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorah extends MyFatoorahHelper
{

    /**
     * The configuration used to connect to MyFatoorah test/live API server
     *
     * @var array
     */
    protected $config = [];

    /**
     * The URL used to connect to MyFatoorah test/live API server
     *
     * @var string
     */
    protected $apiURL = '';

    /**
     * The MyFatoorah PHP Library version
     *
     * @var string
     */
    protected $version = '2.2';

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor that initiates a new MyFatoorah API process
     *
     * @param array $config It has the required keys (apiKey, isTest, and vcCode) to process a MyFatoorah API request.
     */
    public function __construct($config)
    {

        $mfCountries = self::getMFCountries();

        $this->setApiKey($config);
        $this->setIsTest($config);
        $this->setVcCode($config);

        self::$loggerObj            = $this->config['loggerObj']  = empty($config['loggerObj']) ? null : $config['loggerObj'];
        self::$loggerFunc           = $this->config['loggerFunc'] = empty($config['loggerFunc']) ? null : $config['loggerFunc'];

        $code         = $this->config['vcCode'];
        $this->apiURL = $this->config['isTest'] ? $mfCountries[$code]['testv2'] : $mfCountries[$code]['v2'];
    }

    /**
     * Get the API URL
     * The URL used to connect to MyFatoorah test/live API server
     *
     * @return string
     */
    public function getApiURL()
    {
        return $this->apiURL;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set the API token Key
     * The API Token Key is the authentication which identify a user that is using the app. To generate one follow instruction here https://myfatoorah.readme.io/docs/live-token
     *
     * @param array $config It has the required keys (apiKey, isTest, and vcCode) to process a MyFatoorah API request.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function setApiKey($config)
    {
        if (empty($config['apiKey'])) {
            throw new Exception('Config array must have the "apiKey" key.');
        }

        $config['apiKey'] = trim($config['apiKey']);
        if (empty($config['apiKey'])) {
            throw new Exception('The "apiKey" key is required and must be a string.');
        }

        $this->config['apiKey'] = $config['apiKey'];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set the test mode. Set it to false for live mode
     *
     * @param array $config It has the required keys (apiKey, isTest, and vcCode) to process a MyFatoorah API request.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function setIsTest($config)
    {
        if (!isset($config['isTest'])) {
            throw new Exception('Config array must have the "isTest" key.');
        }

        if (!is_bool($config['isTest'])) {
            throw new Exception('The "isTest" key must be boolean.');
        }

        $this->config['isTest'] = $config['isTest'];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Set the vendor country code of the MyFatoorah account
     *
     * @param array $config It has the required keys (apiKey, isTest, and vcCode) to process a MyFatoorah API request.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function setVcCode($config)
    {
        $config['vcCode'] = $config['vcCode'] ?? $config['countryCode'] ?? '';
        if (empty($config['vcCode'])) {
            throw new Exception('Config array must have the "vcCode" key.');
        }

        $mfCountries    = self::getMFCountries();
        $countriesCodes = array_keys($mfCountries);

        $config['vcCode'] = strtoupper($config['vcCode']);
        if (!in_array($config['vcCode'], $countriesCodes)) {
            throw new Exception('The "vcCode" key must be one of (' . implode(', ', $countriesCodes) . ').');
        }

        $this->config['vcCode'] = $config['vcCode'];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * It calls the MyFatoorah API endpoint request and handles the MyFatoorah API endpoint response.
     *
     * @param string          $url        MyFatoorah API endpoint URL
     * @param array|null      $postFields POST request parameters array. It should be set to null if the request is GET.
     * @param int|string|null $orderId    The order id or the payment id of the process, used for the events logging.
     * @param string|null     $function   The requester function name, used for the events logging.
     *
     * @return mixed       The response object as the result of a successful calling to the API.
     *
     * @throws Exception    Throw exception if there is any curl/validation error in the MyFatoorah API endpoint URL.
     */
    protected  function callAPI($url, $postFields = null, $orderId = null, $function = null)
    {

        //to prevent json_encode adding lots of decimal digits
        ini_set('precision', '14');
        ini_set('serialize_precision', '-1');

        $request = isset($postFields) ? 'POST' : 'GET';
        $fields  = empty($postFields) ? json_encode($postFields, JSON_FORCE_OBJECT) : json_encode($postFields);

        $msgLog = "Order #$orderId ----- $function";
        $this->log("$msgLog - Request: $fields");

        //***************************************
        //call url
        //***************************************
        $curl = curl_init($url);

        $option = [
            CURLOPT_CUSTOMREQUEST  => $request,
            CURLOPT_POSTFIELDS     => $fields,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->config['apiKey'], 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true
        ];

        curl_setopt_array($curl, $option);

        $res = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        //example set a local ip to host apitest.myfatoorah.com
        if ($err) {
            $this->log("$msgLog - cURL Error: $err");
            throw new Exception($err);
        }

        $this->log("$msgLog - Response: $res");

        $json = json_decode((string) $res);

        //***************************************
        //check for errors
        //***************************************
        //Check for the reponse errors
        $error = self::getAPIError($json, (string) $res);
        if ($error) {
            $this->log("$msgLog - Error: $error");
            throw new Exception($error);
        }

        //***************************************
        //Success
        //***************************************
        return $json;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Handle Endpoint Errors Function
     *
     * @param mixed  $json MyFatoorah response JSON object.
     * @param string $res  MyFatoorah response string.
     *
     * @return string
     */
    protected static function getAPIError($json, $res)
    {

        $isSuccess = $json->IsSuccess ?? false;
        if ($isSuccess) {
            return '';
        }

        //Check for the HTML errors
        $hErr = self::getHtmlErrors($res);
        if ($hErr) {
            return $hErr;
        }

        //Check for the JSON errors
        if (is_string($json)) {
            return $json;
        }

        if (empty($json)) {
            return (!empty($res) ? $res : 'Kindly review your MyFatoorah admin configuration due to a wrong entry.');
        }

        return self::getJsonErrors($json);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Check for the HTML (response model) errors
     *
     * @param string $res MyFatoorah response string.
     *
     * @return string
     */
    protected static function getHtmlErrors($res)
    {
        //to avoid blocked IP like:
        //<html>
        //<head><title>403 Forbidden</title></head>
        //<body>
        //<center><h1>403 Forbidden</h1></center><hr><center>Microsoft-Azure-Application-Gateway/v2</center>
        //</body>
        //</html>
        //and, skip apple register <YourDomainName> tag error
        $stripHtmlStr = strip_tags($res);
        if ($res != $stripHtmlStr && stripos($stripHtmlStr, 'apple-developer-merchantid-domain-association') !== false) {
            return trim(preg_replace('/\s+/', ' ', $stripHtmlStr));
        }
        return '';
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Check for the json (response model) errors
     *
     * @param mixed $json MyFatoorah response JSON object.
     *
     * @return string
     */
    protected static function getJsonErrors($json)
    {

        $errorsVar = isset($json->ValidationErrors) ? 'ValidationErrors' : 'FieldsErrors';
        if (isset($json->$errorsVar)) {
            $blogDatas = array_column($json->$errorsVar, 'Error', 'Name');

            $mapFun = function ($k, $v) {
                return "$k: $v";
            };
            $errArr = array_map($mapFun, array_keys($blogDatas), array_values($blogDatas));

            return implode(', ', $errArr);
            //return implode(', ', array_column($json->ValidationErrors, 'Error'));
        }

        if (isset($json->Data->ErrorMessage)) {
            return $json->Data->ErrorMessage;
        }

        //if not, get the message.
        //sometimes Error value of ValidationErrors is null, so either get the "Name" key or get the "Message"
        //example {
        //"IsSuccess":false,
        //"Message":"Invalid data",
        //"ValidationErrors":[{"Name":"invoiceCreate.InvoiceItems","Error":""}],
        //"Data":null
        //}
        //example {
        //"Message":
        //"No HTTP resource was found that matches the request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'.",
        //"MessageDetail":
        //"No route providing a controller name was found to match request URI 'https://apitest.myfatoorah.com/v2/SendPayment222'"
        //}

        return empty($json->Message) ? '' : $json->Message;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Log the events
     *
     * @param string $msg It is the string message that will be written in the log file.
     */
    public static function log($msg)
    {

        $loggerObj  = self::$loggerObj;
        $loggerFunc = self::$loggerFunc;

        if (empty($loggerObj)) {
            return;
        }

        if (is_string($loggerObj)) {
            error_log(PHP_EOL . date('d.m.Y h:i:s') . ' - ' . $msg, 3, $loggerObj);
        } elseif (method_exists($loggerObj, $loggerFunc)) {
            $loggerObj->{$loggerFunc}($msg);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 * Trait MyFatoorah is responsible for helping calling MyFatoorah API endpoints.
 */
class MyFatoorahHelper
{

    /**
     * The file name or the logger object
     * It is used in logging the payment/shipping events to help in debugging and monitor the process and connections.
     *
     * @var string|object
     */
    public static $loggerObj;

    /**
     * The function name that will be used in the debugging if $loggerObj is set as a logger object.
     *
     * @var string
     */
    public static $loggerFunc;

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Returns the country code and the phone after applying MyFatoorah restriction
     *
     * Matching regular expression pattern: ^(?:(\+)|(00)|(\\*)|())[0-9]{3,14}((\\#)|())$
     * if (!preg_match('/^(?:(\+)|(00)|(\\*)|())[0-9]{3,14}((\\#)|())$/iD', $inputString))
     * String length: inclusive between 0 and 11
     *
     * @param string $inputString It is the input phone number provide by the end user.
     *
     * @return array        That contains the phone code in the 1st element the the phone number the the 2nd element.
     *
     * @throws Exception    Throw exception if the input length is less than 3 chars or long than 14 chars.
     */
    public static function getPhone($inputString)
    {

        //remove any arabic digit
        $string3 = self::convertArabicDigitstoEnglish($inputString);

        //Keep Only digits
        $string4 = preg_replace('/[^0-9]/', '', $string3);

        //remove 00 at start
        if (strpos($string4, '00') === 0) {
            $string4 = substr($string4, 2);
        }

        if (!$string4) {
            return ['', ''];
        }

        //check for the allowed length
        $len = strlen($string4);
        if ($len < 3 || $len > 14) {
            throw new Exception('Phone Number lenght must be between 3 to 14 digits');
        }

        //get the phone arr
        if (strlen(substr($string4, 3)) > 3) {
            return [
                substr($string4, 0, 3),
                substr($string4, 3)
            ];
        }

        return [
            '',
            $string4
        ];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Converts any Arabic or Persian numbers to English digits
     *
     * @param string $inputString It is the input phone number provide by the end user.
     *
     * @return string
     */
    protected static function convertArabicDigitstoEnglish($inputString)
    {

        $newNumbers = range(0, 9);

        $persianDecimal = ['&#1776;', '&#1777;', '&#1778;', '&#1779;', '&#1780;', '&#1781;', '&#1782;', '&#1783;', '&#1784;', '&#1785;']; // 1. Persian HTML decimal
        $arabicDecimal  = ['&#1632;', '&#1633;', '&#1634;', '&#1635;', '&#1636;', '&#1637;', '&#1638;', '&#1639;', '&#1640;', '&#1641;']; // 2. Arabic HTML decimal
        $arabic         = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']; // 3. Arabic Numeric
        $persian        = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹']; // 4. Persian Numeric

        $string0 = str_replace($persianDecimal, $newNumbers, $inputString);
        $string1 = str_replace($arabicDecimal, $newNumbers, $string0);
        $string2 = str_replace($arabic, $newNumbers, $string1);

        return str_replace($persian, $newNumbers, $string2);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given weight unit to MyFatoorah default weight unit.
     * Weight must be in kg, g, lbs, or oz. Default is kg.
     *
     * @param string $unit It is the weight unit used.
     *
     * @return double|int The conversion rate that will convert the given unit into the kg.
     *
     * @throws Exception Throw exception if the input unit is not support.
     */
    public static function getWeightRate($unit)
    {

        $lUnit = strtolower($unit);

        //kg is the default
        $rateUnits = [
            '1'         => ['kg', 'kgs', 'كج', 'كلغ', 'كيلو جرام', 'كيلو غرام'],
            '0.001'     => ['g', 'جرام', 'غرام', 'جم'],
            '0.453592'  => ['lbs', 'lb', 'رطل', 'باوند'],
            '0.0283495' => ['oz', 'اوقية', 'أوقية'],
        ];

        foreach ($rateUnits as $rate => $unitArr) {
            if (array_search($lUnit, $unitArr) !== false) {
                return (double) $rate;
            }
        }
        throw new Exception('Weight units must be in kg, g, lbs, or oz. Default is kg');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the rate that will convert the given dimension unit to MyFatoorah default dimension unit.
     * Dimension must be in cm, m, mm, in, or yd. Default is cm.
     *
     * @param string $unit It is the dimension unit used in width, hight, or depth.
     *
     * @return double|int   The conversion rate that will convert the given unit into the cm.
     *
     * @throws Exception        Throw exception if the input unit is not support.
     */
    public static function getDimensionRate($unit)
    {

        $lUnit = strtolower($unit);

        //cm is the default
        $rateUnits = [
            '1'     => ['cm', 'سم'],
            '100'   => ['m', 'متر', 'م'],
            '0.1'   => ['mm', 'مم'],
            '2.54'  => ['in', 'انش', 'إنش', 'بوصه', 'بوصة'],
            '91.44' => ['yd', 'يارده', 'ياردة'],
        ];

        foreach ($rateUnits as $rate => $unitArr) {
            if (array_search($lUnit, $unitArr) !== false) {
                return (double) $rate;
            }
        }
        throw new Exception('Dimension units must be in cm, m, mm, in, or yd. Default is cm');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate webhook signature function
     *
     * @param array  $dataArray webhook request array
     * @param string $secret    webhook secret key
     * @param string $signature MyFatoorah signature
     * @param int    $eventType MyFatoorah Event type Number (1, 2, 3 , 4)
     *
     * @return boolean
     */
    public static function isSignatureValid($dataArray, $secret, $signature, $eventType = 0)
    {

        if ($eventType == 2) {
            unset($dataArray['GatewayReference']);
        }

        uksort($dataArray, 'strcasecmp');

        $mapFun = function ($v, $k) {
            return sprintf("%s=%s", $k, $v);
        };
        $outputArr = array_map($mapFun, $dataArray, array_keys($dataArray));
        $output    = implode(',', $outputArr);

        // generate hash of $field string
        $hash = base64_encode(hash_hmac('sha256', $output, $secret, true));

        return $signature === $hash;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get a list of MyFatoorah countries, their API URLs, and names.
     *
     * @return array of MyFatoorah data
     */
    public static function getMFCountries()
    {

        $cachedFile = dirname(__FILE__) . '/mf-config.json';

        if (file_exists($cachedFile)) {
            if ((time() - filemtime($cachedFile) > 3600)) {
                $countries = self::getMFConfigFileContent($cachedFile);
            }

            if (!empty($countries)) {
                return $countries;
            }

            $cache = file_get_contents($cachedFile);
            return ($cache) ? json_decode($cache, true) : [];
        } else {
            return self::getMFConfigFileContent($cachedFile);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Cache a list of MyFatoorah countries, their API URLs, and names.
     *
     * @param string $cachedFile The file name used in caching data.
     *
     * @return array of MyFatoorah data
     */
    protected static function getMFConfigFileContent($cachedFile)
    {

        $curl = curl_init('https://portal.myfatoorah.com/Files/API/mf-config.json');

        $option = [
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true
        ];
        curl_setopt_array($curl, $option);

        $response  = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($http_code == 200 && is_string($response)) {
            file_put_contents($cachedFile, $response);
            return json_decode($response, true);
        } elseif ($http_code == 403) {
            touch($cachedFile);
            $fileContent = file_get_contents($cachedFile);
            if (!empty($fileContent)) {
                return json_decode($fileContent, true);
            }
        }
        return [];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Filter an input from global variables like $_GET, $_POST, $_REQUEST, $_COOKIE, $_SERVER
     *
     * @param string $name The field name the need to be filter.
     * @param string $type The input type to be filter (GET, POST, REQUEST, COOKIE, SERVER).
     *
     * @return string|null
     */
    public static function filterInputField($name, $type = 'GET')
    {
        if (isset($GLOBALS["_$type"][$name])) {
            return htmlspecialchars($GLOBALS["_$type"][$name]);
        }
        return null;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the payment status link
     *
     * @param string $url       The payment URL link
     * @param string $paymentId The payment Id
     *
     * @return string
     */
    public static function getPaymentStatusLink($url, $paymentId)
    {
        //to overcome session urls
        $pattern = '/MpgsAuthentication.*|ApplePayComplete.*|GooglePayComplete.*/i';
        return preg_replace($pattern, "Result?paymentId=$paymentId", $url);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 * MyFatoorahList handles the list process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahList extends MyFatoorah
{
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Gets the rate of a given currency according to the default currency of the MyFatoorah portal account.
     * 
     * @param string $currency The currency that will be converted into the currency of MyFatoorah portal account.
     * @param array  $allRates An array of MyFatoorah currencies and rates
     *
     * @return double
     *
     * @throws Exception        Throw exception if the input currency is not support by MyFatoorah portal account.
     */
    public static function getOneCurrencyRate($currency, $allRates)
    {

        foreach ($allRates as $value) {
            if ($value->Text == $currency) {
                return (double) $value->Value;
            }
        }
        throw new Exception('The selected currency is not supported by MyFatoorah');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Gets the rate of a given currency according to the default currency of the MyFatoorah portal account.
     *
     * @param string $currency The currency that will be converted into the currency of MyFatoorah portal account.
     *
     * @return double
     */
    public function getCurrencyRate($currency)
    {

        $allRates = $this->getCurrencyRates();
        return self::getOneCurrencyRate($currency, $allRates);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get list of MyFatoorah currency rates
     *
     * @return array
     */
    public function getCurrencyRates()
    {

        $url = "$this->apiURL/v2/GetCurrenciesExchangeList";
        return (array) $this->callAPI($url, null, null, 'Get Currencies Exchange List');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 *  MyFatoorahRefund handles the refund process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahRefund extends MyFatoorah
{
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * (deprecated function) use makeRefund instead
     * Refund a given PaymentId or InvoiceId
     *
     * @deprecated
     *
     * @param int|string        $keyId        payment id that will be refunded
     * @param double|int|string $amount       the refund amount
     * @param string            $currencyCode the amount currency
     * @param string            $comment      reason of the refund
     * @param int|string        $orderId      used in log file (default value: null)
     * @param string            $keyType      supported keys are (InvoiceId, PaymentId)
     *
     * @return object
     */
    public function refund($keyId, $amount, $currencyCode = null, $comment = null, $orderId = null, $keyType = 'PaymentId')
    {
        $postFields = [
            'Key'                     => $keyId,
            'KeyType'                 => $keyType,
            'RefundChargeOnCustomer'  => false,
            'ServiceChargeOnCustomer' => false,
            'Amount'                  => $amount,
            'CurrencyIso'             => $currencyCode,
            'Comment'                 => $comment,
        ];

        return $this->makeRefund($postFields, $orderId);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Call makeRefund API (POST API)
     *
     * @param array      $curlData Refund information
     * @param int|string $logId    Used in log file, example you can use the orderId (default value: null)
     *
     * @return object
     */
    public function makeRefund($curlData, $logId = null)
    {
        $url  = "$this->apiURL/v2/MakeRefund";
        $json = $this->callAPI($url, $curlData, $logId, 'Make Refund');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 * MyFatoorahShipping handles the shipping process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahShipping extends MyFatoorah
{
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get MyFatoorah Shipping Countries (GET API)
     *
     * @return array
     */
    public function getShippingCountries()
    {
        $url  = "$this->apiURL/v2/GetCountries";
        $json = $this->callAPI($url, null, null, 'Get Countries');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get Shipping Cities (GET API)
     *
     * @param int    $method      [1 for DHL, 2 for Aramex]
     * @param string $countryCode It can be obtained from getShippingCountries
     * @param string $searchValue The key word that will be used in searching
     *
     * @return array
     */
    public function getShippingCities($method, $countryCode, $searchValue = '')
    {
        $url = $this->apiURL . '/v2/GetCities'
                . '?shippingMethod=' . $method
                . '&countryCode=' . $countryCode
                . '&searchValue=' . urlencode(substr($searchValue, 0, 30));

        $json = $this->callAPI($url, null, null, "Get Cities: $countryCode");
        return array_map('ucwords', $json->Data->CityNames);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Calculate Shipping Charge (POST API)
     *
     * @param array $curlData the curl data contains the shipping information
     *
     * @return object
     */
    public function calculateShippingCharge($curlData)
    {
        $url  = "$this->apiURL/v2/CalculateShippingCharge";
        $json = $this->callAPI($url, $curlData, null, 'Calculate Shipping Charge');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 * MyFatoorahSupplier handles the Supplier process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahSupplier extends MyFatoorah
{
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Gets Supplier Dashboard information if Supplier exists in the MyFatoorah portal account.
     *
     * @param int $supplierCode The supplier code that exists in MyFatoorah portal account.
     *
     * @return object
     */
    public function getSupplierDashboard($supplierCode)
    {
        $url = $this->apiURL . '/v2/GetSupplierDashboard?SupplierCode=' . $supplierCode;
        return $this->callAPI($url, null, null, "Get Supplier Documents");
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Returns the supplier status in MyFatoorah account
     *
     * @param int $supplierCode The supplier code that exists in MyFatoorah portal account.
     *
     * @return boolean
     */
    public function isSupplierApproved($supplierCode)
    {
        $supplier = $this->getSupplierDashboard($supplierCode);
        return ($supplier->IsApproved && $supplier->IsActive);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 *  MyFatoorahPayment handles the payment process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahPaymentApi extends MyFatoorah
{

    /**
     * The file name used in caching the gateways data
     *
     * @var string
     */
    public static $pmCachedFile = __DIR__ . '/mf-methods.json';

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Methods (POST API)
     *
     * @param double|int $invoiceValue       Total invoice amount.
     * @param string     $displayCurrencyIso Total invoice currency.
     * @param boolean    $isCached           It used to cache the gateways
     *
     * @return array
     */
    public function initiatePayment($invoiceValue = 0, $displayCurrencyIso = '', $isCached = false)
    {

        $postFields = [
            'InvoiceAmount' => $invoiceValue,
            'CurrencyIso'   => $displayCurrencyIso,
        ];

        $json = $this->callAPI("$this->apiURL/v2/InitiatePayment", $postFields, null, 'Initiate Payment');

        $paymentMethods = ($json->Data->PaymentMethods) ?? [];

        if (!empty($paymentMethods) && $isCached) {
            file_put_contents(self::$pmCachedFile, json_encode($paymentMethods));
        }
        return $paymentMethods;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Cached Payment Gateways
     *
     * @return mixed of Cached payment methods.
     */
    public function getCachedVendorGateways()
    {

        if (file_exists(self::$pmCachedFile)) {
            $cache = file_get_contents(self::$pmCachedFile);
            return ($cache) ? json_decode($cache) : [];
        } else {
            return $this->initiatePayment(0, '', true);
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available cached  Payment Methods
     *
     * @param bool $isAppleRegistered Is site domain is registered with applePay and MyFatoorah or not
     *
     * @return array
     */
    public function getCachedCheckoutGateways($isAppleRegistered = false)
    {

        $gateways = $this->getCachedVendorGateways();

        $cachedCheckoutGateways = ['all' => [], 'cards' => [], 'form' => [], 'ap' => [], 'gp' => []];
        foreach ($gateways as $gateway) {
            $cachedCheckoutGateways = $this->addGatewayToCheckoutGateways($gateway, $cachedCheckoutGateways, $isAppleRegistered);
        }

        if ($isAppleRegistered) {
            //add only one ap gateway
            $cachedCheckoutGateways['ap'] = $cachedCheckoutGateways['ap'][0] ?? [];
        }

        return $cachedCheckoutGateways;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Add the MyFatoorah gateway object to the a given Payment Methods Array
     *
     * @param object  $gateway           MyFatoorah gateway object.
     * @param array   $checkoutGateways  Payment Methods Array.
     * @param boolean $isAppleRegistered Is site domain is registered with applePay and MyFatoorah or not.
     *
     * @return array
     */
    protected function addGatewayToCheckoutGateways($gateway, $checkoutGateways, $isAppleRegistered)
    {

        if ($gateway->PaymentMethodCode == 'gp') {
            $checkoutGateways['gp']    = $gateway;
            $checkoutGateways['all'][] = $gateway;
        } elseif ($gateway->PaymentMethodCode == 'ap') {
            if ($isAppleRegistered) {
                $checkoutGateways['ap'][] = $gateway;
            } else {
                $checkoutGateways['cards'][] = $gateway;
            }
            $checkoutGateways['all'][] = $gateway;
        } else {
            if ($gateway->IsEmbeddedSupported) {
                $checkoutGateways['form'][] = $gateway;
                $checkoutGateways['all'][]  = $gateway;
            } elseif (!$gateway->IsDirectPayment) {
                $checkoutGateways['cards'][] = $gateway;
                $checkoutGateways['all'][]   = $gateway;
            }
        }

        return $checkoutGateways;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get Payment Method Object
     *
     * @param string     $gateway            MyFatoorah gateway object.
     * @param string     $gatewayType        The Search key     ['PaymentMethodId', 'PaymentMethodCode'].
     * @param double|int $invoiceValue       Total invoice amount.
     * @param string     $displayCurrencyIso Total invoice currency.
     *
     * @return object
     *
     * @throws Exception
     */
    public function getOnePaymentMethod($gateway, $gatewayType = 'PaymentMethodId', $invoiceValue = 0, $displayCurrencyIso = '')
    {

        $paymentMethods = $this->initiatePayment($invoiceValue, $displayCurrencyIso);

        $paymentMethod = null;
        foreach ($paymentMethods as $pm) {
            if ($pm->$gatewayType == $gateway) {
                $paymentMethod = $pm;
                break;
            }
        }

        if (!isset($paymentMethod)) {
            throw new Exception('Please contact Account Manager to enable the used payment method in your account');
        }

        return $paymentMethod;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the invoice/payment URL and the invoice id
     *
     * @param array      $curlData           Invoice information.
     * @param int|string $gatewayId          MyFatoorah Gateway ID (default value: '0').
     * @param int|string $orderId            It used in log file (default value: null).
     * @param string     $sessionId          The payment session used in embedded payment.
     * @param string     $notificationOption could be EML, SMS, LNK, or ALL.
     *
     * @return array of invoiceURL and invoiceURL
     */
    public function getInvoiceURL($curlData, $gatewayId = 0, $orderId = null, $sessionId = null, $notificationOption = 'Lnk')
    {

        $this->log('------------------------------------------------------------');

        $curlData['CustomerReference'] = $curlData['CustomerReference'] ?? $orderId;

        if (!empty($sessionId)) {
            $curlData['SessionId'] = $sessionId;

            $data = $this->executePayment($curlData);
            return ['invoiceURL' => $data->PaymentURL, 'invoiceId' => $data->InvoiceId];
        } elseif ($gatewayId == 'myfatoorah' || empty($gatewayId)) {
            if (empty($curlData['NotificationOption'])) {
                $curlData['NotificationOption'] = $notificationOption;
            }

            $data = $this->sendPayment($curlData);
            return ['invoiceURL' => $data->InvoiceURL, 'invoiceId' => $data->InvoiceId];
        } else {
            $curlData['PaymentMethodId'] = $gatewayId;

            $data = $this->executePayment($curlData);
            return ['invoiceURL' => $data->PaymentURL, 'invoiceId' => $data->InvoiceId];
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Create an invoice Link (POST API)
     *
     * @param array $curlData Invoice information, check https://docs.myfatoorah.com/docs/send-payment#request-model.
     *
     * @return object
     */
    public function sendPayment($curlData)
    {

        $this->preparePayment($curlData);

        $json = $this->callAPI("$this->apiURL/v2/SendPayment", $curlData, $curlData['CustomerReference'], 'Send Payment');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Create an Payment Link (POST API)
     *
     * @param array $curlData Invoice information, check https://docs.myfatoorah.com/docs/execute-payment#required-fields.
     *
     * @return object
     */
    public function executePayment($curlData)
    {

        $this->preparePayment($curlData);

        $json = $this->callAPI("$this->apiURL/v2/ExecutePayment", $curlData, $curlData['CustomerReference'], 'Execute Payment'); //__FUNCTION__
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Prepare payment array for SendPayment and ExecutePayment
     *
     * @param array $curlData Invoice information
     */
    private function preparePayment(&$curlData)
    {

        $curlData['CustomerReference'] = $curlData['CustomerReference'] ?? null;
        $curlData['SourceInfo']        = $curlData['SourceInfo'] ?? 'MyFatoorah PHP Library ' . $this->version;

        if (empty($curlData['CustomerEmail'])) {
            $curlData['CustomerEmail'] = null;
        }
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get session Data
     *
     * @param string     $userDefinedField Customer Identifier to display its saved data.
     * @param int|string $logId            It used in log file, example you can use the orderId (default value: null).
     *
     * @return object
     */
    public function getEmbeddedSession($userDefinedField = '', $logId = null)
    {

        $curlData = ['CustomerIdentifier' => $userDefinedField];

        return $this->InitiateSession($curlData, $logId);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get session Data (POST API)
     *
     * @param array      $curlData Session properties.
     * @param int|string $logId    It used in log file, example you can use the orderId (default value: null).
     *
     * @return object
     */
    public function InitiateSession($curlData, $logId = null)
    {

        $json = $this->callAPI("$this->apiURL/v2/InitiateSession", $curlData, $logId, 'Initiate Session');
        return $json->Data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Register Apple Pay Domain (POST API)
     *
     * @param string $url Site URL
     *
     * @return object
     */
    public function registerApplePayDomain($url)
    {

        $domainName = ['DomainName' => parse_url($url, PHP_URL_HOST)];
        return $this->callAPI("$this->apiURL/v2/RegisterApplePayDomain", $domainName, '', 'Register Apple Pay Domain');
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 *  MyFatoorahPaymentForm handles the form process of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahPaymentEmbedded extends MyFatoorahPayment
{

    /**
     * The checkoutGateways array is used to display the payment in the checkout page.
     *
     * @var array
     */
    protected static $checkoutGateways;

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * List available Payment Methods
     *
     * @param double|int $invoiceValue       Total invoice amount.
     * @param string     $displayCurrencyIso Total invoice currency.
     * @param bool       $isAppleRegistered  Is site domain is registered with applePay and MyFatoorah or not.
     *
     * @return array
     */
    public function getCheckoutGateways($invoiceValue, $displayCurrencyIso, $isAppleRegistered)
    {

        if (!empty(self::$checkoutGateways)) {
            return self::$checkoutGateways;
        }

        $gateways = $this->initiatePayment($invoiceValue, $displayCurrencyIso);

        $mfListObj    = new MyFatoorahList($this->config);
        $allRates     = $mfListObj->getCurrencyRates();
        $currencyRate = MyFatoorahList::getOneCurrencyRate($displayCurrencyIso, $allRates);

        self::$checkoutGateways = ['all' => [], 'cards' => [], 'form' => [], 'ap' => [], 'gp' => []];
        foreach ($gateways as $gateway) {
            $gateway->PaymentTotalAmount = $this->getPaymentTotalAmount($gateway, $allRates, $currencyRate);

            $gateway->GatewayData = [
                'GatewayTotalAmount'   => number_format($gateway->PaymentTotalAmount, 2),
                'GatewayCurrency'      => $gateway->PaymentCurrencyIso,
                'GatewayTransCurrency' => self::getTranslatedCurrency($gateway->PaymentCurrencyIso),
            ];

            self::$checkoutGateways = $this->addGatewayToCheckoutGateways($gateway, self::$checkoutGateways, $isAppleRegistered);
        }

        if ($isAppleRegistered) {
            //add only one ap gateway
            self::$checkoutGateways['ap'] = $this->getOneApplePayGateway(self::$checkoutGateways['ap'], $displayCurrencyIso, $allRates);
        }

        return self::$checkoutGateways;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Calculate the amount value that will be paid in each payment method
     * 
     * @param object $paymentMethod The payment method object obtained from the initiate payment endpoint
     * @param array  $allRates      The MyFatoorah currency rate array of all gateways.
     * @param double $currencyRate  The currency rate of the invoice.
     * 
     * @return double
     */
    private function getPaymentTotalAmount($paymentMethod, $allRates, $currencyRate)
    {

        if ($paymentMethod->PaymentCurrencyIso == $paymentMethod->CurrencyIso) {
            return $paymentMethod->TotalAmount;
        }

        //convert to portal base currency
        $baseTotalAmount = ceil(((int) ($paymentMethod->TotalAmount * 100)) / $currencyRate) / 100;

        //gateway currency is not the portal currency
        $paymentCurrencyRate = MyFatoorahList::getOneCurrencyRate($paymentMethod->PaymentCurrencyIso, $allRates);
        if ($paymentCurrencyRate != 1) {
            return ceil($baseTotalAmount * $paymentCurrencyRate * 100) / 100;
        }

        return $baseTotalAmount;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Returns One Apple pay array in case multiple are enabled in the account
     *
     * @param array  $apGateways      The all available AP gateways
     * @param string $displayCurrency The currency of the invoice total amount.
     * @param array  $allRates        The MyFatoorah currency rate array of all gateways.
     *
     * @return array
     */
    protected function getOneApplePayGateway($apGateways, $displayCurrency, $allRates)
    {

        $displayCurrencyIndex = array_search($displayCurrency, array_column($apGateways, 'PaymentCurrencyIso'));
        if ($displayCurrencyIndex) {
            return $apGateways[$displayCurrencyIndex];
        }

        //get defult mf account currency
        $defCurKey       = array_search('1', array_column($allRates, 'Value'));
        $defaultCurrency = $allRates[$defCurKey]->Text;

        $defaultCurrencyIndex = array_search($defaultCurrency, array_column($apGateways, 'PaymentCurrencyIso'));
        if ($defaultCurrencyIndex) {
            return $apGateways[$defaultCurrencyIndex];
        }

        if (isset($apGateways[0])) {
            return $apGateways[0];
        }

        return [];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Returns the translation of the currency ISO code
     *
     * @param string $currency currency ISO code
     *
     * @return array
     */
    public static function getTranslatedCurrency($currency)
    {

        $currencies = [
            'KWD' => ['en' => 'KD', 'ar' => 'د.ك'],
            'SAR' => ['en' => 'SR', 'ar' => 'ريال'],
            'BHD' => ['en' => 'BD', 'ar' => 'د.ب'],
            'EGP' => ['en' => 'LE', 'ar' => 'ج.م'],
            'QAR' => ['en' => 'QR', 'ar' => 'ر.ق'],
            'OMR' => ['en' => 'OR', 'ar' => 'ر.ع'],
            'JOD' => ['en' => 'JD', 'ar' => 'د.أ'],
            'AED' => ['en' => 'AED', 'ar' => 'د'],
            'USD' => ['en' => 'USD', 'ar' => 'دولار'],
            'EUR' => ['en' => 'EUR', 'ar' => 'يورو']
        ];

        return $currencies[$currency] ?? ['en' => '', 'ar' => ''];
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}


/**
 *  MyFatoorahPaymentStatus handles the payment status of MyFatoorah API endpoints
 *
 * @author    MyFatoorah <tech@myfatoorah.com>
 * @copyright 2021 MyFatoorah, All rights reserved
 * @license   GNU General Public License v3.0
 */
class MyFatoorahPaymentStatus extends MyFatoorahPaymentApi
{
    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Get the Payment Transaction Status (POST API)
     *
     * @param int|string $keyId    MyFatoorah InvoiceId, PaymentId, or CustomerReference value.
     * @param string     $KeyType  The supported key types are "InvoiceId", "PaymentId", or "CustomerReference".
     * @param int|string $orderId  The order id in the store used to match the invoice data with store order.
     * @param string     $price    The order price in the store used to match the invoice data with store order.
     * @param string     $currency The order currency in the store used to match the invoice data with store order.
     *
     * @return object
     *
     * @throws Exception
     */






    public function getPaymentStatus($keyId, $KeyType, $orderId = null, $price = null, $currency = null)
    {

        //payment inquiry
        $curlData = ['Key' => $keyId, 'KeyType' => $KeyType];
        $json     = $this->callAPI("$this->apiURL/v2/GetPaymentStatus", $curlData, $orderId, 'Get Payment Status');

        $data = $json->Data;

        $msgLog = 'Order #' . $data->CustomerReference . ' ----- Get Payment Status';

        //check for the order information
        if (!self::checkOrderInformation($data, $orderId, $price, $currency)) {
            $err = 'Trying to call data of another order';
            $this->log("$msgLog - Exception is $err");
            throw new Exception($err);
        }

        //check invoice status (Paid and Not Paid Cases)
        if ($data->InvoiceStatus == 'Paid' || $data->InvoiceStatus == 'DuplicatePayment') {
            $data = self::getSuccessData($data);
            $this->log("$msgLog - Status is Paid");
        } elseif ($data->InvoiceStatus != 'Paid') {
            $data = self::getErrorData($data, $keyId, $KeyType);
            $this->log("$msgLog - Status is " . $data->InvoiceStatus . '. Error is ' . $data->InvoiceError);
        }

        return $data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Validate the invoice data with store order data
     *
     * @param object     $data     The MyFatoorah invoice data
     * @param int|string $orderId  The order id in the store used to match the invoice data with store order.
     * @param string     $price    The order price in the store used to match the invoice data with store order.
     * @param string     $currency The order currency in the store used to match the invoice data with store order.
     *
     * @return boolean
     */
    private static function checkOrderInformation($data, $orderId = null, $price = null, $currency = null)
    {

        //check for the order ID
        if ($orderId && $orderId != $data->CustomerReference) {
            return false;
        }

        //check for the order price and currency
        list($valStr, $mfCurrency) = explode(' ', $data->InvoiceDisplayValue);
        $mfPrice = (double) (preg_replace('/[^\d.]/', '', $valStr));

        if ($price && $price != $mfPrice) {
            return false;
        }

        return !($currency && $currency != $mfCurrency);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Search for the success transaction and return it in the focusTransaction object
     *
     * @param object $data The payment data object
     *
     * @return object
     */
    private static function getSuccessData($data)
    {

        foreach ($data->InvoiceTransactions as $transaction) {
            if ($transaction->TransactionStatus == 'Succss') {
                $data->InvoiceStatus = 'Paid';
                $data->InvoiceError  = '';

                $data->focusTransaction = $transaction;
                return $data;
            }
        }
        return $data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Search for the failed transaction and return it in the focusTransaction object
     *
     * @param object     $data    The payment data object
     * @param int|string $keyId   MyFatoorah InvoiceId, PaymentId, or CustomerReference value.
     * @param string     $KeyType The supported key types are "InvoiceId", "PaymentId", or "CustomerReference".
     *
     * @return object
     */
    private static function getErrorData($data, $keyId, $KeyType)
    {

        //------------------
        //case 1: payment is Failed
        $focusTransaction = self::{"getLastTransactionOf$KeyType"}($data->InvoiceTransactions, $keyId);
        if ($focusTransaction && $focusTransaction->TransactionStatus == 'Failed') {
            $data->InvoiceStatus = 'Failed';
            $data->InvoiceError  = $focusTransaction->Error . '.';

            $data->focusTransaction = $focusTransaction;

            return $data;
        }

        //------------------
        //case 2: payment is Expired
        //all myfatoorah gateway is set to Asia/Kuwait
        $ExpiryDateTime = $data->ExpiryDate . ' ' . $data->ExpiryTime;
        $ExpiryDate     = new \DateTime($ExpiryDateTime, new \DateTimeZone('Asia/Kuwait'));
        $currentDate    = new \DateTime('now', new \DateTimeZone('Asia/Kuwait'));

        if ($ExpiryDate < $currentDate) {
            $data->InvoiceStatus = 'Expired';
            $data->InvoiceError  = 'Invoice is expired since ' . $data->ExpiryDate . '.';

            return $data;
        }

        //------------------
        //case 3: payment is Pending
        //payment is pending .. user has not paid yet and the invoice is not expired
        $data->InvoiceStatus = 'Pending';
        $data->InvoiceError  = 'Pending Payment.';

        return $data;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Search for the failed transaction by the payment Id
     *
     * @param array      $transactions The transactions array
     * @param int|string $paymentId    the failed payment Id
     *
     * @return object|null
     */
    private static function getLastTransactionOfPaymentId($transactions, $paymentId)
    {

        foreach ($transactions as $transaction) {
            if ($transaction->PaymentId == $paymentId && $transaction->Error) {
                return $transaction;
            }
        }
        return null;
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Search for the last transaction of an invoice
     *
     * @param array $transactions The transactions array
     *
     * @return object
     */
    private static function getLastTransactionOfInvoiceId($transactions)
    {

        $usortFun = function ($a, $b) {
            return strtotime($a->TransactionDate) - strtotime($b->TransactionDate);
        };
        usort($transactions, $usortFun);

        return end($transactions);
    }

    //-----------------------------------------------------------------------------------------------------------------------------------------
}
