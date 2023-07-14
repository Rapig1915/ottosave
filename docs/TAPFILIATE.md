# Tapfiliate Service

**Summary:** This service is utilized for tracking customers who sign up for the application via an affiliate link to ensure that the affiliates are awarded their specified commissions for referrals.

## User registration

**Summary:** The goal here is to associate a user with a given referral code. This code is found in the `ref` query of the registration page url and subsequently passed to the API when submitting the registration form.

1. The API passes the referral code along to the AccountCreated event
1. In response to the AccountCreated event customer is then created in Tapfiliate via their REST API
1. The resulting Tapfiliate customer information is then stored in the database in the `tapfiliate_customers` table

## Conversions

**Summary:** Conversions occur whenever a payment is received for a user who registered via an affiliate link. These processes are slightly different for Braintree based subscriptions vs. iOS based subscriptions.

- ### With Braintree subscriptions

 - The conversion is created in response to the BraintreeRenewalComplete event
 - The Braintree conversions utilize the default commission type

- ### With iOS subscriptions

 - The conversion is created in response to the ITunesRenewalComplete event
 - The iOS conversions utilize custom commission types based on the the iOS product_id. The commission type identifier equals the iOS product_id with the periods replaced with dashes (since Tapfiliate doesn't allow periods in their commission ids.) For example: the iOS product with id `com.defendyourmoney.subscription.plus.yearly.1` becomes `com-defendyourmoney-subscription-plus-yearly-1` in Tapfiliate.
 - The iOS commissions need to be maintained within the [Tapfiliate program settings](https://ottosave.tapfiliate.com/a/settings/program/otto-affiliate-program/commission-structure/) utilizing a fixed commission amount. Percentage based commissions are not a viable option since we do not receive the payment amount information from Apple when verifying their receipts.
 - **Note:** At present there is a bug in the Tapfiliate UI which prevents creating fixed commission amounts ending in `0`. e.g. `1.50` is rejected as "should be a number" but `1.51` is accepted as valid
