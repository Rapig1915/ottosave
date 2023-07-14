<?php

namespace App\Console\Commands;

use App\Services\External\FinicityService;
use Illuminate\Console\Command;
use Storage;

class RemoveIdleFinicityCustomers extends Command
{
    protected $signature = 'dym:remove-idle-finicity-customers {--checkonly=} {--code==}';
    protected $description = 'Delete idle customers';

    private $code;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        if($this->option('code')){
            $this->code = $this->option('code');
        }

        $optionCheckOnly = $this->option('checkonly');
        if(!in_array($optionCheckOnly, ['yes', 'no'])){
            $this->log("Required option [checkonly] should be either 'yes' or 'no'", 'warn');
            $this->log("Example Usage: php artisan dym:remove-idle-finicity-customers --checkonly=yes");
            return;
        }

        $isCheckOnly = $optionCheckOnly !== "no";

        $this->log("Started");
        if($isCheckOnly){
            $this->log("Check-Only mode...");
        }
        
        $FinicityService = new FinicityService();
        $requestPath = 'aggregation/v1/customers';
        $payload = [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Finicity-App-Key' => $FinicityService->app_key,
                'Finicity-App-Token' => $FinicityService->access_token
            ],
            'query' => [
                'start' => 1,
                'limit' => 25,
                'type' => config('finicity.env') === 'production' ? 'active' : 'testing'
            ]
        ];
        
        $moreDataAvailable = false;
        do {
            $this->log("Loading all {$payload['query']['type']} finicity customers [start: {$payload['query']['start']}, limit: {$payload['query']['limit']}]");

            $response = $FinicityService->client->get($requestPath, $payload);
            $responseJson = json_decode($response->getBody()->getContents());
            $moreDataAvailable = $responseJson->moreAvailable;
            $customers = $responseJson->customers;

            $this->log("Loaded " . count($customers) . " customers");
            
            foreach ($customers as $customer) {
                $finictyCustomer = \App\Models\FinicityCustomer::where('customer_id', $customer->id)->first();
                $isIdleCustomer = is_null($finictyCustomer);
                if($isIdleCustomer) {
                    $this->log("Customer #{$customer->id}({$customer->username}) is idle. Deleting from finicity.");
                    if(!$isCheckOnly){
                        $FinicityService->deleteCustomer(null, $customer->id);
                    }
                } else {
                    $this->log("Customer #{$customer->id}({$customer->username}) is active.");
                }
            }

            if(!$moreDataAvailable){
                break;
            }

            $payload['query']['start'] = $payload['query']['start'] + $payload['query']['limit'];
        }while($moreDataAvailable);

        $this->log("Finished");
    }

    private function log($message, $type = 'info'){
        $time = date('Y-m-d H:i:s');
        $signature = explode(' ', $this->signature)[0] ?? '';
        $composedMessage = "[$time][$type][$signature] $message";

        if($type === 'error'){
            $this->error($composedMessage);
        } else if($type === 'warn'){
            $this->warn($composedMessage);
        } else if($type === 'info') {
            $this->info($composedMessage);
        } else {
            $this->line($composedMessage);
        }

        $logOnFile = !empty($this->code);
        if($logOnFile){
            $path = "logs/commands/{$this->code}.log";
            $time = date('Y-m-d H:i:s');
            $signature = explode(' ', $this->signature)[0] ?? '';
            Storage::disk('local')->append($path, $composedMessage);
        }
    }
}
