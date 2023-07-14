# Sentry Integration

We use sentry to monitor errors on the front-end and back-end of this system.

## Getting Started Locally

This integration shouldn't be required to get up and running, but if you're asked to make any changes to the integration or you need to test related functionality, you'll find information below about signing up and integrating Sentry with your project.

In addition, you'll find information below about how to use this integration to capture messages and exceptiosn.

## Setup on QA and Prod

You'll find information below about signing up, integrating, and using this integration.

# Sign up for Sentry

Signing up for a sentry user is super easy with following 3 steps.

1. Sign up for a new sentry user at [sentry.io](https://sentry.io).
2. Enter Your Organization Name.
3. Create a Project for Your Application.

You're all done and just ready to receive your messages.

## Integrate with your project

1. Copy Client DSN
Go to your project settings page at. `https://sentry.io/settings/{organization}/projects/{project}/keys/`.
Make sure to replace your `organization` and `project` name.
2. Update api/.env to add above dsn as SENTRY_LARAVEL_DSN as well as SENTRY_ENVIRONMENT.
3. Update web/.env to add above dsn as VUE_APP_SENTRY_CLIENT_DSN as well as VUE_APP_SENTRY_ENVIRONMENT.
4. From `app.js`, pass custom options to initialize your sentry client.

## Usage

Sentry automatically captures uncaught exceptions and rejected handlers and send to your client so that you don't miss any errors in your application.
