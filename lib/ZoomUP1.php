<?php

namespace RechercheCreneauxLib;
use ZoomLibrary\Zoom;
use Exception;

/**
 * ZoomUP1
 * classe étendant la librairie zoom-php
 * la méthode tokenUP1 récupère le token zoom
 */
class ZoomUP1 extends Zoom {

    public bool $newToken = true;
    public function tokenUP1($code)
    {
      $response = $this->CLIENT->request('POST', '/oauth/token', [
          "headers" => [
              "Authorization" => "Basic ". base64_encode($this->CLIENT_ID.':'.$this->CLIENT_SECRET)
          ],
          'form_params' => [
              "grant_type" => "account_credentials",
              "account_id" => $code
          ],
      ]);
      $response_token =json_decode($response->getBody()->getContents(), true);
      $token = json_encode($response_token);
      file_put_contents($this->CREDENTIAL_PATH, $token);

      if (!file_exists($this->CREDENTIAL_PATH))
        throw new Exception("Error file Token ! ");

      $this->CREDENTIAL_DATA = $response_token;

      $savedToken = json_decode(file_get_contents($this->CREDENTIAL_PATH), true); //getting json from saved json file
      if (!$this->newToken && !empty(array_diff($savedToken,$response_token))) { // checking reponse token and saved tokends are same
        return ['status' => false, 'message' => 'Error in saved token'];
      }
      $this->newToken = false;
      return ['status' => true, 'message' => 'Token saved successfully'];
    }

    public function inviteLink($meeting_id = '', $json = [])
    {
      try {
        $response = $this->CLIENT->request('POST', "/v2/meetings/{$meeting_id}/invite_links", [
            "headers" => [
                "Authorization" => "Bearer ".$this->CREDENTIAL_DATA['access_token']
            ],
            'json' => $json
        ]);

        if ($response->getStatusCode() == 201) {
          return array('status' => true, 'message' => 'Registration successfull', 'data' => json_decode($response->getBody(), true) );
        }

        throw new Exception("Not able to find error");
      }
      catch (Exception $e) {
        if( $e->getCode() == 401 && $this->refreshToken() ) {
          return $this->inviteLink($meeting_id, $json);
        }
        if ($e->getCode() == 300) {
          return array('status' => false, 'message' => 'Meeting {meetingId} is not found or has expired.');
        }
        if ($e->getCode() == 400) {
          return array('status' => false, 'message' => 'Access error. Not have correct access. validation failed');
        }
        if ($e->getCode() == 404) {
          return array('status' => false, 'message' => 'Meeting not found or Meeting host does not exist: {userId}.');
        }
        if( $e->getCode() != 401 ) {
          return array('status' => false, 'message' => $e->getMessage());
        }
        return array('status' => false, 'message' => 'Not able to refresh token');
      }
    }
}
