# rutorrent-thirdparty-plugins
Plugins for ruTorrent web client, filemanager, fileshare, fileupload, mediastream

easily manage your shell files from rutorrent web interface

# filemanager plugin setup:
edit filemanager/conf.php 
chmod 755 plugins/filemanager/scripts/sucmd.sh

# fileshare plugin setup
- create a symlink to /fileshare/share.php outside the AUTH protected space (domain.com/noauth/share.php, cdn.domain.com/fshare.php, etc)
- edit fileshare/conf.php with that full domain path
