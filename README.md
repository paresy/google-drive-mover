Small set of tools to move your files/folders from a My Drive to a Shared Drive

File        | Usage
----------- | ---------------------
summary.php | Go through a folder recursively and detect all owners you need to contact
drives.php  | List all team drives (Just for checking permissions)
mover.php   | Move file by file and create the required folder structure in the Shared Drive
cleanup.php | Cleanup empty folders in the old My Drive

Every Owner listed in `summary.php` will need to run the `mover.php` script.
At the end `cleanup.php` may be called. Due to mixed permissions the cleanup script might not delete all folders at once but the script may need to be called several times to properly cleanup nested folder with different permissions.