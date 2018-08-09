<?php
/**
 * Cointopay International Payment Gateway for Blesta
 *
 * Crypto currency payment provider
 *
 * @package blesta
 * @subpackage blesta.components.gateways.nonmerchant.cointopay
 * @author Cointopay International
 * @copyright Copyright (c) 2018, Cointopay International
 * @link http://www.blesta.com/ Blesta
 * @link https://cointopay.com Cointopay International
 */
class Cointopay extends NonmerchantGateway
{
    private static $version = "1.0.0";
    private static $authors = [['name' => 'Cointopay International B.V.', 'url' => 'https://app.cointopay.com']];
    private $meta;
    public function __construct()
    {
        Loader::loadComponents($this, array("Input"));

        Loader::loadModels($this, ['Clients']);

        Language::loadLang("cointopay", null, dirname(__FILE__) . DS . "language" . DS);
    }

    public function getName()
    {
        return Language::_("Cointopay.name", true);
    }

    public function getVersion()
    {
        return self::$version;
    }

    public function getAuthors()
    {
        return self::$authors;
    }

    public function getCurrencies()
    {
        return array("EUR", "GBP", "USD", "BTC", "RUR");
    }

    public function setCurrency($currency)
    {
        $this->currency = $currency;
    }

    public function getSettings(array $meta = null)
    {
        $this->view = $this->makeView("settings", "default", str_replace(ROOTWEBDIR, "", dirname(__FILE__) . DS));

        Loader::loadHelpers($this, array("Form", "Html"));


        $this->view->set('meta', $meta);

        return $this->view->fetch();
    }

    public function editSettings(array $meta)
    {
        $rules = [
            'merchant_id'     => [
                'valid' => [
                    'rule'    => "isEmpty",
                    'negate'  => true,
                    'message' => Language::_("Cointopay.!error.merchant.id.valid", true),
                ],
            ],
            'security_code' => [
                'valid' => [
                    'rule'    => "isEmpty",
                    'negate'  => true,
                    'message' => Language::_("Cointopay.!error.security.code.valid", true),
                ],
            ],
        ];

        $this->Input->setRules($rules);

        $this->Input->validates($meta);

        return $meta;
    }

    public function encryptableFields()
    {
        return ['merchant_id','security_code'];
    }

    public function setMeta(array $meta = null)
    {
        $this->meta = $meta;
    }

    public function buildProcess(array $contact_info, $amount, array $invoice_amounts = null, array $options = null)
    {
        Loader::load(dirname(__FILE__) . DS . 'cointopay' . DS . 'init.php');

        $client_id = $this->ifSet($contact_info['client_id']);

        if (isset($invoice_amounts) && is_array($invoice_amounts)) {
            $invoices = $this->serializeInvoices($invoice_amounts);
        }

        $record = new Record();

        $company_name = $record->select("name")->from("companies")->where("id", "=", 1)->fetch();


        $orderId = $client_id . '@' . (!empty($invoices) ? $invoices : time());
        $token = md5($orderId);

        echo $callbackURL = Configure::get('Blesta.gw_callback_url')
            . Configure::get('Blesta.company_id') . '/cointopay/?client_id='
            . $this->ifSet($contact_info['client_id']) . '&token=' . $token.'&order_id='.$orderId;
        $post_params = array(
            'order_id'         => $client_id,
            'price'            => $this->ifSet($amount),
            'description'      => $this->ifSet($options['description']),
            'title'            => $company_name->name . " " .$this->ifSet($options['description']),
            'token'            => $token,
            'currency'         => $this->ifSet($this->currency),
            'callback_url'     => $this->flash_encode($callbackURL),
            'cancel_url'       => $this->flash_encode($this->ifSet($options['return_url'])),
            'success_url'      => $this->flash_encode($this->ifSet($options['return_url'])),
        );
        $order = \Cointopay\Merchant\Order::createOrFail($post_params, array(), array(
            'merchant_id'       => $this->meta['merchant_id'],
            'security_code'     => $this->meta['security_code'],
            'selected_currency' =>$this->ifSet($this->currency),
            'user_agent'  => 'Cointopay - Blesta v' .BLESTA_VERSION . ' Extension v' . $this->getVersion(),
        ));
        //print_r($post_params);exit;
        if ($order && $order->shortURL) {
            header("Location: " . $order->shortURL);
        } else {
            print_r($order);
        }
    }

    public function validate(array $get, array $post)
    {
        $this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($get), "output", true);


        $data_parts = explode('@', $this->ifSet($get['order_id']), 2);

        $client_id = $data_parts[0];
        $invoices = $this->ifSet($data_parts[1]);

        if (is_numeric($invoices)) {
            $invoices = null;
        }

        $invoice_detail=$this->unserializeInvoices($invoices);

        $orderId = $get['order_id'];

        $status = $this->statusChecking($get);

        return [
            'client_id'      => $client_id,
            'amount'         => $this->ifSet($invoice_detail[0]['amount']),
            'currency'       => $this->ifSet($post['currency']) ?? 'USD',
            'status'         => $status,
            'reference_id'   => $orderId,
            'transaction_id' => $this->ifSet($get['TransactionID']),
            'invoices'       => $this->unserializeInvoices($invoices),
        ];
    }

    public function success(array $get, array $post)
    {
        $this->log($this->ifSet($_SERVER['REQUEST_URI']), serialize($get), "output", true);

        $data_parts = explode('@', $this->ifSet($get['order_id']), 2);

        $client_id = $data_parts[0];
        $invoices = $this->ifSet($data_parts[1]);

        if (is_numeric($invoices)) {
            $invoices = null;
        }

        $invoice_detail=$this->unserializeInvoices($invoices);

        $orderId = $get['order_id'];

        $status = $this->statusChecking($get);
        $data = [
            'client_id'      => $client_id,
            'amount'         => $this->ifSet($invoice_detail[0]['amount']),
            'currency'       => $this->ifSet($post['currency']) ?? 'USD',
            'status'         => $status,
            'reference_id'   => $orderId,
            'transaction_id' => $this->ifSet($get['TransactionID']),
            'invoices'       => $this->unserializeInvoices($invoices),
        ];
        //var_dump($data);exit;
        return $data;
    }

    public function validateResponse($response) {
        $validate = true;
        $merchant_id =  $this->meta['merchant_id'];;
        $transaction_id = $response['TransactionID'];
        $confirm_code = $response['ConfirmCode'];
        $url = "https://app.cointopay.com/v2REAPI?MerchantID=$merchant_id&Call=QA&APIKey=_&output=json&TransactionID=$transaction_id&ConfirmCode=$confirm_code";
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => 0
        ));
        $result = curl_exec($curl);
        $result = json_decode($result, true);
        if(!$result || !is_array($result)) {
            $validate = false;
        }else{
            if($response['Status'] != $result['Status']) {
                $validate = false;
            }
        }
        return $validate;
    }

    public function capture($reference_id, $transaction_id, $amount, array $invoice_amounts = null)
    {
        $this->Input->setErrors($this->getCommonError("unsupported"));
    }

    public function void($reference_id, $transaction_id, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError("unsupported"));
    }

    public function refund($reference_id, $transaction_id, $amount, $notes = null)
    {
        $this->Input->setErrors($this->getCommonError("unsupported"));
    }

    private function serializeInvoices(array $invoices)
    {
        $str = '';
        foreach ($invoices as $i => $invoice) {
            $str .= ($i > 0 ? '|' : '') . $invoice['id'] . '=' . $invoice['amount'];
        }

        return $str;
    }

    private function unserializeInvoices($str)
    {
        $invoices = [];
        $temp = explode('|', $str);
        foreach ($temp as $pair) {
            $pairs = explode('=', $pair, 2);
            if (count($pairs) != 2) {
                continue;
            }
            $invoices[] = ['id' => $pairs[0], 'amount' => $pairs[1]];
        }

        return $invoices;
    }

    public function statusChecking($get)
    {
        $ctp_status = $get['status'];
        $not_enough = (integer)$get['notenough'];
        $validate = $this->validateResponse($get);
        if(!$validate) {
            $lang['ClientPay.received.statement'] = "Your payment has been declined";
            $this->Input->setErrors(['cointopay' =>[
                "declined" => "Data do not match! Data doesn\'t match to Cointopay."
            ]]);
            $status = 'declined';
        }
        elseif($ctp_status== 'paid')
        {
            $lang['ClientPay.received.statement'] = "Your payment has been approved!";
            $status = 'approved';
            if($not_enough == 1) {
                $lang['ClientPay.received.statement'] = "Your payment is in pending!";
                $this->Input->setErrors(['cointopay' =>[
                    "pending" => "Your payment has not been Paid in full"
                ]]);
                $status = 'pending';
            }
        }
        else
        {
            $lang['ClientPay.received.statement'] = "Your payment has been declined!";
            $this->Input->setErrors(['cointopay' =>[
                "declined" => "Your payment has been declined!"
            ]]);
            $status = 'declined';
        }

        return $status;
    }

    private function flash_encode($input)
    {
        return rawurlencode(utf8_encode($input));
    }
}
