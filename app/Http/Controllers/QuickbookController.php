<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RendersLegacyPages;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use QuickBooksOnline\API\Core\OAuth\OAuth2\OAuth2LoginHelper;
use QuickBooksOnline\API\DataService\DataService;
use URL;
use App\Support\AccessMessages;

/**
 * The QuickBooks connection page and its OAuth callback. Formerly actions of LexaAdmin, moved as
 * they were.
 */
class QuickbookController extends Controller
{
    use RendersLegacyPages;

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('2fa');
        // Kept from LexaAdmin, whose pages load whole result sets at once.
        ini_set('memory_limit', '-1');
    }

    public function quickbook() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if((Auth::user()->can('admin'))) {
            $accessTokenDb = DB::table('quickbook')->where('id',1)->value('data');
            try {
                $accessToken = !empty($accessTokenDb) ? unserialize($accessTokenDb) : null;
            } catch (\Throwable) {
                $accessTokenDb = null;
                $accessToken = null;
            }
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => config('app.quick_client_id'),
                'ClientSecret' =>  config('app.quick_client_secret'),
                'RedirectURI' => URL::to('/').config('app.quick_oauth_redirect_uri'),
                'scope' => config('app.quick_oauth_scope'),
                'baseUrl' => config('app.quick_baseUrl')
            ));
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            assert($OAuth2LoginHelper instanceof OAuth2LoginHelper);
            if(empty($accessTokenDb) || strtotime($accessToken->getRefreshTokenExpiresAt())<time()) {
                $authUrl = $OAuth2LoginHelper->getAuthorizationCodeURL();
                $userInfo="";
            } else {
                $authUrl = NULL;
                $dataService->updateOAuth2Token($accessToken);
                if(strtotime($accessToken->getAccessTokenExpiresAt())<time()){
                    $refreshedAccessTokenObj = $OAuth2LoginHelper->refreshAccessTokenWithRefreshToken($accessToken->getRefreshToken());
                    $dataService->updateOAuth2Token($refreshedAccessTokenObj);
                    DB::table('quickbook')->where('id',1)->update(["data"=>serialize($refreshedAccessTokenObj)]);
                    $accessToken = $refreshedAccessTokenObj;
                }
                $oauthLoginHelper = $dataService->getOAuth2LoginHelper();
                assert($oauthLoginHelper instanceof OAuth2LoginHelper);
                $userInfo = $oauthLoginHelper->getUserInfo($accessToken->getAccessToken());
            }
            $res_view = view('quickbook.index',['alert'=>'','title'=>'Quickbook','br1'=>'Quickbook','br2'=>'Index','authUrl'=>$authUrl,'userInfo'=>$userInfo]);
            return $this->page(request(), $res_view);
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }

    public function quickbookCallback() {
        if(Auth::user()->isblocked_or_isactive()) {
            return abort(403, AccessMessages::ACCOUNT_INACTIVE);
        }
        if((Auth::user()->can('admin'))) {
            $dataService = DataService::Configure(array(
                'auth_mode' => 'oauth2',
                'ClientID' => config('app.quick_client_id'),
                'ClientSecret' =>  config('app.quick_client_secret'),
                'RedirectURI' => URL::to('/').config('app.quick_oauth_redirect_uri'),
                'scope' => config('app.quick_oauth_scope'),
                'baseUrl' => config('app.quick_baseUrl')
            ));
            $OAuth2LoginHelper = $dataService->getOAuth2LoginHelper();
            assert($OAuth2LoginHelper instanceof OAuth2LoginHelper);
            if(request()->query->has('code') && request()->query->has('realmId')) {
                $accessToken = $OAuth2LoginHelper->exchangeAuthorizationCodeForToken(request()->query('code'), request()->query('realmId'));
                $dataService->updateOAuth2Token($accessToken);
                DB::table('quickbook')->where('id',1)->update(["data"=>serialize($accessToken)]);
                return response("<script>window.close();</script>");
            }
            return abort(400, 'QuickBooks authorization was not completed.');
        } else {
            return abort(403, AccessMessages::NO_PERMISSION);
        }
    }
}
