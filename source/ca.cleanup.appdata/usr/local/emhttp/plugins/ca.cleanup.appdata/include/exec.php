<?PHP

###############################################################
#                                                             #
# Community Applications copyright 2015-2016, Andrew Zawadzki #
#                                                             #
###############################################################

require_once("/usr/local/emhttp/plugins/ca.cleanup.appdata/include/helpers.php");
require_once("/usr/local/emhttp/plugins/dynamix.docker.manager/include/DockerClient.php");
require_once("/usr/local/emhttp/plugins/ca.cleanup.appdata/include/xmlHelpers.php");

############################################
############################################
##                                        ##
## BEGIN MAIN ROUTINES CALLED BY THE HTML ##
##                                        ##
############################################
############################################


switch ($_POST['action']) {

#########################################
#                                       #
# Displays the orphaned appdata folders #
#                                       #
#########################################

case 'getOrphanAppdata':
	libxml_use_internal_errors(true);
  $all_files = glob("/boot/config/plugins/dockerMan/templates-user/*.xml");
  if ( is_dir("/var/lib/docker/tmp") ) {
    $DockerClient = new DockerClient();
    $info = $DockerClient->getDockerContainers();
  } else {
    $info = array();
  }

  # Get the list of appdata folders used by all of the my* templates
  $availableVolumes = array();
  foreach ($all_files as $xmlfile) {
		$o = XML2Array::createArray(file_get_contents("$xmlfile"));
		reset($o);
		$first_key = key($o);
		$o = $o[$first_key]; # get the name of the first key (root of the xml)
		if ( isset($o['Data']['Volume']) ) {
			if ( $o['Data']['Volume'][0] ) {
				$volumes = $o['Data']['Volume'];
			} else {
				unset($volumes);
				$volumes[] = $o['Data']['Volume'];
			}
			foreach ( $volumes as $volumeArray ) {
				$volumeList[0] = $volumeArray['HostDir'].":".$volumeArray['ContainerDir'];
				if ( findAppdata($volumeList) ) {
					$temp['Name'] = $o['Name'];
					$temp['HostDir'] = $volumeArray['HostDir'];
					$availableVolumes[$volumeArray['HostDir']] = $temp;
				}
			}
		} 
    
  }

  # remove from the list the folders used by installed docker apps
  
  foreach ($info as $installedDocker) {
    if ( ! is_array($installedDocker['Volumes']) ) {
      continue;
    }
     foreach ($installedDocker['Volumes'] as $volume) {
       $folders = explode(":",$volume);
       $cacheFolder = str_replace("/mnt/user/","/mnt/cache/",$folders[0]);
       $userFolder = str_replace("/mnt/cache/","/mnt/user/",$folders[0]);
       unset($availableVolumes[$cacheFolder]);
       unset($availableVolumes[$userFolder]);
     }
  }
  
  # remove from list any folders which don't actually exist
  
  $temp = $availableVolumes;
  foreach ($availableVolumes as $volume) {
    $userFolder = str_replace("/mnt/cache/","/mnt/user/",$volume['HostDir']);
    
    if ( ! is_dir($userFolder) ) {
      unset($temp[$volume['HostDir']]);
    }
		if ( $userFolder == "/" || $userFolder == "/mnt/" || $userFolder == "/mnt/user/" ) {
			unset($temp[$volume['HostDir']]);
		}
  }
  $availableVolumes = $temp;

  # remove from list any folders which are equivalent 
  $tempArray = $availableVolumes;
  foreach ( $availableVolumes as $volume ) {
    $flag = false;
    foreach ( $availableVolumes as $testVolume ) {
      if ( $testVolume['HostDir'] == $volume['HostDir'] ) {
        continue; # ie: its the same index in the array;
      }
     $cacheFolder = str_replace("/mnt/user/","/mnt/cache/",$volume['HostDir']);
     $userFolder = str_replace("/mnt/cache/","/mnt/user/",$volume['HostDir']);
      if ( startswith($testVolume['HostDir'],$cacheFolder) || startsWith($testVolume['HostDir'],$userFolder) ) {
        $flag = true;
        break;
      }
    }
    if ( $flag ) {
      unset($tempArray[$volume['HostDir']]);
    }
  }
  $availableVolumes = $tempArray;
  
  foreach ($tempArray as $testVolume) {
    if ( ! $installedDocker['Volumes'] ) {
      continue;
    }
    foreach ($installedDocker['Volumes'] as $volume) {
      $folders = explode(":",$volume);
      $cacheFolder = str_replace("/mnt/user/","/mnt/cache/",$folders[0]);
      $userFolder = str_replace("/mnt/cache/","/mnt/user/",$folders[0]);
      if ( startswith($cacheFolder,$testVolume['HostDir']) || startsWith($userFolder,$testVolume['HostDir']) ) {
        unset($availableVolumes[$testVolume['HostDir']]);
      }
    }
  }
  
  if ( empty($availableVolumes) ) {
    echo "No orphaned appdata folders found <script>$('#selectAll').prop('disabled',true);</script>";
  } else {
    foreach ($availableVolumes as $volume) {
      echo "<input type='checkbox' class='appdata' value=".htmlentities($volume['HostDir'])." onclick='$(&quot;#deleteButton&quot;).prop(&quot;disabled&quot;,false);'>".$volume['Name'].":  <b>".$volume['HostDir']."</b><br>";
    }
  }
  break;
  
########################################
#                                      #
# Deletes the selected appdata folders #
#                                      #
########################################

case "deleteAppdata":
  $paths = getPost("paths","no");
  $paths = explode("*",$paths);
  foreach ($paths as $path) {
    $userPath = str_replace("/mnt/cache/","/mnt/user/",$path);
    exec ("rm -rf ".escapeshellarg($userPath));
  }
  echo "deleted";
  break;
  

}
?>
