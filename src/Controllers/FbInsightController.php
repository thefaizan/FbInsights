<?php

namespace SmartHub\FbInsights\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as Controller;

use SmartHub\FbInsights\Models\FbUser;
use SmartHub\FbInsights\Models\PageInfo;
use Session;

class FbInsightController extends Controller
{


    // authenticating user facebook account
    public function authFacebook(\SammyK\LaravelFacebookSdk\LaravelFacebookSdk $fb) {
    	// Send an array of permissions to request
	    $login_url = $fb->getLoginUrl(['email', 'pages_show_list', 'manage_pages', 'pages_manage_cta']);

	    // Obviously you'd do this in blade :)
	    return '<a href="' . $login_url . '">Login with Facebook</a>';
    }


    // authenticating user facebook account
    public function storeUserFanpages(\SammyK\LaravelFacebookSdk\LaravelFacebookSdk $fb) {

    	    // Obtain an access token.
		    try {
		        $token = $fb->getAccessTokenFromRedirect();
		    } catch (Facebook\Exceptions\FacebookSDKException $e) {
		        dd($e->getMessage());
		    }


		    // Access token will be null if the user denied the request
		    // or if someone just hit this URL outside of the OAuth flow.
		    if (! $token) {
		        // Get the redirect helper
		        $helper = $fb->getRedirectLoginHelper();

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
		        $oauth_client = $fb->getOAuth2Client();

		        // Extend the access token.
		        try {
		            $token = $oauth_client->getLongLivedAccessToken($token);
		        } catch (Facebook\Exceptions\FacebookSDKException $e) {
		            dd($e->getMessage());
		        }
		    }


		    $fb->setDefaultAccessToken($token);

		    // Save for later
		    Session::put('fb_user_access_token', (string) $token);

		    $accessToken = $token;

		    // $accessToken = 'EAAC1Wdc6WdsBALtUOz64gNx0uZCv7CTmuzKzTRfJCq1oMwbr9qxluT8M2pTf48DL4fUBoeOyAZAMVFXWC2ZCYCVMbZCw8fBYWARJnhF2n8BAmCo9ORCSUhdUgNTRQwM27T2Y1TIZA47Spkj286cMj1VRCjIS6ld0ZD';

		    // Get basic info on the user from Facebook.
		    try {
		        $response = $fb->get('/me/?fields=id,name,email', $accessToken);

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
			  $response = $fb->get(
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


			foreach ($userPagesNode as $node) {

				$pageName = $node->getField('name');
				$pageId = $node->getField('id');
				$pageCategory = $node->getField('category');
				$pageAccessToken = $node->getField('access_token');			

				// get page insights
				try {
				  
				  // Returns a `FacebookFacebookResponse` object
				  $response = $fb->get(
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
			}

			echo "done";
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
}
