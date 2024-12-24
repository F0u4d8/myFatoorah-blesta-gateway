<?php



/**
 * Merchant Demo Credit Card Gateway
 *
 * @package blesta
 * @subpackage blesta.components.gateways.merchant_demo_cc
 * @copyright Copyright (c) 2010, Phillips Data, Inc.
 * @license http://www.blesta.com/license/ The Blesta License Agreement
 * @link http://www.blesta.com/ Blesta
 */
class MyfatoorahPayment extends NonmerchantGateway  {

   private $base_url = 'https://apitest.myfatoorah.com/v2/';
    private $meta;
   public function __construct() {
 $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

	  $this->loadConfig(dirname(__FILE__) . DS . 'config.json');

        // Load components required by this gateway
        Loader::loadComponents($this, ['Input']);

		Language::loadLang("myfatoorah_payment", null, dirname(__FILE__) . DS . "language" . DS);
	}
	
    
    /**
     * Attempt to install this gateway
     */
    public function install()
    {
        // Ensure that the system has support for the JSON extension
        if (!function_exists('json_decode')) {
            $errors = [
                'json' => [
                    'required' => Language::_('MyfatoorahPayment.!error.json_required', true)
                ]
            ];
            $this->Input->setErrors($errors);
        }
    }

        public function setCurrency($currency)
    {
        $this->currency = $currency;
    }




    /**
 * Create and return the view content required to modify the settings of this gateway
 *
 * @param array $meta An array of meta (settings) data belonging to this gateway
 * @return string HTML content containing the fields to update the meta data for this gateway
 */
public function getSettings(array $meta = null)
{
    // Load the view into this object, so helpers can be automatically added to the view
    
    $this->view = $this->makeView('settings', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));

    // Load the helpers required for this view
    Loader::loadHelpers($this, ['Form', 'Html']);

    $this->view->set('meta', $meta);

    return $this->view->fetch();
}

public function editSettings(array $meta) {
    $rules = [
        'mode' => [
            'valid' => [
                'rule' => ['in_array', ['test', 'prod']],
                'message' => Language::_('MyfatoorahPayment.!error.mode.invalid', true)
            ]
        ],
        'test_api_key' => [
            'required_if_test' => [
                'rule' => function ($key) use ($meta) {
                    return $meta['mode'] === 'test' ? !empty($key) : true;
                },
                'message' => Language::_('MyfatoorahPayment.!error.test_api_key.required', true)
            ]
        ],
        'prod_api_key' => [
            'required_if_prod' => [
                'rule' => function ($key) use ($meta) {
                    return $meta['mode'] === 'prod' ? !empty($key) : true;
                },
                'message' => Language::_('MyfatoorahPayment.!error.prod_api_key.required', true)
            ]
        ],
        'country_code' => [
            'valid' => [
                'rule' => ['in_array', ['KWT', 'SAU', 'ARE', 'OMN', 'BHR', 'QAT', 'JOR' ,'EGY']],
                'message' => Language::_('MyfatoorahPayment.!error.country_code.invalid', true)
            ]
        ]
    ];
    $this->Input->setRules($rules);
    $this->Input->validates($meta);
    return $meta;
}

  private function getApiUrl() {
        if ($this->meta['mode'] === 'prod') {
            // Use appropriate production URL based on country
            return 'https://portal.myfatoorah.com/v2/';
        }
        return $this->base_url;
    }

 public function encryptableFields() {
        return ['test_api_key', 'prod_api_key'];
    }

    private function getApiKey() {
        return $this->meta['mode'] === 'test' ? 
            $this->meta['test_api_key'] : 
            $this->meta['prod_api_key'];
    }

private function getScriptUrl() {
        return $this->meta['mode'] === 'prod' 
            ? 'https://portal.myfatoorah.com/payment/v1/session.js'
            : 'https://demo.myfatoorah.com/payment/v1/session.js';
    }


    /**
     * Sets the meta data for this particular gateway
     *
     * @param array $meta An array of meta data to set for this gateway
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }






 public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null) {
        
Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'MyfatoorahLibrary.php');
        
$apiKey = $this->getApiKey();
$currency = (isset($this->currency) ? $this->currency : null);
$callbackUrl = Configure::get("Blesta.gw_callback_url") . Configure::get("Blesta.company_id") . "/myfatoorah_payment/". $contact_info['client_id'] ;
$mfConfig = [
        'apiKey' => $apiKey,
        'vcCode' => $this->meta['country_code'],
        'isTest' => $this->meta['mode'] === 'test',
    ];

$baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
        $baseUrl .= $_SERVER['HTTP_HOST'];

            $mfObj = new MyFatoorahPaymentApi($mfConfig);


 // Load the models required
        Loader::loadModels($this, ['Clients']);

        $client = $this->Clients->get($contact_info['client_id']);



$invoiceItems = array_map(function ($invoice) {
    return [
        'ItemName' => 'Invoice ID ' . $invoice['id'], // Use the invoice ID or custom description
        'Quantity' => 1,                             // Default to 1
        'UnitPrice' => $invoice['amount'],           // Use the invoice amount
       
    ];
}, $invoice_amounts);


// Extract invoice IDs for the CustomerReference
$invoiceIds = array_column($invoice_amounts, 'id'); // Get all invoice IDs
$customerReference = implode(',', $invoiceIds);


$logFile = dirname(__FILE__) . DS . 'debug.log';

file_put_contents(
    $logFile,
    '[' . date('Y-m-d H:i:s') . '] Method Error: ' . print_r($invoiceItems, true) . '\n' .  print_r($invoice_amounts, true) . PHP_EOL,
    FILE_APPEND
);




 // Prepare payment data for each method
            $paymentMethodsData = [];
            $methods = $mfObj->initiatePayment($amount, $currency);
try {

foreach ($methods as $method) {
    
        try {
            

                    
                    $postFields = [
                        'InvoiceValue' => $amount,
                        'PaymentMethodId' => $method->PaymentMethodId,
                        'DisplayCurrencyIso' => $currency,
                        'InvoiceItems' =>$invoiceItems ,
                        'DisplayCurrencyIso' => $currency ,
                      //  'CallBackUrl' => $options['return_url'] ,
                           'CallBackUrl' => $callbackUrl,
                  'CustomerEmail' => $client->email ,
                  'CustomerReference' => $contact_info['client_id'],
                    ];
                    
                    $data = $mfObj->executePayment($postFields);
                    
                    $method->PaymentURL = $data->PaymentURL;
                    $paymentMethodsData[] = $method;
                    
                } catch (Exception $ex) {
                    // Log individual method errors but continue with others
                    $logFile = dirname(__FILE__) . DS . 'debug.log';
                    file_put_contents(
                        $logFile,
                        '[' . date('Y-m-d H:i:s') . '] Method Error: ' . $ex->getMessage() . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }
            
            $this->view = $this->makeView('paymentMethods', 'default', str_replace(ROOTWEBDIR, '', dirname(__FILE__) . DS));
            Loader::loadHelpers($this, ['Form', 'Html']);
            
            $this->view->set('paymentMethods', $paymentMethodsData);
            
        } catch (Exception $ex) {
            $this->view->set('error', $ex->getMessage());
        }
        
        return $this->view->fetch();
    }


public function validate(array $get, array $post) 
{
    $logFile = dirname(__FILE__) . DS . 'debug.log';
    Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'MyfatoorahLibrary.php');
    
    $apiKey = $this->getApiKey();
    $mfConfig = [
        'apiKey' => $apiKey,
        'vcCode' => $this->meta['country_code'],
        'isTest' => $this->meta['mode'] === 'test',
    ];
    
    $mfObj = new MyFatoorahPaymentStatus($mfConfig);
    $paymentId = isset($get['paymentId']) ? $get['paymentId'] : null;
    
    try {
        $paymentData = $mfObj->getPaymentStatus($paymentId, "PaymentId");

 file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . '] Payment Data: ' . json_encode($paymentData, JSON_PRETTY_PRINT),
            FILE_APPEND
        );
 $status = 'declined';
        $transaction_id = null;

 if ($paymentData->InvoiceStatus === 'Paid') {
            foreach ($paymentData->InvoiceTransactions as $transaction) {
                if ($transaction->TransactionStatus === 'Succss') {
                    $status = 'approved';
                    $transaction_id = $transaction->PaymentId;
                    break;
                }
            }
        }
  elseif ($paymentData->InvoiceStatus === 'Pending') {
  foreach ($paymentData->InvoiceTransactions as $transaction) {

if ($transaction->PaymentId === $paymentId) {
                    if ($transaction->TransactionStatus === 'Failed') {
                        $status = 'declined';
                        $transaction_id = $transaction->PaymentId;
                    } elseif ($transaction->TransactionStatus === 'InProgress') {
                        $status = 'pending';
                        $transaction_id = $transaction->PaymentId;
                    }
                    break;
                }

  }
  
  }      

$client_id = $paymentData->CustomerReference;
$invoices = $this->extractInvoices($paymentData);;


$invoiceData = $this->parseInvoiceDisplayValue($paymentData->InvoiceDisplayValue);



 file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . '] validate Data: ' . $client_id . $paymentData->focusTransaction -> PaidCurrencyValue . $paymentData-> focusTransaction -> PaidCurrency . print_r($invoices, true) . $status   ,
            FILE_APPEND
        ); 




return [
            'client_id' => $client_id,
            'amount' => $invoiceData['amount'] ,
            'currency' => $invoiceData['currency'] ,
            'invoices' => $invoices,
            'status' => $status,
            'transaction_id' => $transaction_id,
            'parent_transaction_id' => null
        ];
    } 
   catch (Exception $e) {
     file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . '] Success handler error: ' . $e->getMessage(),
            FILE_APPEND
        );
        return null;
   }
        
        
    
}
        
    




public function success(array $get, array $post) 
{

        Loader::load(dirname(__FILE__) . DS . 'lib' . DS . 'MyfatoorahLibrary.php');
        
 $logFile = dirname(__FILE__) . DS . 'debug.log';

        $apiKey = $this->getApiKey();
        $mfConfig = [
        'apiKey' => $apiKey,
        'vcCode' => $this->meta['country_code'],
        'isTest' => $this->meta['mode'] === 'test',
    ];
        
        $mfObj = new MyFatoorahPaymentStatus($mfConfig);
        $paymentId = isset($get['paymentId']) ? $get['paymentId'] : null;
        $paymentData = $mfObj->getPaymentStatus($paymentId, "PaymentId");
        

    $status = 'declined';
        $transaction_id = null;

  if ($paymentData->InvoiceStatus === 'Paid') {
            foreach ($paymentData->InvoiceTransactions as $transaction) {
                if ($transaction->TransactionStatus === 'Succss') {
                    $status = 'approved';
                    $transaction_id = $transaction->PaymentId;
                    break;
                }
            }
        }
  elseif ($paymentData->InvoiceStatus === 'Pending') {
  foreach ($paymentData->InvoiceTransactions as $transaction) {

if ($transaction->PaymentId === $paymentId) {
                    if ($transaction->TransactionStatus === 'Failed') {
                        $status = 'declined';
                        $transaction_id = $transaction->PaymentId;
                    } elseif ($transaction->TransactionStatus === 'InProgress') {
                        $status = 'pending';
                        $transaction_id = $transaction->PaymentId;
                    }
                    break;
                }

  }
  
  }      

$client_id = $paymentData->CustomerReference;
$invoices = $this->extractInvoices($paymentData);;
$invoiceData = $this->parseInvoiceDisplayValue($paymentData->InvoiceDisplayValue);

 file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . '] success Data: ' . $client_id . $paymentData->focusTransaction -> PaidCurrencyValue . $paymentData-> focusTransaction -> PaidCurrency .  print_r($invoices, true) . $status   ,
            FILE_APPEND
        ); 


return [
            'client_id' => $client_id,
            'amount' =>$invoiceData['amount'],
            'currency' => $invoiceData['currency'],
            'invoices' => $invoices,
            'status' => $status,
            'transaction_id' => $transaction_id,
            'parent_transaction_id' => null
        ];

    

    
}


/**
 * Extracts invoice information from payment data items
 * 
 * @param array|object $paymentData The payment data from MyFatoorah
 * @return array Array of invoice data with IDs and amounts
 */
private function extractInvoices($paymentData)
{
    $invoices = [];
    $logFile = dirname(__FILE__) . DS . 'debug.log';
    
    try {
        // Check if InvoiceItems exists and is accessible
        if (!isset($paymentData->InvoiceItems)) {
            file_put_contents(
                $logFile,
                '[' . date('Y-m-d H:i:s') . '] Warning: InvoiceItems not found in payment data' . "\n",
                FILE_APPEND
            );
            return $invoices;
        }

        foreach ($paymentData->InvoiceItems as $item) {
            // Extract the ID from the "ItemName" field
            preg_match('/Invoice ID (\d+)/', $item->ItemName, $matches);
            $id = isset($matches[1]) ? (int)$matches[1] : null;
            
            // Prepare the transformed invoice data
            if ($id !== null) {
                $invoices[] = [
                    'id' => $id,
                    'amount' => $item->UnitPrice
                ];
            }
        }
        
        // Log the extracted invoices
        file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . '] Extracted invoices: ' . json_encode($invoices, JSON_PRETTY_PRINT) . "\n",
            FILE_APPEND
        );
        
        return $invoices;
        
    } catch (Exception $e) {
        file_put_contents(
            $logFile,
            '[' . date('Y-m-d H:i:s') . '] Invoice extraction error: ' . $e->getMessage() . "\n",
            FILE_APPEND
        );
        return $invoices;
    }
}


public function parseInvoiceDisplayValue($invoiceDisplayValue)
{
    // Define a mapping of two-letter abbreviations to three-letter ISO codes
    $currencyMap = [
        'KD' => 'KWD',
        'SR' => 'SAR',
        'BD' => 'BHD',
        'DH' => 'AED',
        'QR' => 'QAR',
        'OR' => 'OMR',
        'JD' => 'JOD',
        'EG' => 'EGP',
        'USD' => 'USD', // No conversion needed for USD
    ];

    // Regex to capture amount and currency
    if (preg_match('/^([\d.]+)\s+([A-Z]{2,3})$/', $invoiceDisplayValue, $matches)) {
        $amount = number_format((float)$matches[1], 2, '.', ''); // Format amount to 2 decimal places
        $currency = strtoupper($matches[2]); // Ensure the currency is uppercase

        // Convert two-letter codes to their three-letter equivalents
        if (isset($currencyMap[$currency])) {
            $currency = $currencyMap[$currency];
        }

        return [
            'amount' => $amount,
            'currency' => $currency
        ];
    }

    return null; // Return null if the format doesn't match
}

}
?>