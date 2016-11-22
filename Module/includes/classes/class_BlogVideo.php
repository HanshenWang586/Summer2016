<?php
class BlogVideo {

	function __construct($video_id = '') {
		if (ctype_digit($video_id)) {
			$this->video_id = $video_id;
		}
	}

	public function getEmbeddable() {
		$content = "<script language=\"JavaScript\" type=\"text/javascript\">
var hasProductInstall = DetectFlashVer(6, 0, 65);

var hasRequestedVersion = DetectFlashVer(requiredMajorVersion, requiredMinorVersion, requiredRevision);

if ( hasProductInstall && !hasRequestedVersion ) {
	var MMPlayerType = (isIE == true) ? \"ActiveX\" : \"PlugIn\";
	var MMredirectURL = window.location;
    document.title = document.title.slice(0, 47) + \" - Flash Player Installation\";
    var MMdoctitle = document.title;

	AC_FL_RunContent(
		\"src\", \"playerProductInstall\",
		\"FlashVars\", \"MMredirectURL=\"+MMredirectURL+'&MMplayerType='+MMPlayerType+'&MMdoctitle='+MMdoctitle+\"\",
		\"width\", \"100%\",
		\"height\", \"100%\",
		\"align\", \"middle\",
		\"id\", \"test\",
		\"quality\", \"high\",
		\"bgcolor\", \"#869ca7\",
		\"name\", \"test\",
		\"allowScriptAccess\",\"sameDomain\",
		\"type\", \"application/x-shockwave-flash\",
		\"pluginspage\", \"http://www.adobe.com/go/getflashplayer\"
	);
} else if (hasRequestedVersion) {
	AC_FL_RunContent(
			\"src\", \"/images/flash/blog_player\",
			\"width\", \"450\",
			\"height\", \"450\",
			\"align\", \"middle\",
			\"id\", \"test\",
			\"quality\", \"high\",
			\"bgcolor\", \"#869ca7\",
			\"name\", \"blog_player\",
			\"allowScriptAccess\",\"sameDomain\",
			\"type\", \"application/x-shockwave-flash\",
			\"FlashVars\", \"video_id=$this->video_id\",
			\"pluginspage\", \"http://www.adobe.com/go/getflashplayer\"
	);
  } else {
    var alternateContent = ''
  	+ 'This content requires the Adobe Flash Player. '
   	+ '<a href=http://www.adobe.com/go/getflash/>Get Flash</a>';
    document.write(alternateContent);
  }
</script>";

	$content = preg_replace('/\s+/', ' ', $content);
	$content = str_replace("\n", '', $content);
	return $content;
	}
}
?>