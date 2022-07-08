Small set of tools to move your files/folders from a My Drive to a Shared Drive

| File          | Usage                                                                          |
|---------------|--------------------------------------------------------------------------------|
| summary.php   | Go through a folder recursively and detect all owners you need to contact      |
| drives.php    | List all team drives (Just for checking permissions)                           |
| mover.php     | Move file by file and create the required folder structure in the Shared Drive |
| cleanup.php   | Cleanup empty folders in the old My Drive                                      |

Every owner listed in `summary.php` will need to run the `mover.php` script.
At the end `cleanup.php` may be called. Due to mixed permissions the cleanup script might not delete all folders at once but the script may need to be called several times to properly cleanup nested folders with different permissions.

### Installation & Usage

* Read the tutorial (https://developers.google.com/drive/api/quickstart/php) and make sure you get your credentials.json from the Google Cloud Console
* Running any of those script will require login through your browser to authenticate
* The Browser will fail at the end, and you need to copy & paste the code, which you can find in the url
* After successful login a token.json should have been created
* Delete token.json if you want to proceed moving with another user
* Everyone also needs to be granted access to the target Shared Drive
* Update folder IDs in the `config.php`
* Make a dry run with `php mover.php` and verify that it identifies the correct files
* Clear your Google Drive Trash! Trashed files cannot be moved an will fail the mover!
* Update the `$dryRun` variable to false and run `php mover.php`again
* For very big drives keep in mind that there is a daily API request limit!