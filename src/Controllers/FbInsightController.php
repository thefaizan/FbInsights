<?php

namespace SmartHub\FbInsights\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as Controller;

use SmartHub\FbInsights\Models\FbUser;
use SmartHub\FbInsights\Models\PageInfo;
use SmartHub\FbInsights\Models\FbPagePost;
use SmartHub\FbInsights\Models\FbInsightSetting;
use Session;
use Log;

use SmartHub\FbInsights\Jobs\StorePagePosts;

class FbInsightController extends Controller
{
	private $fb;

	public function __construct(\SammyK\LaravelFacebookSdk\LaravelFacebookSdk $fb) {

		$this->fb = $fb;
		
		/*$this->fb = $fb->newInstance([
					      'app_id' => env('FACEBOOK_APP_ID2'),
					      'app_secret' => env('FACEBOOK_APP_SECRET2'),
					      'default_graph_version' => 'v2.10',
					      // . . .
					    ]);*/
	}


    // authenticating user facebook account
    public function authFacebook() {
    	// Send an array of permissions to request
	    $login_url = $this->fb->getLoginUrl(['email', 'pages_show_list', 'manage_pages', 'pages_manage_cta']);

	    // Obviously you'd do this in blade :)
	    return '<a href="' . $login_url . '">Login with Facebook</a>';
    }


    // authenticating user facebook account
    public function storeUserFanpages() {

    	    // Obtain an access token.
		    try {
		        $token = $this->fb->getAccessTokenFromRedirect();
		    } catch (Facebook\Exceptions\FacebookSDKException $e) {
		        dd($e->getMessage());
		    }


		    // Access token will be null if the user denied the request
		    // or if someone just hit this URL outside of the OAuth flow.
		    if (! $token) {
		        // Get the redirect helper
		        $helper = $this->fb->getRedirectLoginHelper();

		        if (! $helper->getError()) {
		            abort(403, 'Unauthorized action.');
		        }

		        // User denied the request
		        dd(
		            $helper->getError(),
		            $helper->getErrorCode(),
		            $helper->getErrorReason(),
		            $helper->getErrorDescription()
		        );
		    }

		    if (! $token->isLongLived()) {
		        // OAuth 2.0 client handler
		        $oauth_client = $this->fb->getOAuth2Client();

		        // Extend the access token.
		        try {
		            $token = $oauth_client->getLongLivedAccessToken($token);
		        } catch (Facebook\Exceptions\FacebookSDKException $e) {
		            dd($e->getMessage());
		        }
		    }


		    $this->fb->setDefaultAccessToken($token);

		    // Save for later
		    Session::put('fb_user_access_token', (string) $token);

		    $accessToken = $token;

		    // $accessToken = 'EAAC1Wdc6WdsBALtUOz64gNx0uZCv7CTmuzKzTRfJCq1oMwbr9qxluT8M2pTf48DL4fUBoeOyAZAMVFXWC2ZCYCVMbZCw8fBYWARJnhF2n8BAmCo9ORCSUhdUgNTRQwM27T2Y1TIZA47Spkj286cMj1VRCjIS6ld0ZD';

		    // Get basic info on the user from Facebook.
		    try {
		        $response = $this->fb->get('/me/?fields=id,name,email', $accessToken);

		    } catch(Facebook\Exceptions\FacebookResponseException $e) {
			  echo 'Graph returned an error: ' . $e->getMessage();
			  exit;
			} catch(Facebook\Exceptions\FacebookSDKException $e) {
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
			  exit;
			}

		    // Convert the response to a `Facebook/GraphNodes/GraphUser` collection
		    $userNode =  $response->getGraphUser();
		    $userFbId = $userNode->getField('id');
		    $userName = $userNode->getField('name');
		    $userEmail = $userNode->getField('email');

		    $user = FbUser::firstorCreate(['fb_id'=>$userFbId], ['name'=>$userName, 'email'=>$userEmail]);


		    // dd($user);
		    
		    try {
			  // Returns a `FacebookFacebookResponse` object
			  $response = $this->fb->get(
			    '/me/accounts',
			    $accessToken
			  );
			} catch(FacebookExceptionsFacebookResponseException $e) {
			  echo 'Graph returned an error: ' . $e->getMessage();
			  exit;
			} catch(FacebookExceptionsFacebookSDKException $e) {
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
			  exit;
			}

			$userPagesNode = $response->getGraphEdge();

			$pagesArr = [];

			foreach ($userPagesNode as $node) {

				$pageName = $node->getField('name');
				$pageId = $node->getField('id');
				$pageCategory = $node->getField('category');
				$pageAccessToken = $node->getField('access_token');			

				$pagesArr[] = $pageId;
				// get page insights
				try {
				  
				  // Returns a `FacebookFacebookResponse` object
				  $response = $this->fb->get(
				    "/$pageId/?fields=id,name,fan_count,engagement,checkins,country_page_likes,displayed_message_response_time,founded,link,location,mission,new_like_count,overall_star_rating,phone,price_range,rating_count,start_info,talking_about_count,verification_status,website,were_here_count",
				    $accessToken
				  );

				} catch(FacebookExceptionsFacebookResponseException $e) {
				  echo 'Graph returned an error: ' . $e->getMessage();
				  exit;
				} catch(FacebookExceptionsFacebookSDKException $e) {
				  echo 'Facebook SDK returned an error: ' . $e->getMessage();
				  exit;
				}

				$pageNode = $response->getGraphNode();
				$arr = [];
				$arr['fan_count'] = $pageNode->getField('fan_count');
				$arr['engagement'] = $pageNode->getField('engagement');
				$arr['checkins'] = $pageNode->getField('checkins');
				$arr['country_page_likes'] = $pageNode->getField('country_page_likes');
				$arr['displayed_message_response_time'] = $pageNode->getField('displayed_message_response_time');
				$arr['founded'] = $pageNode->getField('founded');
				$arr['link'] = $pageNode->getField('link');
				$arr['location'] = $pageNode->getField('location');
				$arr['mission'] = $pageNode->getField('mission');
				$arr['new_like_count'] = $pageNode->getField('new_like_count');
				$arr['overall_star_rating'] = $pageNode->getField('overall_star_rating');
				$arr['phone'] = $pageNode->getField('phone');
				$arr['price_range'] = $pageNode->getField('price_range');
				$arr['rating_count'] = $pageNode->getField('rating_count');
				$arr['start_info'] = $pageNode->getField('start_info');
				$arr['talking_about_count'] = $pageNode->getField('talking_about_count');
				$arr['verification_status'] = $pageNode->getField('verification_status');
				$arr['website'] = $pageNode->getField('website');
				$arr['were_here_count'] = $pageNode->getField('were_here_count');

				$pageInsights = json_encode($arr);

				$page = PageInfo::firstorCreate(
												['user_id'=>$user->id, 'page_id'=>$pageId], 
												[
													'page_name' => $pageName, 
													'page_category' => $pageCategory, 
													'page_access_token' => $pageAccessToken,
													'page_insights' => $pageInsights
												]);


				// get page posts insights
				/*try {
				  
				  // Returns a `FacebookFacebookResponse` object
				  $response = $this->fb->get(
				    "/660019274011702/feed?pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message",
				    "EAAC1Wdc6WdsBADIuZBZA0JKHDlFFCYMxQiIzxARm8rz21D3ifsL8x9J6IZChjBikO0svUrcq7KwZCZC8w3IKI2BZBCFAOY3153oo2bEru1ktHKJrEVgeKJMV6uZBeA7a6mO8De1HoXuyjd18goLKZBh4HEXZB9w6cNeOf0s9tyfGXEAZDZD"
				  );

				} catch(FacebookExceptionsFacebookResponseException $e) {
				  echo 'Graph returned an error: ' . $e->getMessage();
				  exit;
				} catch(FacebookExceptionsFacebookSDKException $e) {
				  echo 'Facebook SDK returned an error: ' . $e->getMessage();
				  exit;
				}

				$pageNode = $response->getGraphEdge();*/
				// dd($pageNode);
			}

			if (count($pagesArr) > 0)
				$this->storePagePosts($pagesArr);
			exit;

		    // dd($graphNode);
		    // exit;

		    // Create the user if it does not exist or update the existing entry.
		    // This will only work if you've added the SyncableGraphNodeTrait to your User model.
		    // $user = App\User::createOrUpdateGraphNode($facebook_user);

		    // Log the user into Laravel
		    // Auth::login($user);

		    // return redirect('/')->with('message', 'Successfully logged in with Facebook');
    }


    // store facebook page posts data
    public function storePagePosts($pages = [], $pageNextCursor = false) {

    	$pages = PageInfo::select('page_id', 'user_id', 'page_access_token')->whereIn('page_id', $pages)->get();

    	foreach ($pages as $page) {

			// get page posts insights
			try {
			  
				  // Returns a `FacebookFacebookResponse` object
				  $response = $this->fb->get(
				    "/$page->page_id/feed?summary=true&pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message,updated_time",
				    "$page->page_access_token"
				  );

				/*if ($pageNextCursor) {
					// Returns a `FacebookFacebookResponse` object
					  // $response = $this->fb->get(
					  //   "/$page->page_id/feed?after=$pageNextCursor&summary=true&pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message,updated_time",
					  //   "$page->page_access_token"
					  // );

					   $response = $this->fb->get(
						    "/660019274011702/feed?after=$pageNextCursor&summary=true&pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message,updated_time",
						    "EAAC1Wdc6WdsBANA603h1eMFDYVhrLO1uD3mmoRA3OZAMW8sZCZBZCILA379uMxdotNLezRsDuAzArgNxXeX7Shuj5UfHovyyCPrsA9DBnsT25Wlcakh9Vy00AF8BWUhLphYpKYYRWOiNTruHggiXsdV9wS7uZCi8rR3TiOwXVswZDZD"
						  );
				} else {
					// Returns a `FacebookFacebookResponse` object
					  // $response = $this->fb->get(
					  //   "/$page->page_id/feed?summary=true&pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message,updated_time",
					  //   "$page->page_access_token"
					  // );

					   $response = $this->fb->get(
						    "/660019274011702/feed?after=Q2c4U1pXNTBYM0YxWlhKNVgzTjBiM0o1WDJsa0R5TTJOakF3TVRreU56UXdNVEUzTURJNk16UXdNamN4T0RVNU5qSTBNRFkyTkRNNU1ROE1ZAWEJwWDNOMGIzSjVYMmxrRHlBMk5qQXdNVGt5TnpRd01URTNNREp&summary=true&pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message,updated_time",
						    "EAAC1Wdc6WdsBANA603h1eMFDYVhrLO1uD3mmoRA3OZAMW8sZCZBZCILA379uMxdotNLezRsDuAzArgNxXeX7Shuj5UfHovyyCPrsA9DBnsT25Wlcakh9Vy00AF8BWUhLphYpKYYRWOiNTruHggiXsdV9wS7uZCi8rR3TiOwXVswZDZD"
						  );
				}*/

			  // $response = $this->fb->get(
			  //   "/660019274011702/feed?summary=true&pretty=0&limit=100&fields=shares,created_time,with_tags,type,targeting,status_type,properties,privacy,location,is_published,feed_targeting,admin_creator,message,updated_time",
			  //   "EAAC1Wdc6WdsBANA603h1eMFDYVhrLO1uD3mmoRA3OZAMW8sZCZBZCILA379uMxdotNLezRsDuAzArgNxXeX7Shuj5UfHovyyCPrsA9DBnsT25Wlcakh9Vy00AF8BWUhLphYpKYYRWOiNTruHggiXsdV9wS7uZCi8rR3TiOwXVswZDZD"
			  // );

			} catch(FacebookExceptionsFacebookResponseException $e) {
			  echo 'Graph returned an error: ' . $e->getMessage();
			  exit;
			} catch(FacebookExceptionsFacebookSDKException $e) {
			  echo 'Facebook SDK returned an error: ' . $e->getMessage();
			  exit;
			}

			$pageNode = $response->getGraphEdge();

			// dd($pageNode);

			$pageNextCursor = $pageNode->getNextCursor();

			if (!is_null($pageNextCursor)) {
				// echo "hello there"; exit;

				$userData = [];
				$userData['page_id'] = $page->page_id;
				$userData['user_id'] = $page->user_id;

				$this->loadMoreResults($pageNode, $userData);

				// $nextNode = $this->fb->next($pageNode);
				// $pageNode = $response->getGraphEdge();

				// $this->storePagePosts([$page->page_id], $pageNextCursor);
			}

			// $cursor = $this->fb->next($pageNode);


			$data = [];

			foreach ($pageNode as $node) {



				$data['post_id'] = $node->getField('id');
				$data['message'] = $node->getField('message');
				$data['shares'] = $node->getField('shares');
				$data['created_time'] = $node->getField('created_time');
				$data['with_tags'] = $node->getField('with_tags');
				$data['type'] = $node->getField('type');
				$data['targeting'] = $node->getField('targeting');
				$data['status_type'] = $node->getField('status_type');
				$data['properties'] = $node->getField('properties');
				$data['privacy'] = $node->getField('privacy');
				$data['location'] = $node->getField('location');
				$data['is_published'] = $node->getField('is_published');
				$data['feed_targeting'] = $node->getField('feed_targeting');
				$data['admin_creator'] = $node->getField('admin_creator');
				$data['updated_time'] = $node->getField('updated_time');

				if (!empty($data['post_id'])) {
					
					$storeData['postData'] = $data;
					$storeData['userData'] = ['user_id'=>$page->user_id, 'page_id' => $page->page_id];

					Log::info('Job queued');
					StorePagePosts::dispatch($storeData)
									->delay(now()->addSeconds(25));

					// $post = FbPagePost::firstorCreate(['user_id'=>$page->user_id, 'post_id'=>$data['post_id']], 
					// 								[
					// 									'page_id' => $page->page_id,
					// 									'post_insights' => json_encode($data),
					// 								]);

				}
			}

		}

		echo "its done!";
    }



    public function loadMoreResults($pageNode, $userData = false) {

    	// $response = $pageNode->getGraphEdge();
    	// dd($pageNode);


		$pageNextCursor = $pageNode->getNextCursor();
		// echo $pageNextCursor; exit;

		if (!is_null($pageNextCursor)) {

			$nextNode = $this->fb->next($pageNode);

			if (is_null($nextNode)) {
				return false;
			}

			// dd($nextNode);
			// $pageNode = $nextNode->getGraphEdge();

			$data = [];

			foreach ($nextNode as $node) {

				$data['post_id'] = $node->getField('id');
				$data['message'] = $node->getField('message');
				$data['shares'] = $node->getField('shares');
				$data['created_time'] = $node->getField('created_time');
				$data['with_tags'] = $node->getField('with_tags');
				$data['type'] = $node->getField('type');
				$data['targeting'] = $node->getField('targeting');
				$data['status_type'] = $node->getField('status_type');
				$data['properties'] = $node->getField('properties');
				$data['privacy'] = $node->getField('privacy');
				$data['location'] = $node->getField('location');
				$data['is_published'] = $node->getField('is_published');
				$data['feed_targeting'] = $node->getField('feed_targeting');
				$data['admin_creator'] = $node->getField('admin_creator');
				$data['updated_time'] = $node->getField('updated_time');

				if (!empty($data['post_id'])) {
					
					$storeData['postData'] = $data;
					$storeData['userData'] = ['user_id'=>$userData['user_id'], 'page_id' => $userData['page_id'] ];

					Log::info('Job queued');
					StorePagePosts::dispatch($storeData)
									->delay(now()->addSeconds(25));

					// $post = FbPagePost::firstorCreate(['user_id'=>$page->user_id, 'post_id'=>$data['post_id']], 
					// 								[
					// 									'page_id' => $page->page_id,
					// 									'post_insights' => json_encode($data),
					// 								]);

				}
			}

			$this->loadMoreResults($nextNode, $userData);
			
		} else {
			return false;
		}
	}



}
