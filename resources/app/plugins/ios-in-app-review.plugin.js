export default iosInAppReviewPlugin();

function iosInAppReviewPlugin(){
    return {
        install
    };

    function install(Vue, options){
        const { inappreview } = window;
        const platform = window.appEnv.clientPlatform;

        Vue.iosInAppReview = {
            requestReview
        };

        function requestReview(){
            let reviewHistory = null;
            let shouldPromptForReview = false;
            if(platform !== 'ios'){
                return false;
            }
            Vue.clientStorage.getItem('in_app_review_requests')
                .then(verfiyPromptEligibility)
                .then(promptForReview);

            function verfiyPromptEligibility(reviewHistoryFromStorage){
                reviewHistory = reviewHistoryFromStorage || {
                    request_timestamps: []
                };

                const today = Date.now();
                const oneMonth = 2629800000;
                const oneYear = 31557600000;
                const maxRequestsPerYear = 3;

                const hasBeenPrompted = reviewHistory.request_timestamps.length;
                const lastRequest = hasBeenPrompted && reviewHistory.request_timestamps[reviewHistory.request_timestamps.length - 1];
                const lastRequestOlderThanOneYear = lastRequest && lastRequest < (today - oneYear);
                const lastRequestOlderThanOneMonth = lastRequest && lastRequest < (today - oneMonth);

                if(lastRequestOlderThanOneYear){
                    shouldPromptForReview = true;
                    reviewHistory.request_timestamps = [];
                } else if(lastRequestOlderThanOneMonth){
                    shouldPromptForReview = reviewHistory.request_timestamps.length < maxRequestsPerYear;
                } else {
                    shouldPromptForReview = !hasBeenPrompted;
                }
            }

            function promptForReview(){
                if(shouldPromptForReview){
                    reviewHistory.request_timestamps.push(Date.now());
                    Vue.clientStorage.setItem('in_app_review_requests', reviewHistory);
                    inappreview.requestReview();
                }
            }
        }
    }
}
