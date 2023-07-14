import { Plugins } from '@capacitor/core';
import router from 'vue_root/router';

export default iosUniversalLinksPlugin();

function iosUniversalLinksPlugin(){
    return {
        install
    };

    function install(Vue, options){
        const { App } = Plugins;
        const platform = window.appEnv.clientPlatform;

        if(!options.baseUrl){
            throw new Error('The baseUrl option is required for use of iosUniversalLinksPlugin');
        }

        if(platform !== 'web'){
            App.getLaunchUrl().then(navigateToUrl);
            App.addListener('appUrlOpen', navigateToUrl);
        }

        function navigateToUrl({ url }){
            const path = url.replace(options.baseUrl, '');
            router.push({ path });
        }
    }
}
