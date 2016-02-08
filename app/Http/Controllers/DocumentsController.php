<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentsController extends Controller
{
    public function __construct()
    {
        define('APPLICATION_NAME', 'Drive API PHP Quickstart');
        define('CREDENTIALS_PATH', '~/.credentials/drive-php-quickstart.json');
        define('CLIENT_SECRET_PATH', config_path() . '/client_secret.json');
        define('SCOPES', implode(' ', array(
                \Google_Service_Drive::DRIVE_METADATA_READONLY)
        ));
    }

    public function storeDocument(Request $request, $storage = 's3')
    {
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            if (\Storage::disk($storage)->put($file->getFilename(), file_get_contents($file->getPathname()))) {
                return \Response::json(['success' => true]);
            } else {
                return \Response::json(['success' => false, 'error' => error_get_last()]);
            }
        }
        return \Response::json(['success' => false, 'error' => 'Document not found']);
    }

    public function listDocuments($storage = 's3')
    {
        var_dump(\Storage::disk($storage)->allFiles());
    }


    public function getFileListFromGoogleDrive()
    {
        // Get the API client and construct the service object.
        $client = $this->getClient();
        $service = new \Google_Service_Drive($client);

        // Print the names and IDs for up to 10 files.
        $optParams = array(
            //'pageSize' => 10,
            //'fields' => "nextPageToken, files(id, name)"
        );
        $results = $service->files->listFiles($optParams);

        if ($results->count() == 0) {
            print "No files found.\n";
        } else {
            print "Files:\n";
            while ($file = $results->next()) {
                $file = $this->hint($file);
                //printf("%s (%s)\n", $file->getName(), $file->getId());
                var_dump($file);
            }
        }
    }

    /**
     * Returns an authorized API client.
     * @return \Google_Client the authorized client object
     */
    function getClient()
    {
        $client = new \Google_Client();
        $client->setApplicationName(APPLICATION_NAME);
        $client->setScopes(SCOPES);
        $client->setAuthConfigFile(CLIENT_SECRET_PATH);
        $client->setAccessType('offline');

        // Load previously authorized credentials from a file.
        $credentialsPath = $this->expandHomeDirectory(CREDENTIALS_PATH);
        if (file_exists($credentialsPath)) {
            $accessToken = file_get_contents($credentialsPath);
        } else {
            // Request authorization from the user.
            //$authUrl = $client->createAuthUrl();
            //printf("Open the following link in your browser:\n%s\n", $authUrl);
            //print 'Enter verification code: ';
            //$authCode = trim(fgets(STDIN));
            $authCode = '4/hTKdasF_ajjCkP_s7-yvG6sRlBc06FipmSQmSoQOYPI';

            // Exchange authorization code for an access token.
            $accessToken = $client->authenticate($authCode);

            // Store the credentials to disk.
            if (!file_exists(dirname($credentialsPath))) {
                mkdir(dirname($credentialsPath), 0700, true);
            }
            file_put_contents($credentialsPath, $accessToken);
            printf("Credentials saved to %s\n", $credentialsPath);
        }
        $client->setAccessToken($accessToken);

        // Refresh the token if it's expired.
        if ($client->isAccessTokenExpired()) {
            dd('expired');
            $client->refreshToken($client->getRefreshToken());
            file_put_contents($credentialsPath, $client->getAccessToken());
        }
        return $client;
    }

    /**
     * Expands the home directory alias '~' to the full path.
     * @param string $path the path to expand.
     * @return string the expanded path.
     */
    private function expandHomeDirectory($path)
    {
        $homeDirectory = getenv('HOME');
        if (empty($homeDirectory)) {
            $homeDirectory = getenv("HOMEDRIVE") . getenv("HOMEPATH");
        }
        return str_replace('~', realpath($homeDirectory), $path);
    }

    /**
     * @param $file
     * @return \Google_Service_Drive_DriveFile
     */
    private function hint($file)
    {
        return $file;
    }


}
