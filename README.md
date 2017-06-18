# paypal
Code to make PayPal work automatically. 

#### How to try Paypal Sandbox veresion: (Jun 18, 2017 by Yoshi Noda)

##### Before testing:

* Please copy some CC under yoshi/paypal/back/jpgs/250s and yoshi/paypal/back/stack/250s.
* You may need to create your Paypal sandbox buyer account. Please ask Sean or myself to create one for you if you want to test on your end.


##### Sandbox Tesing:

0. Clear all Paypal related cookies to have the credit card payment show up. 
1. goto https://cloudcoinconsortium.com/yoshi/buy.php 
2. buy 1 250 CC Jpeg note  or Stack one.
3. click Next. 
4. Please enter your sandbox buyer accout email and password.
5. Pay from Paypal should work so there is no need to input credit card info.
6. Click proceed and you will get the email sent from paypalemailer.php.
7. Optionally click "back to sellers page" to get back to buy page.
8. For the URL for downloading bought CC, please add "yoshi/" before paypal. For example
   https://cloudcoinconsortium.com/paypal/orders/2017.06.17.20.22.35.RTBPHHF2J855Q/250cc.zip
should be changed to
   https://cloudcoinconsortium.com/yoshi/paypal/orders/2017.06.17.20.22.35.RTBPHHF2J855Q/250cc.zip


#### Going Live:

yoshi/buy.php and yoshi/paypal/code/paypalemailer.php has some code designated to use Paypal sandbox. Those have to be changed to live one.

##### buy.php

[sandbox]
	<form action="https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_xclick&business=sean-facilitator@worthington.net" method="post" target="_top">
        <input type="hidden" name="notify_url" value="https://cloudcoinconsortium.com/yoshi/paypal/code/paypalemailer.php" />
        <input type="hidden" name="return" value="https://cloudcoinconsortium.com/yoshi/buy.php" />  <!--this is the return URL after purchase */

[live]
	<form action="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=[Sean's Paypal Business Account Email]" method="post" target="_top">
        <input type="hidden" name="notify_url" value="https://cloudcoinconsortium.com/paypal/code/paypalemailer.php" />
        <input type="hidden" name="return" value="https://cloudcoinconsortium.com/buy.php" />   <!--this is the return URL after purchase. change if necessary. */
[Sean's Paypal Business Account Email] should be replaced with the email address.


##### paypalemailer.php

[sandbox]
$enable_sandbox = true;

[live]
$enable_sandbox = false;


##### PaypalIPN.php

[sandbox]
    private $use_sandbox = true;

[live]
    private $use_sandbox = false;





#### Status as of Jun.18, 2017

* The sandbox version has been tested and confirmed working.


