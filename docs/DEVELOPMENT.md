# Development Docs

Read this page to understand how to run this codebase quickly and make and submit changes correctly. A thorough review of this entire page will get you started well.


## Setup and Run Your Local Development Environment

Everything you need to run this codebase locally is included in the Docker Setup Steps linked below. Following this link is probably the first thing you do after you read the rest of this page.

+ [Docker Setup Steps](../docker/README.md)


## GIT Branching Model

The following link is important to help you understand how to make code changes and submit them for review. You'll want to review this after you get your code running locally, but before you start making code changes.

+ [Remote Flow Branching Model](https://buink.biz/a-continuous-deployment-git-branching-model/)

For now, just make sure you use the `master` branch.


## Third-party Integrations

Don't worry about these now, but you may eventually need to setup some of the following integrations if your future tasks require it.

+ [Finicity](./FINICITY.md)
+ [Recaptcha](./RECAPTCHA.md)
+ [Tapfiliate](./TAPFILIATE.md)
+ [Sentry](./SENTRY.md)
+ [Capacitor](./CAPACITOR.md)


## Code style guide

It is important that you write your code in a cost effective way that makes it easy to understand, update, and maintain.

Here are some [general code requirements](https://github.com/bbuie/docs/wiki/Common-Code-Requirements) that may reduce the amount of feedback you get on your pull requests. It would be helpful to review these after you get your code running, but before you make any code changes.

Here are some additional recommendations, which can be reviewed later:

- API responses should match the [JSON API format](http://jsonapi.org/format/).
- Use [BEM](http://getbem.com/introduction/) for SCSS class naming conventions.


## Developer user

After the codebase is running, you'll find the user credentials in [/database/seeders/UsersSeeder.php](../database/seeders/UsersSeeder.php)


## IOS App Information

The vue code in this repository is used to build and deploy an IOS app to the app store. Changes you make to the views will eventually need to be tested and deployed to the app store. In most instances, if it works locally on the web, it will work in the app, so you don't need to worry much about this when you begin working.

For more information on testing, building, and deploying to the app store, see the [Capacitor docs](./CAPACITOR.md)
