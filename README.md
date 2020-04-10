# Cointopay Custom Payment Gateway For Blesta
 
Cointopay.com crypto payment plugin for: **Blesta**

**Plugin is compatible with Blesta 4.3 version**

## Install

Please sign up for an account at <https://cointopay.com/Signup.jsp> Cointopay.com.

Please follow the Blesta Cointopay Plugin install instructions mentioned here: <a href="https://docs.google.com/document/d/1l6ZyS5lbPcsHe5s_3oKHGW_PrkiEhvKcIXUworMxJms/edit?usp=sharing">download Blesta Cointopay Plugin documentation</a> or direct link: https://docs.google.com/document/d/1l6ZyS5lbPcsHe5s_3oKHGW_PrkiEhvKcIXUworMxJms/edit?usp=sharing

Note down the MerchantID, SecurityCode and Currency, information is located in the Account section. These pieces of information are mandatory to be able to connect the payment module to your Blesta.

### Via Blesta Module Upload

Installation of cointopay plugin can be done by following steps below

1) Go to your Blesta installation directory
2) Unzip the cointopay plugin 
3) Redirect to admin area of your website and from left menu Click on Setting link  
4) Find Payment Gateways in "Company Tab" and click on Payment Gateways.
5) Now click on available tab and search cointopay in Non-Merchant Section
6) Find cointopay and click on install button
7) After installation you will be redirected to cointopay settings page insert your merchant id, security code and select coin for user transactions
8) Make sure to activate the payment method
9) If you want to configure cointopay after installation then click on Setting >> payment methods >> cointopay.
10) Click on cointopay from the list of installed methods.


### Testing

After login go to Setting >> Payment methods and click on  cointopay method to configure cointopay settings.

To test cointopay do the following steps.
1) Go to home page url
2) Click on test product add add test product to your cart
3) Click on cart  from top right corner 
4) Inside cart click on checkout option 
5) Select delivery method and click on proceed to payment 
6) Select cointopay from the list of payment methods 
7) Click on place order which will redirect you to cointopay website
8) After payment you will be redirected to Blesta
9) Go to domain/admin.php then click on client >> click on client ID to view order list.