# Finicity Integration

**Summary:** This service is utilized for connecting bank accounts via Finicity API.

**Important** Applications accessing the Finicity APIs must be hosted within the United States or Canada.

## Generate Your Credentials

**Summary:** Sign up for a developer account on the Finicity Developer Portal and create your API credentials. These will give you access to our Test Drive and test banks (FinBanks) until you are ready to upgrade to a paid plan with live financial institutions and real customers.

1. Navigate to the `Sign Up page`
2. Click `Test Drive` and follow the step-by-step instructions
3. Copy your `Partner ID`, `Partner Secret` and `Finicity App Key` and set env vars.

## Testing Locally and on QA

**Summary:** If you'd like to link an ottosave account to a test bank account, search for the 'Finbank' institution and enter any value in the username and password fields. From there you can create linked fake accounts.

1. Login with your test user
1. Click "Accounts" in the left menu
1. Click "Link accounts and credit cards"
1. Search "Finbank" and click it.
1. Agree to the terms of service
1. Enter the Userid and Password (you can use any strings here)
1. Select the accounts you want to link
1. Submit them to be linked