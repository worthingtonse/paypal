# paypal
Code to make PayPal work automatically. 

#### How to try Paypal Sandbox veresion: (Jun 17, 2017 by Yoshi Noda)

0. Clear all Paypal related cookies to have the credit card payment show up. 
1. goto https://cloudcoinconsortium.com/yoshi/buy.php 
2. buy 1 250 CC Jpeg note  or Stack one.
3. click Next. 
4. Choose the credit payment. For the card number goto this site and get one  http://www.getcreditcardnumbers.com/
5. Input necessary info. the email should be sean-buyer@worthington.net. 
6. Click proceed and you get the payment complete page.  
7. Click on go back to buyer’s page will get you to yoshi/paypalemailer.php and it will show a blank page as it is almost empty.


#### Status as of Jun.17, 2017

* I have confirmed buy.php works with the Paypal Sandbox. I confirmed the paypalemailer.php is called after clicking on "back to seller's site."
* Debugged paypalemailer.php locally on my Mac. The fakemsg.html submits the fake Paypal IPN message (values). It is not tested on the server yet.

