<?php

namespace SmartHub\FbInsights\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use SmartHub\FbInsights\Models\FbPagePost;

use Log;

class StorePagePosts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        Log::info('Job started');

        FbPagePost::firstorCreate([
                                    'user_id'=>$this->data['userData']['user_id'], 
                                    'post_id'=>$this->data['postData']['post_id']
                                  ], 
                                    [
                                        'page_id' => $this->data['userData']['page_id'],
                                        'post_insights' => json_encode($this->data['postData']),
                                    ]);

        Log::info('Job end');


    }
}
