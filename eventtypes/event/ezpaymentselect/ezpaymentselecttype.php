<?php

//
// Definition of eZSimpleShippingType class
//
// Created on: <09-äÅË-2002 14:42:23 sp>
//
// SOFTWARE NAME: eZ Publish
// SOFTWARE RELEASE: 4.1.0alpha1
// BUILD VERSION: 22737
// COPYRIGHT NOTICE: Copyright (C) 1999-2008 eZ Systems AS
// SOFTWARE LICENSE: GNU General Public License v2.0
// NOTICE: >
//   This program is free software; you can redistribute it and/or
//   modify it under the terms of version 2.0  of the GNU General
//   Public License as published by the Free Software Foundation.
//
//   This program is distributed in the hope that it will be useful,
//   but WITHOUT ANY WARRANTY; without even the implied warranty of
//   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//   GNU General Public License for more details.
//
//   You should have received a copy of version 2.0 of the GNU General
//   Public License along with this program; if not, write to the Free
//   Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
//   MA 02110-1301, USA.
//
//

/*! \file ezsimpleshippingtype.php
*/

/*!
  \class eZSimpleShippingType ezsimpleshippingtype.php
  \brief The class eZSimpleShippingType handles adding shipping cost to an order

*/

class eZPaymentSelectType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'ezpaymentselect';

    /*!
     Constructor
    */
    
    
	private static function storeOrderData(& $order, $data_text_1, $data_text_2 = "") {
		
		$order->setAttribute('data_text_1', $data_text_1);
		$order->setAttribute('data_text_2', $data_text_2);

		$order->store();

	}
  
   
    function eZPaymentSelectType()
    {
        $this->eZWorkflowEventType( eZPaymentSelectType::WORKFLOW_TYPE_STRING, ezi18n( 'kernel/workflow/event', "Payment select" ) );
        $this->setTriggerTypes( array( 'shop' => array( 'confirmorder' => array ( 'before' ) ) ) );
    }
    
    
    private function checkCreditCard($owner, $type, $number, $cvv, $expiry){
    	
    	$is_valid = true;
    	
    	if($is_valid) $is_valid = CreditCard::number((int) $number);
		if($is_valid) $is_valid = CreditCard::expiry($expiry['month'],$expiry['year']);	
		if($is_valid) $is_valid = CreditCard::owner($owner);
		//if($is_valid) $is_valid = CreditCard::cvv($cvv,$type);
		return $is_valid;
    }
    
    
    private function checkDebit($accountname,$accountnumber,$bankname,$banknumber){
    	$is_valid = true;
    	
    	if(strlen($accountname ) < 3) $is_valid = false;
    	if(strlen($accountnumber) < 3) $is_valid = false;
    	if(strlen($bankname) < 3) $is_valid = false;
    	if(strlen($banknumber) < 3) $is_valid = false;
    
    
    	return  $is_valid;
    }
       
    

    private static function checkPayment(){
    	$http = eZHTTPTool :: instance();
		
		$http->setSessionVariable('payment',$http->postVariable('payment'));
		
    	if($http->postVariable('payment'))
	{
		return true;
	}

    
    }
    
    
    private static function filterOrderPayment($order)
    {
    
    	$filter = array(0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15);
    	
    	foreach($order->productItems() as $item)
    	{
    		$dataMap = $item['item_object']->contentObject()->dataMap();
    		if(!array_key_exists('paymethod', $dataMap))
    		{
    			return $filter;
    		}
    		$paymethod = $dataMap['paymethod'];
    		$content = $paymethod->content();
    		
    		
    		$filter = array_intersect($filter,$content);
    		
    		
    		
    	}
    	return $filter;
    }

    public function execute( $process, $event )
    {
    	
    
	$parameters = $process->attribute('parameter_list');
	$order = eZOrder :: fetch($parameters['order_id']);
   

	$filter = self::filterOrderPayment($order);
	
	$process->Template = array();
    $process->Template['templateName'] = 'design:workflow/ezpaymentselect.tpl';
    $process->Template['templateVars'] = array ( 'order' => $order, 'filter' =>  $filter);
    
		
	if(!$this->checkPayment())
	
		return eZWorkflowType::STATUS_FETCH_TEMPLATE_REPEAT;
	else
	{
		eZPaymentSelectType::storeOrderData($order, $_POST['payment'], serialize($_POST));
		return eZWorkflowType::STATUS_ACCEPTED;
	}


    }
      
    
}

eZWorkflowEventType::registerEventType( eZPaymentSelectType::WORKFLOW_TYPE_STRING, "eZPaymentSelectType" );

?>
