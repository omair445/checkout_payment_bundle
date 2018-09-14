<?php

namespace Checkout\Bundle\PaymentBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Class PaymentController
 * @package Checkout\Bundle\PaymentBundle\Controller
 */
class PaymentController extends DefaultController
{
    private $checkout_url;
    /**
     * @param $firstName
     * @param $lastName
     * @return Response
     */
    public function paymentCheckoutHandler($dataNeedTopassOnRedirection= array(),$secretKey,$publicKey,$currency,
                                           $amount,$customerName,$customerUserName,$customerEmail)
    {
        $env = $this->container->getParameter("checkout_sdk_env");
        $this->checkout_url  =  $env == 'sandbox' ? 'https://sandbox.checkout.com/api2/v2' : 'https://checkout.com/api2/v2';

        $dataNeedTopassOnRedirection["secretKey"] =$secretKey;
        $dataNeedTopassOnRedirection["publicKey"] = $publicKey;
        $dataNeedTopassOnRedirection["currency"] = $currency;
        $dataNeedTopassOnRedirection['value'] = $amount;
        $dataNeedTopassOnRedirection['customerName'] =$customerName;
        $dataNeedTopassOnRedirection['userName'] = $customerUserName;
        $dataNeedTopassOnRedirection['customerEmail'] = $customerEmail;
        $str = json_encode($dataNeedTopassOnRedirection);
        $dataNeedTopassOnRedirection["checkout_js_url"]  = $env == 'sandbox' ? "https://cdn.checkout.com/sandbox/js/checkout.js" : "https://cdn.checkout.com/js/checkout.js";
        return $this->render('@CheckoutPayment/checkout/checkout.html.twig', array("data_array" => $dataNeedTopassOnRedirection,"str" =>base64_encode($str)));
    }




    ///// this method needs to be used for the public access only

    /**
     * @param Request $request
     * @Route("/checkout/process" ,name="paymentSuccess")
     */
    public function checkoutCompletePaymentHandler(Request $request)
    {
        $base_url =  $this->container->getParameter("app_root_url");
        $base_url= $base_url."/web";

        if(!empty($_POST["cko-card-token"])) {
            if (!empty($_POST["hidden_cart_data"])) {
                $hidden_data = base64_decode($_POST["hidden_cart_data"]);
                $data_array = array();
                $data_array = json_decode($hidden_data, true);
                $data_array["cardToken"] = $_POST["cko-card-token"];

            }
            if (empty($_POST["cko-card-token"])) exit();
            $response = $this->createPaymentCheckoutRequest($data_array);
            $response = json_decode($response);
            return $response;
        }
    }




    /**
     * @param $data_array
     * @return mixed
     */
    public function createPaymentCheckoutRequest($checkoutDataArrayThatIsRetrievedFromInitiatingRequest,$uniqueTrackId)
    {
        $data_array = $checkoutDataArrayThatIsRetrievedFromInitiatingRequest;
        $checkout_api_envirement = $this->container->getParameter("checkout_sdk_env");
        $this->checkout_url  = $checkout_api_envirement == 'sandbox' ? 'https://sandbox.checkout.com/api2/v2' : 'https://checkout.com/api2/v2';
        $secretKey = $data_array["secretKey"];
        $data_array["email"] = $data_array["customerEmail"];
        $data_array["trackId"] = $uniqueTrackId;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->checkout_url."/charges/token");
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        $headers = array();
        $headers[] = 'Content-type: application/json;charset=UTF-8';
        $headers[] = "Authorization: {$secretKey}";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_array));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }


}
