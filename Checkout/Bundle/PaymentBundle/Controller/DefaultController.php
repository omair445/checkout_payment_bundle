<?php

namespace Checkout\Bundle\PaymentBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{



    /**
     * @param $response
     * @return string
     */
    public function createPaymentResponseValidation($response,$data_array)
    {
        $data_array2 = $data_array;
        $base_url =  $this->container->getParameter("app_root_url");
        $base_url= $base_url."/web";
        if( empty( $response ) ){
            header("HTTP/1.0 500 Internal Server Error");
            $message = "Ops, some error occurred during payment process , please try again !";
            $base_url=$base_url."/?error=1";
            $html = $this->renderView('V1Bundle:Default:checkout_error.html.twig',array("screen_message"=> $message , 'base_url' =>$base_url));
            return json_encode( ['html'=> $html, 'error' => 1002, 'message' => $message] );
        }

        if (isset($response->errorCode) && !empty($response->errorCode)) {


            header("HTTP/1.0 500 Internal Server Error");
            $screen_message = "Ops, some error occurred during payment process , please try again !";
            $base_url=$base_url."/?error=1";
            if($response->errorCode == 405)
            {
                $screen_message = $response->message;
            }
            $html = $this->renderView('V1Bundle:Default:checkout_error.html.twig',array("screen_message"=> $screen_message , 'base_url' =>$base_url));
            return json_encode( ['html'=> $html, 'error' => 1002, 'message' => $response->message] );
        }

        if ($response->status == "Authorised" && ($response->responseCode == "10000" || $response->responseCode == "10100") ) {
            $package = $this->getDoctrine()->getRepository("CoredirectionBundle:Package")->find($data_array2['packageId']);
            $data_array2['card_id']   = $response->card->id;
            $data_array2['last4']   = $response->card->last4;
            $data_array2['charge_id']   = $response->id;
            $data_array2['track_id']   = $response->trackId;
            $data_array2['value']   = ($response->value);
            $data_array2['currency']   = $response->currency;
            $data_array2['api_response']   = $response;
            $data_array2['package_name']   = $package->getName();
            $date = new \DateTime('now');
            $data_array2['p_on_date']   = $date->format('d-m-Y');
            $key =  $this->generateCorePassCorporateKey($data_array2['subscription_id']);
            $data_array2['key'] = $key->getCompanyKey();
            $billing = new SubscriptionBilling();
            $billing->setAmount($data_array2['value'] / 100);
            $billing->setCurrency( $response->currency );
            $billing->setCard($response->card->id);
            $billing->setCharge( 1);
            $billing->setTransactionType('Payment');
            $billing->setCreatedDate(new \DateTime('now') );
            $billing->setTransactionResponse(json_encode($response));
            $billing->setVat(0);
            $billing->setTrack($data_array2['subscription_id']);
            $subscription = $this->getDoctrine()->getRepository('CoredirectionBundle:Subscription')->find($data_array2['subscription_id']);
            $billing->setUpdatedDate(new \DateTime('now'));
            $billing->setSubscription($subscription);
            $billing->setTransactionResponse($response->card->paymentMethod);
            $em = $this->getDoctrine()->getManager();
            $em->persist($billing);
            $em->flush();
            $subscription->setKey($key);
            $em->persist($subscription);
            $em->flush();

            // ========= Payment Invoice Email =================== //
            $util = new UtilService($this->container);
            $email_template  ="payment_invoice_email_key.html.twig";

            $rendered = $util->getContainer()->get('templating')->render("V1Bundle:Default:{$email_template}",$data_array2);
            $subject= "Core Direction - Core Pass Activation";
            $body = $rendered;

            $util = new UtilService($this->container);
            $toEmail = $data_array2['customerEmail'];
            $util->sendSMTPEmail($subject,$body, $toEmail,array(Shared::$BCC_EMAIL_1,Shared::$BCC_EMAIL_2,Shared::$BCC_EMAIL_3));
            // ----------------------------------------------------------- //
            $message = isset($response->message) ? $response->message : 'Transaction successfull';
            $screen_message=Shared::$CORPORATE_KEY_SENT_MESSAGE;

            $html = $this->renderView('V1Bundle:Default:checkout_success.html.twig', array("screen_message"=> $screen_message ,"notAvailableMessage"=> '' , "base_url"=>$base_url));

            $responseCode = isset($response->responseCode) ? $response->responseCode : 0;

            return json_encode(['html' => $html, 'error' => 200, 'message' => $message, 'track_id' => $data_array2['track_id'], 'package_name' => $data_array2['package_name'], 'value' => $data_array2['value'], 'price' => $data_array2['value'] / 100]);




        } else {
            $responseCode = isset($response->responseCode) ? $response->responseCode : 207;
            $message = "Ops, some error occurred during payment process , please try again !";
            header("HTTP/1.0 500 Internal Server Error");
            $screen_message = "Ops, some error occurred during payment process , please try again !";
            $html = $this->renderView('V1Bundle:Default:checkout_error.html.twig',array("screen_message"=> $screen_message , 'base_url' =>$base_url));
            return json_encode( ['html'=> $html, 'error' => 1002, 'message' => $message] );
        }
    }
}
