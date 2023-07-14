### Initial Environment Setup
1. Make sure your Mac device has the [minimum Capacitor dependencies](https://capacitor.ionicframework.com/docs/getting-started/dependencies/) installed
	1. Specifically Node v8.6.9 on your mac, CocoaPods 1.5.3, and Xcode Command Line tools.
1. If you need to monitor network calls, use [Charles](https://www.charlesproxy.com/).
	- You'll need to install the [root certificates for IOS similators](https://www.charlesproxy.com/documentation/using-charles/ssl-certificates/). Then Enable SSL Proxying in the SSL Proxying Settings with a wildcard for both host and port.

### Building for Local iOS Development
1. Start up the docker containers following the steps outlined in the [docker docs.](../docker/README.md)
1. Open another terminal on your mac for the following commands
1. Make sure your node depencencies are installed locally on your mac (e.g. `npm install`)
1. Double check your `APP_URL` in your `.env` file and your `server.url` in `capacitor.config.json`
	- With Docker Toolbox, default values are already set
	- With Docker for Mac
		- `APP_URL=http://localhost`
		- `server.url="http://localhost"`
1. Double check your `RECAPTCHA_DISABLED` and `RECAPTCHA_SITE_KEY` in your `.env` file
1. Sync project dependencies for the iOS app by running `npx cap sync`
1. Update the iOS build with any front-end file changes by running `npx cap copy`
1. Open the project in Xcode by running `npx cap open ios`
1. Build the application and run the simulator by clicking the "Play button" in Xcode (top left)

### Building Before Submitting for Review
1. Once your changes are ready to be submitted, you'll need to test the final code in the similator
1. Remove the server.url in capacitor.config.json (server.url is only for local development)
1. Update the index file: `node docker/ios/build-scripts/compile-index-file.js` (this step may not be necessary upon further testing)
1. Sync project dependencies for the iOS app by running `npx cap sync`
1. Update the iOS build with any front-end file changes by running `npx cap copy` on your mac
1. Open the project in Xcode by running `npx cap open ios` on your mac
1. Build the application and run the simulator by clicking the "Play button" in Xcode (top left)
1. Test your changes before

### Building for Production (or Staging)
1. Checkout the master (or develop) branch
1. Build the containers using the steps outlined in the [docker docs.](../docker/README.md)
    1. If you change branches, you'll need to do a full rebuild
1. Update the APP_URL in your .env file to point to the environment you're trying to build for
1. Update the `RECAPTCHA_DISABLED` and `RECAPTCHA_SITE_KEY` in your .env file depending on the environment you're trying to build for
1. Update the index file: `node docker/ios/build-scripts/compile-index-file.js`
1. Ensure the proper domains are configured for the target environment
    1. Change the file `ios/App/App.entitlements` or through Xcode in App > Capabilities > Associated Domains
    1. These should already be set if you're rebuilding
1. Update the release and build version in Xcode
    - Open the project in xcode `npx cap open ios`
    - Select the file in the left column `App project` (this will open the App.xcodeproj in the center editor)
    - Navigate to the General tab > Identity section
    - Update the Version to equal the current release version
1. Remove the server.url in capacitor.config.json (server.url is only for local development)
1. Sync project dependencies for the iOS app by running `npx cap sync`
1. Set your target to "Any iOS Device" (dropdown to right of play button)
1. Select Product > Archive in Xcode
1. Once the archive is finished, click `Distribute App`
    - Select the apple store option
    - Select to upload
    - Use all defaults till it is done
